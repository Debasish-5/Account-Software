<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

include 'navigation.php';
include 'connection.php'; // Database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['fees'])) {
    foreach ($_POST['fees'] as $id => $semesters) {
        $updates = [];
        foreach ($semesters as $sem => $value) {
            $updates[] = "$sem = 'Paid'";
        }
        $updateString = implode(', ', $updates);
        $query = "UPDATE payment SET $updateString WHERE id = '$id'";
        mysqli_query($con, $query);
    }

    header("location: examination-fee.php");
    exit;
}

$query = "SELECT * FROM payment";
$result = mysqli_query($con, $query);
$students = mysqli_fetch_all($result, MYSQLI_ASSOC);
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
    <title>Examination Fee</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="bills.css">
</head>
<body>
    <div class="content">
        <div class="header">
            <h2>Examination Fee</h2>
        </div>
        <div class="main-content">
        <!-- <div class="total-payment-details">
                <div class="details-box">
                    <h4>Total Students : <span id="total-students-count"></span></h4>
                </div>
                <div class="details-box">
                    <select id="batch-filter" onchange="filterResults()">
                        <option value="">Select Batch</option>
                        <option value="BBA">BBA</option>
                        <option value="BCA">BCA</option>
                        <option value="BSC">B.Sc CS(H)</option>
                    </select>
                </div>
                <div class="details-box">
                    <select id="year-filter" onchange="filterResults()">
                        <option value="">Select Year</option>
                        <option value="22">2022</option>
                        <option value="23">2023</option>
                        <option value="24">2024</option>
                    </select>
                </div>
                
            </div> -->
            <div class="total-students filter-section">
                <div class="search-bar">
                    <input type="text" name="" id="search" placeholder="Search by Roll No.  or  Batch">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>
            <div class="data-section" style="padding-bottom: 70px;">
                <form id="fee-form" method="post" action="examination-fee.php">
                    <table>
                        <thead>
                            <tr>
                                <th>Roll No.</th>
                                <th>1st Sem</th>
                                <th>2nd Sem</th>
                                <th>3rd Sem</th>
                                <th>4th Sem</th>
                                <th>5th Sem</th>
                                <th>6th Sem</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo $student['ID']; ?></td>
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <td>
                                            <input type="checkbox" id="examination-fee-checkbox" name="fees[<?php echo $student['ID']; ?>][semester_<?php echo $i; ?>_examination_fee]" <?php echo $student["semester_{$i}_examination_fee"] === 'Paid' ? 'checked' : ''; ?>>
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" id="fine-btn" style="position: absolute; right: 10px; bottom: 0">Submit</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById('search');
            searchInput.addEventListener('input', function() {
                filterTableRows(this.value.trim().toLowerCase());
            });
        });

        function filterTableRows(rollNumber) {
            const tableRows = document.querySelectorAll('#table-body tr');
            tableRows.forEach(row => {
                const rollNoCell = row.querySelector('td:first-child');
                const rollNo = rollNoCell.textContent.trim().toLowerCase();
                if (rollNo.includes(rollNumber)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
