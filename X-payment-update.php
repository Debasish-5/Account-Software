<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

include 'connection.php';
include 'navigation.php';

ob_start(); // Start output buffering

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['rollNo'] = $_POST['rollNo'];
    $_SESSION['year'] = $_POST['year'];
}

$rollNo = isset($_SESSION['rollNo']) ? $_SESSION['rollNo'] : '';
$year = isset($_SESSION['year']) ? $_SESSION['year'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["rollNo"]) && isset($_POST["year"])) {
    $rollNo = $_POST["rollNo"];
    $year = $_POST["year"];

    if ($year == 1) {
        $year = "1st_yr";
    } elseif ($year == 2) {
        $year = "2nd_yr";
    } elseif ($year == 3) {
        $year = "3rd_yr";
    }

    $sql = "SELECT * FROM payment WHERE `ID` = ?";
    if ($stmt = $con->prepare($sql)) {
        $stmt->bind_param("s", $rollNo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            $totalAmount = $row[$year];
            $paymentType = $row["{$year}_payment_type"];
            $oneTimePaymentMode = $row["{$year}_one_time_payment_method"];
            $oneTimePaymentTransactionId = $row["{$year}_one_time_payment_transaction_id"];
            $oneTimePaymentTimestamp = $row["{$year}_one_time_payment_timestamp"];
        } else {
            echo "No payment data found for the provided roll number and year.<br>";
            exit;
        }
    } else {
        echo "Error: Unable to fetch payment data.<br>";
        exit;
    }

    if (isset($_POST["totalAmount"]) && isset($_POST["payment-type"])) {
        $totalAmount = $_POST["totalAmount"];
        $paymentType = $_POST["payment-type"];

        // Update total payment and payment type
        $sql = "UPDATE payment SET `{$year}` = ?, `{$year}_payment_type` = ? WHERE `ID` = ?";
        if ($stmt = $con->prepare($sql)) {
            $stmt->bind_param("iis", $totalAmount, $paymentType, $rollNo);
            if ($stmt->execute()) {
                echo "Payment successful.<br>";
            } else {
                echo "Error updating payment information: " . $stmt->error . "<br>";
            }
            $stmt->close();
        } else {
            echo "Error: Unable to prepare SQL statement.<br>";
        }

        // Handle one-time payment submission
        if ($paymentType == 1 && isset($_POST["oneTimePaymentSubmit"])) {
            $oneTimePaymentMode = $_POST["oneTimePaymentMode"];
            $oneTimePaymentTransactionId = $_POST["oneTimePaymentTransactionId"];

            $oneTimePaymentInfo =  "{$year}_one_time_payment";
            $sql = "UPDATE payment SET 
                        `{$year}_one_time_payment` = ?, 
                        `{$year}_one_time_payment_method` = ?, 
                        `{$year}_one_time_payment_transaction_id` = ?, 
                        `{$year}_one_time_payment_timestamp` = NOW() 
                    WHERE `ID` = ?";
            if ($stmt = $con->prepare($sql)) {
                $stmt->bind_param("iiss", $totalAmount, $oneTimePaymentMode, $oneTimePaymentTransactionId, $rollNo);
                if ($stmt->execute()) {
                    echo "One-time payment information updated successfully.<br>";
                    // Insert one-time payment into payment history
                    $insertHistorySql = "INSERT INTO bill (roll_no, payment_type, payment_info, amount, payment_mode, transaction_id) VALUES (?, ?, ?, ?, ?, ?)";
                    if ($historyStmt = $con->prepare($insertHistorySql)) {
                        $historyStmt->bind_param("sisiss", $rollNo, $paymentType, $oneTimePaymentInfo, $totalAmount, $oneTimePaymentMode, $oneTimePaymentTransactionId);
                        $historyStmt->execute();
                        $historyStmt->close();
                    } else {
                        echo "Error: Unable to prepare SQL statement for payment history.<br>";
                    }
                } else {
                    echo "Error updating one-time payment information: " . $stmt->error . "<br>";
                }
                $stmt->close();
            } else {
                echo "Error: Unable to prepare SQL statement for one-time payment.<br>";
            }
        }

        // Process installment payments
        if ($paymentType == 0) { // installment payments
            for ($i = 1; $i <= 4; $i++) {
                if (isset($_POST["{$i}_installment_btn"])) {
                    $installmentAmount = $_POST["{$i}_installment"];
                    $installmentPaymentMode = $_POST["installment{$i}_payment_mode"];
                    $installmentTransactionId = $_POST["{$i}_installment_transaction_id"];
                    // $installmentTimestamp = date('Y-m-d H:i:s');

                    // Determine installment labels based on $i
                    $installmentSuffix = ["1st", "2nd", "3rd", "4th"][$i - 1];
                    $installmentField = "{$year}_{$installmentSuffix}_installment";
                    $installmentModeField = "{$year}_{$installmentSuffix}_installment_payment_method";
                    $installmentTransactionField = "{$year}_{$installmentSuffix}_installment_transaction_id";
                    $installmentTimestampField = "{$year}_{$installmentSuffix}_installment_timestamp";


                    // Update installment details including the timestamp
                    $sql = "UPDATE payment SET 
                                $installmentField = ?, 
                                $installmentModeField = ?, 
                                $installmentTransactionField = ?, 
                                $installmentTimestampField = NOW() 
                            WHERE `ID` = ?";
                    if ($stmt = $con->prepare($sql)) {
                        $stmt->bind_param("iiss", $installmentAmount, $installmentPaymentMode, $installmentTransactionId, $rollNo);
                        if ($stmt->execute()) {
                            echo "$installmentSuffix Installment information updated successfully.<br>";
                            // Insert installment into payment history
                            $insertHistorySql = "INSERT INTO bill (roll_no, payment_type, payment_info, amount, payment_mode, transaction_id) VALUES (?, ?, ?, ?, ?, ?)";
                            if ($historyStmt = $con->prepare($insertHistorySql)) {
                                $historyStmt->bind_param("sisiss", $rollNo, $paymentType, $installmentField, $installmentAmount, $installmentPaymentMode, $installmentTransactionId);
                                $historyStmt->execute();
                                $historyStmt->close();
                            } else {
                                echo "Error: Unable to prepare SQL statement for payment history.<br>";
                            }
                        } else {
                            echo "Error updating installment $installmentSuffix information: " . $stmt->error . "<br>";
                        }
                        $stmt->close();
                    } else {
                        echo "Error: Unable to prepare SQL statement for installment $installmentSuffix.<br>";
                    }
                }
            }
        }
    }
} else {
    echo "Error: Roll No. or Year not received!<br>";
    exit;
}

$messages = ob_get_clean(); // Get the buffer content and clean the buffer
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="payment-update.css">
</head>

<body>
    <div class="content">
        <div class="header">
            <h2>Payment Update</h2>
            <button type="button" onclick="window.location.href='bills.php'" id="go-to-bills">Go to Bills</button>
        </div>
        <div class="main-content">
        <div id="information-container">
            <h3><?php echo $messages; ?></h3>
        </div>
            <div class="total-students">
                <h2><?php echo 'Roll No. : ' . htmlspecialchars($_POST["rollNo"]); ?></h2>
                <?php echo '<h2>Year : ' . htmlspecialchars($year) . '</h2>'; ?>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="rollNo" value="<?php echo htmlspecialchars($_POST["rollNo"]); ?>">
                <input type="hidden" name="year" value="<?php echo htmlspecialchars($year); ?>">
                <input type="hidden" name="rollNo" value="<?php echo htmlspecialchars($rollNo); ?>">
                <input type="hidden" name="year" value="<?php echo htmlspecialchars($_POST["year"]); ?>">
                <div class="container">
                    <div class="inputBox">
                        <label for="year">Current Year</label>
                        <input type="text" value="<?php echo htmlspecialchars($year); ?>" readonly>
                    </div>
                    <div class="inputBox">
                        <label for="totalAmount">Total Annual Fee</label>
                        <input type="number" name="totalAmount" value="<?php echo htmlspecialchars($totalAmount); ?>" placeholder="Enter Total Amount" required inputmode="numeric">
                    </div>
                    <div class="inputBox">
                        <label for="paidAmount">Paid Amount</label>
                        <input type="number" name="paidAmount" value="<?php echo $paidAmount; ?>" disabled inputmode="numeric">
                    </div>
                    <div class="inputBox">
                        <label for="remainingAmount">Remaining Amount</label>
                        <input type="number" name="remainingAmount" value="<?php echo $remainingAmount; ?>" disabled inputmode="numeric">
                    </div>
                </div>

                <div class="container">
                    <div class="inputBox payment-type">
                        <label for="p-type">Payment Type</label>
                        <div class="enter">
                            <div class="grid">
                                <input type="radio" name="payment-type" id="once" value="1" onclick="toggleInstallmentContainer()" <?php if ($paymentType == 1) echo 'checked'; ?>>
                                <label for="once">One Time Payment</label>
                            </div>
                            <div class="grid">
                                <input type="radio" name="payment-type" id="installment" value="0" onclick="toggleInstallmentContainer()" <?php if ($paymentType == 0) echo 'checked'; ?>>
                                <label for="installment">Pay in 4 Installments</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container installment" id="oneTimePaymentContainer" style="display: none;">
                    <div class="inputBox">
                        <label for="otp">Timestamp</label>
                        <input type="text" name="oneTimePaymentTimestamp" value="<?php echo htmlspecialchars($oneTimePaymentTimestamp); ?>" disabled>
                    </div>
                    <div class="inputBox">
                        <label for="p-mode">Payment Mode</label>
                        <div class="enter">
                            <div class="grid">
                                <input type="radio" name="oneTimePaymentMode" id="oneTimePaymentCash" value="0" <?php if ($oneTimePaymentMode == 0) echo 'checked'; ?>>
                                <label for="oneTimePaymentCash">Cash</label>
                            </div>
                            <div class="grid">
                                <input type="radio" name="oneTimePaymentMode" id="oneTimePaymentOnline" value="1" <?php if ($oneTimePaymentMode == 1) echo 'checked'; ?>>
                                <label for="oneTimePaymentOnline">Online</label>
                            </div>
                        </div>
                    </div>
                    <div class="inputBox">
                        <label for="transactionId">Transaction ID</label>
                        <input type="text" name="oneTimePaymentTransactionId" value="<?php echo htmlspecialchars($oneTimePaymentTransactionId); ?>" placeholder="Required for Online Transaction">
                    </div>
                    <button type="submit" name="oneTimePaymentSubmit">Update Payment</button>
                </div>

                <div class="container installment" id="installmentContainer" <?php if ($paymentType == 1) echo 'style="display: none;"'; ?>>
                    <?php
                    // Fetch existing installment payment data for the student
                    $sql = "SELECT * FROM payment WHERE `ID` = ?";
                    if ($stmt = $con->prepare($sql)) {
                        $stmt->bind_param("s", $rollNo);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        $stmt->close();

                        // Display existing installment data
                        for ($i = 1; $i <= 4; $i++) {
                            $installmentSuffix = ["1st", "2nd", "3rd", "4th"][$i - 1];
                            $installmentAmount = $row["{$year}_{$installmentSuffix}_installment"];
                            $installmentPaymentMode = $row["{$year}_{$installmentSuffix}_installment_payment_method"];
                            $installmentTransactionId = $row["{$year}_{$installmentSuffix}_installment_transaction_id"];
                            $installmentTimestamp = $row["{$year}_{$installmentSuffix}_installment_timestamp"];
                    ?>
                            <div class="inputBox">
                                <label for="<?php echo $i; ?>_installment"><?php echo $i; ?> Installment</label>
                                <input type="number" name="<?php echo $i; ?>_installment" value="<?php echo htmlspecialchars($installmentAmount); ?>" placeholder="Enter <?php echo $i; ?> Installment">
                            </div>
                            <div class="inputBox">
                                <label for="<?php echo $i; ?>_installment_timestamp">Timestamp</label>
                                <input type="text" name="<?php echo $i; ?>_installment_timestamp" value="<?php echo htmlspecialchars($installmentTimestamp); ?>" disabled>
                            </div>
                            <div class="inputBox">
                                <label for="p-mode">Payment Mode</label>
                                <div class="enter">
                                    <div class="grid">
                                        <input type="radio" name="installment<?php echo $i; ?>_payment_mode" id="installment<?php echo $i; ?>_cash" value="0" <?php if ($installmentPaymentMode == 0) echo 'checked'; ?>>
                                        <label for="installment<?php echo $i; ?>_cash">Cash</label>
                                    </div>
                                    <div class="grid">
                                        <input type="radio" name="installment<?php echo $i; ?>_payment_mode" id="installment<?php echo $i; ?>_online" value="1" <?php if ($installmentPaymentMode == 1) echo 'checked'; ?>>
                                        <label for="installment<?php echo $i; ?>_online">Online</label>
                                    </div>
                                </div>
                            </div>
                            <div class="inputBox transactionId">
                                <label for="<?php echo $i; ?>_installment_transaction_id">Transaction ID</label>
                                <input type="text" name="<?php echo $i; ?>_installment_transaction_id" value="<?php echo htmlspecialchars($installmentTransactionId); ?>" placeholder="Required for Online Transaction">
                            </div>
                            <button type="submit" name="<?php echo $i; ?>_installment_btn">Update Payment</button>
                    <?php
                        }
                    }
                    ?>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleInstallmentContainer() {
            var oneTimePaymentContainer = document.getElementById('oneTimePaymentContainer');
            var installmentContainer = document.getElementById('installmentContainer');
            var oneTimePaymentRadio = document.getElementById('once');
            var installmentRadio = document.getElementById('installment');

            if (oneTimePaymentRadio.checked) {
                oneTimePaymentContainer.style.display = 'flex';
                installmentContainer.style.display = 'none';
            } else if (installmentRadio.checked) {
                oneTimePaymentContainer.style.display = 'none';
                installmentContainer.style.display = 'flex';
            }
        }

        function toggleTransactionIdInput(paymentType) {
            var transactionIdInput = document.getElementById(paymentType + 'TransactionId');
            var cashRadio = document.getElementById(paymentType + 'Cash');
            if (cashRadio.checked) {
                transactionIdInput.disabled = true;
            } else {
                transactionIdInput.disabled = false;
            }
        }

        // Initialize the form based on current state
        toggleInstallmentContainer();
        <?php for ($i = 1; $i <= 4; $i++) { ?>
            toggleTransactionIdInput(<?php echo $i; ?>, document.querySelector('input[name="installment<?php echo $i; ?>_payment_mode"]:checked')?.value == '1');
        <?php } ?>
    </script>
</body>

</html>





<!-- ####################### -->
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

include 'connection.php';
include 'navigation.php';

ob_start(); // Start output buffering

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['rollNo'] = $_POST['rollNo'];
    $_SESSION['year'] = $_POST['year'];
}

$rollNo = isset($_SESSION['rollNo']) ? $_SESSION['rollNo'] : '';
$year = isset($_SESSION['year']) ? $_SESSION['year'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["rollNo"]) && isset($_POST["year"])) {
    $rollNo = $_POST["rollNo"];
    $year = $_POST["year"];

    if ($year == 1) {
        $year = "1st_yr";
    } elseif ($year == 2) {
        $year = "2nd_yr";
    } elseif ($year == 3) {
        $year = "3rd_yr";
    }

    $sql = "SELECT * FROM payment WHERE `ID` = ?";
    if ($stmt = $con->prepare($sql)) {
        $stmt->bind_param("s", $rollNo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            $totalAmount = $row[$year];
            $paymentType = $row["{$year}_payment_type"];
            $oneTimePaymentMode = $row["{$year}_one_time_payment_method"];
            $oneTimePaymentTransactionId = $row["{$year}_one_time_payment_transaction_id"];
            $oneTimePaymentTimestamp = $row["{$year}_one_time_payment_timestamp"];
        } else {
            echo "No payment data found for the provided roll number and year.<br>";
            exit;
        }
    } else {
        echo "Error: Unable to fetch payment data.<br>";
        exit;
    }

    if (isset($_POST["totalAmount"]) && isset($_POST["payment-type"])) {
        $totalAmount = $_POST["totalAmount"];
        $paymentType = $_POST["payment-type"];

        // Update total payment and payment type
        $sql = "UPDATE payment SET `{$year}` = ?, `{$year}_payment_type` = ? WHERE `ID` = ?";
        if ($stmt = $con->prepare($sql)) {
            $stmt->bind_param("iis", $totalAmount, $paymentType, $rollNo);
            if ($stmt->execute()) {
                echo "Payment successful.<br>";
            } else {
                echo "Error updating payment information: " . $stmt->error . "<br>";
            }
            $stmt->close();
        } else {
            echo "Error: Unable to prepare SQL statement.<br>";
        }

        // Handle one-time payment submission
        if ($paymentType == 1 && isset($_POST["oneTimePaymentSubmit"])) {
            $oneTimePaymentMode = $_POST["oneTimePaymentMode"];
            $oneTimePaymentTransactionId = $_POST["oneTimePaymentTransactionId"];

            $oneTimePaymentInfo =  "{$year}_one_time_payment";
            $sql = "UPDATE payment SET 
                        `{$year}_one_time_payment` = ?, 
                        `{$year}_one_time_payment_method` = ?, 
                        `{$year}_one_time_payment_transaction_id` = ?, 
                        `{$year}_one_time_payment_timestamp` = NOW() 
                    WHERE `ID` = ?";
            if ($stmt = $con->prepare($sql)) {
                $stmt->bind_param("iiss", $totalAmount, $oneTimePaymentMode, $oneTimePaymentTransactionId, $rollNo);
                if ($stmt->execute()) {
                    echo "One-time payment information updated successfully.<br>";
                    // Insert one-time payment into payment history
                    $insertHistorySql = "INSERT INTO bill (roll_no, payment_type, payment_info, amount, payment_mode, transaction_id) VALUES (?, ?, ?, ?, ?, ?)";
                    if ($historyStmt = $con->prepare($insertHistorySql)) {
                        $historyStmt->bind_param("sisiss", $rollNo, $paymentType, $oneTimePaymentInfo, $totalAmount, $oneTimePaymentMode, $oneTimePaymentTransactionId);
                        $historyStmt->execute();
                        $historyStmt->close();
                    } else {
                        echo "Error: Unable to prepare SQL statement for payment history.<br>";
                    }
                } else {
                    echo "Error updating one-time payment information: " . $stmt->error . "<br>";
                }
                $stmt->close();
            } else {
                echo "Error: Unable to prepare SQL statement for one-time payment.<br>";
            }
        }

        // Process installment payments
        if ($paymentType == 0) { // installment payments
            for ($i = 1; $i <= 4; $i++) {
                if (isset($_POST["{$i}_installment_btn"])) {
                    $installmentAmount = $_POST["{$i}_installment"];
                    $installmentPaymentMode = $_POST["installment{$i}_payment_mode"];
                    $installmentTransactionId = $_POST["{$i}_installment_transaction_id"];
                    // $installmentTimestamp = date('Y-m-d H:i:s');

                    // Determine installment labels based on $i
                    $installmentSuffix = ["1st", "2nd", "3rd", "4th"][$i - 1];
                    $installmentField = "{$year}_{$installmentSuffix}_installment";
                    $installmentModeField = "{$year}_{$installmentSuffix}_installment_payment_method";
                    $installmentTransactionField = "{$year}_{$installmentSuffix}_installment_transaction_id";
                    $installmentTimestampField = "{$year}_{$installmentSuffix}_installment_timestamp";


                    // Update installment details including the timestamp
                    $sql = "UPDATE payment SET 
                                $installmentField = ?, 
                                $installmentModeField = ?, 
                                $installmentTransactionField = ?, 
                                $installmentTimestampField = NOW() 
                            WHERE `ID` = ?";
                    if ($stmt = $con->prepare($sql)) {
                        $stmt->bind_param("iiss", $installmentAmount, $installmentPaymentMode, $installmentTransactionId, $rollNo);
                        if ($stmt->execute()) {
                            echo "$installmentSuffix Installment information updated successfully.<br>";
                            // Insert installment into payment history
                            $insertHistorySql = "INSERT INTO bill (roll_no, payment_type, payment_info, amount, payment_mode, transaction_id) VALUES (?, ?, ?, ?, ?, ?)";
                            if ($historyStmt = $con->prepare($insertHistorySql)) {
                                $historyStmt->bind_param("sisiss", $rollNo, $paymentType, $installmentField, $installmentAmount, $installmentPaymentMode, $installmentTransactionId);
                                $historyStmt->execute();
                                $historyStmt->close();
                            } else {
                                echo "Error: Unable to prepare SQL statement for payment history.<br>";
                            }
                        } else {
                            echo "Error updating installment $installmentSuffix information: " . $stmt->error . "<br>";
                        }
                        $stmt->close();
                    } else {
                        echo "Error: Unable to prepare SQL statement for installment $installmentSuffix.<br>";
                    }
                }
            }
        }
    }
} else {
    echo "Error: Roll No. or Year not received!<br>";
    exit;
}

$messages = ob_get_clean(); // Get the buffer content and clean the buffer
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="payment-update.css">
</head>

<body>
    <div class="content">
        <div class="header">
            <h2>Payment Update</h2>
            <button type="button" onclick="window.location.href='bills.php'" id="go-to-bills">Go to Bills</button>
        </div>
        <div class="main-content">
            <div id="information-container">
                <h2>Student Information</h2>
                <p><strong>Roll No.:</strong> <?php echo htmlspecialchars($rollNo); ?></p>
                <p><strong>Year:</strong> <?php echo htmlspecialchars($year); ?></p>
            </div>

            <form id="paymentForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="rollNo" value="<?php echo htmlspecialchars($rollNo); ?>">
                <input type="hidden" name="year" value="<?php echo htmlspecialchars($year); ?>">
                <div class="container">
                <div class="form-group">
                    <label for="totalAmount">Total Amount:</label>
                    <input type="number" id="totalAmount" name="totalAmount" value="<?php echo isset($totalAmount) ? $totalAmount : ''; ?>" <?php echo $totalAmount ? 'disabled' : ''; ?> required>
                </div>
                <div class="form-group">
                    <label for="payment-type">Payment Type:</label>
                    <select id="payment-type" name="payment-type" <?php echo $totalAmount ? 'disabled' : ''; ?> required>
                        
                        <option value="1" <?php if (isset($paymentType) && $paymentType == 1) echo 'selected'; ?>>One-Time Payment</option>
                        <option value="0" <?php if (isset($paymentType) && $paymentType == 0) echo 'selected'; ?>>Installments</option>
                    </select>
                </div>
                </div>

                <div class="container" id="one-time-payment" style="display: none;">
                    <!-- <h3>One-Time Payment</h3> -->

                    <div class="form-group">
                        <label for="otp">Timestamp</label>
                        <input type="text" name="oneTimePaymentTimestamp" value="<?php echo htmlspecialchars($oneTimePaymentTimestamp); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="oneTimePaymentMode">Payment Mode:</label>
                        <select id="oneTimePaymentMode" name="oneTimePaymentMode" <?php echo isset($oneTimePaymentTimestamp) ? 'disabled' : ''; ?>>

                            <option value="0" <?php if (isset($oneTimePaymentMode) && $oneTimePaymentMode == '0') echo 'selected'; ?>>Cash</option>
                            <option value="1" <?php if (isset($oneTimePaymentMode) && $oneTimePaymentMode == '1') echo 'selected'; ?>>Online</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="oneTimePaymentTransactionId">Transaction ID:</label>
                        <input type="text" id="oneTimePaymentTransactionId" name="oneTimePaymentTransactionId" value="<?php echo isset($oneTimePaymentTransactionId) ? $oneTimePaymentTransactionId : ''; ?>" <?php echo isset($oneTimePaymentTimestamp) ? 'disabled' : ''; ?>>
                    </div>
                    <button type="submit" name="oneTimePaymentSubmit" <?php echo isset($oneTimePaymentTimestamp) ? 'disabled' : ''; ?>>Submit</button>
                </div>

                <div class="" id="installments" style="display: none;">
                    <!-- <h3>Installments</h3> -->
                    <?php for ($i = 1; $i <= 4; $i++) {
                        $installmentSuffix = ["1st", "2nd", "3rd", "4th"][$i - 1];
                            $installmentAmount = $row["{$year}_{$installmentSuffix}_installment"];
                            $installmentMode = $row["{$year}_{$installmentSuffix}_installment_payment_method"];
                            $installmentTransactionId = $row["{$year}_{$installmentSuffix}_installment_transaction_id"];
                            $installmentTimestamp = $row["{$year}_{$installmentSuffix}_installment_timestamp"];
                    ?>
                        <div class="container">
                            <div class="form-group">
                                <label for="<?php echo $i; ?>_installment"><?php echo $installmentSuffix; ?> Installment:</label>
                                <input type="number" id="<?php echo $i; ?>_installment" name="<?php echo $i; ?>_installment" value="<?php echo htmlspecialchars($installmentAmount); ?>" <?php echo $installmentAmount ? 'disabled' : ''; ?>>
                            </div>
                            <div class="form-group">
                                <label for="<?php echo $i; ?>_installment_timestamp">Timestamp</label>
                                <input type="text" name="<?php echo $i; ?>_installment_timestamp" value="<?php echo htmlspecialchars($installmentTimestamp); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label for="installment<?php echo $i; ?>_payment_mode">Payment Mode:</label>
                                <select id="installment<?php echo $i; ?>_payment_mode" name="installment<?php echo $i; ?>_payment_mode" <?php echo $installmentAmount ? 'disabled' : ''; ?>>

                                    <option value="0" <?php echo $installmentMode == '0' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="1" <?php echo $installmentMode == '1' ? 'selected' : ''; ?>>Online</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="<?php echo $i; ?>_installment_transaction_id">Transaction ID:</label>
                                <input type="text" id="<?php echo $i; ?>_installment_transaction_id" name="<?php echo $i; ?>_installment_transaction_id" value="<?php echo htmlspecialchars($installmentTransactionId); ?>" <?php echo $installmentAmount ? 'disabled' : ''; ?>>
                            </div>
                            <button type="submit" name="<?php echo $i; ?>_installment_btn" <?php echo $installmentAmount ? 'disabled' : ''; ?>>Submit</button>
                        </div>
                    <?php } ?>
                </div>
            </form>
            <div id="messages">
                <?php echo $messages; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var paymentTypeSelect = document.getElementById('payment-type');
            var oneTimePaymentDiv = document.getElementById('one-time-payment');
            var installmentsDiv = document.getElementById('installments');

            function togglePaymentType() {
                if (paymentTypeSelect.value == '1') {
                    oneTimePaymentDiv.style.display = 'flex';
                    installmentsDiv.style.display = 'none';
                } else if (paymentTypeSelect.value == '0') {
                    oneTimePaymentDiv.style.display = 'none';
                    installmentsDiv.style.display = 'block';
                } else {
                    oneTimePaymentDiv.style.display = 'none';
                    installmentsDiv.style.display = 'none';
                }
            }

            paymentTypeSelect.addEventListener('change', togglePaymentType);
            togglePaymentType(); // Initialize based on the current selection
        });
    </script>
</body>

</html>