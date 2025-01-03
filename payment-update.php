<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

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
            $oneTimePaymentTimestamp = $row["{$year}_one_time_payment_timestamp"];
            $oneTimePaymentMode = $row["{$year}_one_time_payment_method"];
            $oneTimePaymentTransactionId = $row["{$year}_one_time_payment_transaction_id"];
            $paidAmount = $row["{$year}_one_time_payment"] + $row["{$year}_1st_installment"] + $row["{$year}_2nd_installment"] + $row["{$year}_3rd_installment"] + $row["{$year}_4th_installment"];
            $remainingAmount = $totalAmount - $paidAmount;
        } else {
            echo "No payment data found for the provided roll number and year.<br>";
            exit;
        }
    } else {
        echo "Error: Unable to fetch payment data.<br>";
        exit;
    }

    $oneTimePaymentDescriptions = [];
    $installmentPaymentDescriptions = [];

    $sql = "SELECT 
            `{$year}_one_time_payment`, 
            `{$year}_one_time_payment_description`, 
            `{$year}_1st_installment_description`, 
            `{$year}_2nd_installment_description`, 
            `{$year}_3rd_installment_description`, 
            `{$year}_4th_installment_description` 
        FROM payment WHERE `ID` = ?";

    if ($stmt = $con->prepare($sql)) {
        $stmt->bind_param("s", $rollNo);
        $stmt->execute();
        $stmt->bind_result(
            $oneTimePayment,
            $oneTimePaymentDescription,
            $firstInstallmentDescription,
            $secondInstallmentDescription,
            $thirdInstallmentDescription,
            $fourthInstallmentDescription
        );
        $stmt->fetch();
        $stmt->close();

        // Decode JSON strings into arrays
        $oneTimePaymentDescriptions = json_decode($oneTimePaymentDescription, true) ?? [];
        $installmentPaymentDescriptions = [
            1 => json_decode($firstInstallmentDescription, true) ?? [],
            2 => json_decode($secondInstallmentDescription, true) ?? [],
            3 => json_decode($thirdInstallmentDescription, true) ?? [],
            4 => json_decode($fourthInstallmentDescription, true) ?? []
        ];
    }

    if (isset($_POST["annualFeeSubmit"])) {
        if (isset($_POST["totalAmount"]) && isset($_POST["payment-type"])) {
            $totalAmount = $_POST["totalAmount"];
            $paymentType = $_POST["payment-type"];

            // Update total payment and payment type
            $sql = "UPDATE payment SET `{$year}` = ?, `{$year}_payment_type` = ? WHERE `ID` = ?";
            if ($stmt = $con->prepare($sql)) {
                $stmt->bind_param("iis", $totalAmount, $paymentType, $rollNo);
                if ($stmt->execute()) {
                    echo "Update successful.<br>";
                } else {
                    echo "Error updating payment information: " . $stmt->error . "<br>";
                }
                $stmt->close();
            } else {
                echo "Error: Unable to prepare SQL statement.<br>";
            }
        }
    } else {
        // Handle one-time payment submission
        if ($paymentType == 1 && isset($_POST["oneTimePaymentSubmit"])) {
            $oneTimePaymentMode = $_POST["oneTimePaymentMode"];
            $oneTimePaymentTransactionId = $_POST["oneTimePaymentTransactionId"];


            // Collect only the checked payment descriptions
            $oneTimePaymentDescriptionArray = [];
            $paymentDescriptions = [
                'application-form-fee' => 'Application Form Fee',
                'admission-fee' => 'Admission Fee',
                'tuition-fee' => 'Tuition Fee',
                'readmission-fee' => 'Re-Admission Fee',
                'examination-fee' => 'Examination Fee',
                'library-fee' => 'Library Fee',
                'seminar&workshop-fee' => 'Seminar & Workshop Fee',
                'sports-fee' => 'Sports Fee',
                'youth-innovation&cultural-fee' => 'Youth Innovation & Cultural Fee',
                'nss-fee' => 'NSS Fee',
                'college-development-fee' => 'College Development Fee',
                'others-fee' => 'Others'
            ];
            foreach ($paymentDescriptions as $key => $description) {
                if (isset($_POST[$key])) {
                    $oneTimePaymentDescriptionArray[$description] = true;
                }
            }
            $oneTimePaymentDescription = json_encode($oneTimePaymentDescriptionArray);

            $oneTimePaymentInfo = "{$year}_one_time_payment";
            $sql = "UPDATE payment SET 
                        `{$year}_one_time_payment` = ?, 
                        `{$year}_one_time_payment_method` = ?, 
                        `{$year}_one_time_payment_transaction_id` = ?, 
                        `{$year}_one_time_payment_timestamp` = NOW(),
                        `{$year}_one_time_payment_description` = ?
                    WHERE `ID` = ?";
            if ($stmt = $con->prepare($sql)) {
                $stmt->bind_param("iisss", $totalAmount, $oneTimePaymentMode, $oneTimePaymentTransactionId, $oneTimePaymentDescription, $rollNo);
                if ($stmt->execute()) {
                    echo "One-time Payment updated successfully.<br>";
                    // Insert one-time payment into payment history
                    $insertHistorySql = "INSERT INTO bill (roll_no, payment_type, payment_info, amount, payment_mode, transaction_id, payment_description) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    if ($historyStmt = $con->prepare($insertHistorySql)) {
                        $historyStmt->bind_param("sisisss", $rollNo, $paymentType, $oneTimePaymentInfo, $totalAmount, $oneTimePaymentMode, $oneTimePaymentTransactionId, $oneTimePaymentDescription);
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
                if (isset($_POST["{$i}_installment_btn"]) && $_POST["{$i}_installment"] != 0) {
                    $installmentAmount = $_POST["{$i}_installment"];
                    $installmentPaymentMode = $_POST["installment{$i}_payment_mode"];
                    $installmentTransactionId = $_POST["{$i}_installment_transaction_id"];
                    // $installmentTimestamp = date('Y-m-d H:i:s');

                    // Collect only the checked payment descriptions
                    $installmentDescriptionArray = [];
                    $paymentDescriptions = [
                        "{$i}_application" => 'Application Form Fee',
                        "{$i}_admission" => 'Admission Fee',
                        "{$i}_tuition" => 'Tuition Fee',
                        "{$i}_readmission" => 'Re-Admission Fee',
                        "{$i}_examination" => 'Examination Fee',
                        "{$i}_library" => 'Library Fee',
                        "{$i}_seminar-workshop" => 'Seminar & Workshop Fee',
                        "{$i}_sports" => 'Sports Fee',
                        "{$i}_innovation-cultural" => 'Youth Innovation & Cultural Fee',
                        "{$i}_nss" => 'NSS Fee',
                        "{$i}_development" => 'College Development Fee',
                        "{$i}_others" => 'Others'
                    ];
                    foreach ($paymentDescriptions as $key => $description) {
                        if (isset($_POST[$key])) {
                            $installmentDescriptionArray[$description] = true;
                        }
                    }
                    $installmentDescription = json_encode($installmentDescriptionArray);

                    // Determine installment labels based on $i
                    $installmentSuffix = ["1st", "2nd", "3rd", "4th"][$i - 1];
                    $installmentField = "{$year}_{$installmentSuffix}_installment";
                    $installmentModeField = "{$year}_{$installmentSuffix}_installment_payment_method";
                    $installmentTransactionField = "{$year}_{$installmentSuffix}_installment_transaction_id";
                    $installmentTimestampField = "{$year}_{$installmentSuffix}_installment_timestamp";
                    $installmentDescriptionField = "{$year}_{$installmentSuffix}_installment_description";

                    // Update installment details including the timestamp
                    $sql = "UPDATE payment SET 
                                $installmentField = ?, 
                                $installmentModeField = ?, 
                                $installmentTransactionField = ?, 
                                $installmentTimestampField = NOW(),
                                $installmentDescriptionField = ?
                            WHERE `ID` = ?";
                    if ($stmt = $con->prepare($sql)) {
                        $stmt->bind_param("iisss", $installmentAmount, $installmentPaymentMode, $installmentTransactionId, $installmentDescription, $rollNo);
                        if ($stmt->execute()) {
                            echo "$installmentSuffix Installment updated successfully.<br>";
                            // Insert installment into payment history
                            $insertHistorySql = "INSERT INTO bill (roll_no, payment_type, payment_info, amount, payment_mode, transaction_id, payment_description) VALUES (?, ?, ?, ?, ?, ?, ?)";
                            if ($historyStmt = $con->prepare($insertHistorySql)) {
                                $historyStmt->bind_param("sisisss", $rollNo, $paymentType, $installmentField, $installmentAmount, $installmentPaymentMode, $installmentTransactionId, $installmentDescription);
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
    <title>Payment Update</title>
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
                <div class="container" id="annual-fee">
                    <!-- <div class="inputBox">
                        <label for="year">Current Year</label>
                        <input type="text" value="<?php echo htmlspecialchars($year); ?>" readonly>
                    </div> -->
                    <div class="inputBox">
                        <label for="totalAmount">Total Annual Fee</label>
                        <input type="number" name="totalAmount" value="<?php echo htmlspecialchars($totalAmount); ?>" <?php echo isset($totalAmount) ? $totalAmount : ''; ?> placeholder="Enter Total Amount" required inputmode="numeric">
                    </div>
                    <div class="inputBox">
                        <label for="paidAmount">Paid Amount</label>
                        <input type="number" name="paidAmount" value="<?php echo $paidAmount; ?>" disabled inputmode="numeric">
                    </div>
                    <div class="inputBox">
                        <label for="remainingAmount">Remaining Amount</label>
                        <input type="number" name="remainingAmount" value="<?php echo $remainingAmount; ?>" disabled inputmode="numeric">
                    </div>
                    <div class="payment-type">
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
                    <button type="submit" name="annualFeeSubmit" id="annual-fee-btn" <?php echo ($_SESSION["type"] == 2 && $totalAmount) ? 'disabled' : ''; ?>>Submit</button>
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
                    <button type="submit" class="update-btn" name="oneTimePaymentSubmit" <?php echo ($oneTimePaymentTimestamp ? 'disabled' : '') . (!$totalAmount ? 'disabled' : ''); ?>>Update Payment</button>

                    <?php
                    $paymentDescriptions = [
                        'application-form-fee' => 'Application Form Fee',
                        'admission-fee' => 'Admission Fee',
                        'tuition-fee' => 'Tuition Fee',
                        'readmission-fee' => 'Re-Admission Fee',
                        'examination-fee' => 'Examination Fee',
                        'library-fee' => 'Library Fee',
                        'seminar&workshop-fee' => 'Seminar & Workshop Fee',
                        'sports-fee' => 'Sports Fee',
                        'youth-innovation&cultural-fee' => 'Youth Innovation & Cultural Fee',
                        'nss-fee' => 'NSS Fee',
                        'college-development-fee' => 'College Development Fee',
                        'others-fee' => 'Others'
                    ];
                    foreach ($paymentDescriptions as $key => $description) {
                        $checked = isset($oneTimePaymentDescriptions[$description]) ? 'checked' : '';
                        echo "<div class='input-checkbox'><label><input type='checkbox' name='{$key}' {$checked}> {$description}</label></div>";
                    }
                    ?>
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

                            <div class="container installment">
                                <div class="inputBox">
                                    <label for="<?php echo $i; ?>_installment"><?php echo $installmentSuffix; ?> Installment</label>
                                    <input type="number" name="<?php echo $i; ?>_installment" value="<?php echo htmlspecialchars($installmentAmount); ?>" placeholder="Enter <?php echo $i; ?> Installment">
                                </div>
                                <div class="inputBox">
                                    <label for="<?php echo $i; ?>_installment_timestamp">Timestamp</label>
                                    <input type="text" name="<?php echo $i; ?>_installment_timestamp" value="<?php echo htmlspecialchars($installmentTimestamp); ?>" readonly>
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
                                <button type="submit" class="update-btn" name="<?php echo $i; ?>_installment_btn" <?php echo ($installmentAmount ? 'disabled' : '') . (!$totalAmount ? 'disabled' : ''); ?>>Update</button>


                                <?php
                                $installmentDescriptions = [
                                    "{$i}_application" => 'Application Form Fee',
                                    "{$i}_admission" => 'Admission Fee',
                                    "{$i}_tuition" => 'Tuition Fee',
                                    "{$i}_readmission" => 'Re-Admission Fee',
                                    "{$i}_examination" => 'Examination Fee',
                                    "{$i}_library" => 'Library Fee',
                                    "{$i}_seminar-workshop" => 'Seminar & Workshop Fee',
                                    "{$i}_sports" => 'Sports Fee',
                                    "{$i}_innovation-cultural" => 'Youth Innovation & Cultural Fee',
                                    "{$i}_nss" => 'NSS Fee',
                                    "{$i}_development" => 'College Development Fee',
                                    "{$i}_others" => 'Others'
                                ];
                                foreach ($installmentDescriptions as $key => $description) {
                                    $checked = isset($installmentPaymentDescriptions[$i][$description]) ? 'checked' : '';
                                    echo "<div class='input-checkbox'><label><input type='checkbox' name='{$key}' {$checked}> {$description}</label></div>";
                                }
                                ?>
                            </div>
                    <?php
                        }
                    }
                    ?>
                </div>
            </form>
        </div>
    </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
    var annualFeeBtn = document.getElementById('annual-fee-btn');
    var annualFeeContainer = document.getElementById('annual-fee');

    if (annualFeeBtn.disabled) {
        annualFeeContainer.style.display = 'none';
    }
});
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