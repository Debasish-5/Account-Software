<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

include 'connection.php';
include 'navigation.php';

// Initialize variables
$from_date = isset($_POST['from_date']) ? $_POST['from_date'] : '';
$to_date = isset($_POST['to_date']) ? $_POST['to_date'] : '';

// Prepare SQL query with date filtering
$sql = "SELECT * FROM bill";
$conditions = [];

if (!empty($from_date)) {
    $conditions[] = "payment_timestamp >= ?";
}

if (!empty($to_date)) {
    // Adjust the condition for to_date
    $conditions[] = "payment_timestamp < DATE_ADD(?, INTERVAL 1 DAY)";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY invoice_no DESC";

// Prepare the statement
$stmt = $con->prepare($sql);

// Bind parameters
$param_types = "";
$params = [];

if (!empty($from_date)) {
    $param_types .= "s";
    $params[] = $from_date;
}

if (!empty($to_date)) {
    $param_types .= "s";
    $params[] = $to_date;
}

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

// Execute the query and fetch results
$stmt->execute();
$result = $stmt->get_result();
$bills = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bills[] = $row;
    }
} else {
    echo "No records found.";
}

$stmt->close();
$con->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <title>Bills</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="bills.css">
</head>

<body>
    <div class="content">
        <div class="header">
            <h2>Bills</h2>
        </div>
        <div class="main-content">
            <div class="total-payment-details">
                <div class="details-box">
                    <h4>Total Invoices : <span id="total-invoice-count"></span></h4>
                </div>
                <div class="details-box">
                    <h4>Total Received Amount : <span id="total-received-amount"></span></h4>
                </div>
                <div class="details-box">
                    <h4>By Cash : <span id="by-cash"></span></h4>
                </div>
                <div class="details-box">
                    <h4>By Online : <span id="by-online"></span></h4>
                </div>
            </div>
            <div class="total-students filter-section">
                <div class="search-bar">
                    <input type="text" name="" id="search" placeholder="Search by Roll No.  or  Batch">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <form action="" method="POST">
                    <div class="date-filter year">
                        <div class="date-input">
                            <label for="from-date">From Date :</label>
                            <input type="date" name="from_date" id="from-date" value="<?php echo isset($_POST['from_date']) ? $_POST['from_date'] : ''; ?>">
                            <label for="to-date">To Date :</label>
                            <input type="date" name="to_date" id="to-date" value="<?php echo isset($_POST['to_date']) ? $_POST['to_date'] : ''; ?>">
                        </div>
                        <button type="submit" class="apply-btn">Apply</button>
                    </div>
                </form>
            </div>
            <div class="data-section">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice No.</th>
                            <th>Roll No.</th>
                            <th>Payment Info</th>
                            <th>Amount</th>
                            <th>Transaction Mode</th>
                            <th>Transaction ID</th>
                            <th>Transaction Time</th>
                            <th class="last-column">Billing</th>
                        </tr>
                    </thead>
                    <tbody id="table-body">
                        <?php if (!empty($bills)) : ?>
                            <?php foreach ($bills as $bill) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($bill['invoice_no']); ?></td>
                                    <td><?php echo htmlspecialchars($bill['roll_no']); ?></td>
                                    <td><?php echo htmlspecialchars($bill['payment_info']); ?></td>
                                    <td><?php echo htmlspecialchars($bill['amount']); ?></td>
                                    <td><?php echo htmlspecialchars($bill['payment_mode'] == 0 ? 'Cash' : 'Online'); ?></td>
                                    <td><?php echo htmlspecialchars($bill['transaction_ID']); ?></td>
                                    <td><?php echo htmlspecialchars(date("d-m-Y H:i:s", strtotime($bill['payment_timestamp']))); ?></td>
                                    <td>
                                        <form action="payment/invoice.php" method="POST">
                                            <input type="hidden" name="invoice_no" value="<?php echo htmlspecialchars($bill['invoice_no']); ?>">
                                            <input type="hidden" name="roll_no" value="<?php echo htmlspecialchars($bill['roll_no']); ?>">
                                            <input type="hidden" name="payment_info" value="<?php echo htmlspecialchars($bill['payment_info']); ?>">
                                            <input type="hidden" name="amount" value="<?php echo htmlspecialchars($bill['amount']); ?>">
                                            <input type="hidden" name="payment_mode" value="<?php echo htmlspecialchars($bill['payment_mode']); ?>">
                                            <input type="hidden" name="transaction_ID" value="<?php echo htmlspecialchars($bill['transaction_ID']); ?>">
                                            <input type="hidden" name="payment_timestamp" value="<?php echo htmlspecialchars($bill['payment_timestamp']); ?>">
                                            <input type="hidden" name="payment_description" value="<?php echo htmlspecialchars($bill['payment_description']); ?>">
                                            <button type="submit" class="update-btn last-column">Generate Bill</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="8">No records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="bills.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const searchInput = document.getElementById('search');
            searchInput.addEventListener('input', function() {
                filterTableRows(this.value.trim().toLowerCase());
                calculateTotals(); // Recalculate totals after filtering
            });

            calculateTotals(); // Initially calculate totals
        });

        // search by roll number
        function filterTableRows(rollNumber) {
            const tableRows = document.querySelectorAll('#table-body tr');
            tableRows.forEach(row => {
                const rollNoCell = row.querySelector('td:nth-child(2)');
                const rollNo = rollNoCell.textContent.trim().toLowerCase();
                if (rollNo.includes(rollNumber)) {
                    row.style.display = ''; // Show row if roll number matches search query
                } else {
                    row.style.display = 'none'; // Hide row if roll number does not match search query
                }
            });
        }

        // format amount in Indian Pricing
        function formatNumberIndian(num) {
            const x = num.toString();
            const lastThree = x.substring(x.length - 3);
            const otherNumbers = x.substring(0, x.length - 3);
            if (otherNumbers !== '') {
                return otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ",") + ',' + lastThree;
            }
            return lastThree;
        }
        
        // Display total received information in the Heading
        function calculateTotals() {
            const visibleRows = document.querySelectorAll('#table-body tr:not([style*="display: none"])'); // Get all visible rows
            let totalInvoices = 0;
            let totalAmount = 0;
            let totalByCash = 0;
            let totalByOnline = 0;

            visibleRows.forEach(row => {
                const invoiceNoCell = row.querySelector('td:nth-child(1)');
                const invoiceNo = invoiceNoCell.textContent.trim();
                totalInvoices++;

                const amountCell = row.querySelector('td:nth-child(4)');
                const amount = parseFloat(amountCell.textContent.trim());
                totalAmount += amount;

                const paymentModeCell = row.querySelector('td:nth-child(5)');
                const paymentMode = paymentModeCell.textContent.trim();
                if (paymentMode === 'Cash') {
                    totalByCash += amount;
                } else {
                    totalByOnline += amount;
                }
            });

            document.getElementById('total-invoice-count').textContent = totalInvoices;
            document.getElementById('total-received-amount').textContent = formatNumberIndian(totalAmount); // Display total amount with 2 decimal places
            document.getElementById('by-cash').textContent = formatNumberIndian(totalByCash);
            document.getElementById('by-online').textContent = formatNumberIndian(totalByOnline);
        }
    </script>

</body>

</html>
<!-- /////////////////////////////////////////////////////////////////////////// -->