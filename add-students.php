<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

include 'navigation.php';
include 'connection.php'; // Database connection file for account database
include 'connection-student-database.php'; // Database connection file for student information database

// Fetch table names from the student information database
$tables = [];
$result = mysqli_query($std_con, "SHOW TABLES");
while ($row = mysqli_fetch_array($result)) {
    $tables[] = $row[0];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['roll_numbers']) && isset($_POST['table_name']) && isset($_POST['student_type'])) {
        $rollNumbers = $_POST['roll_numbers'];
        $tableName = $_POST['table_name'];
        $studentTypes = $_POST['student_type'];

        foreach ($rollNumbers as $rollNo) {
            if (isset($studentTypes[$rollNo])) {
                $studentType = $studentTypes[$rollNo];

                // Insert roll number into the account database
                $sql = "INSERT INTO payment (ID, student_type) VALUES ('$rollNo', '$studentType')";
                mysqli_query($con, $sql);

                // Update ACCOUNT_DATABASE column in the student information database
                $updateSql = "UPDATE $tableName SET ACCOUNT_DATABASE = 'Added' WHERE `COL 2` = '$rollNo'";
                mysqli_query($std_con, $updateSql);
            }
        }
    }
}
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
    <title>Add Students Into Account Database</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="bills.css">
</head>

<body>
    <div class="content">
        <div class="header">
            <h2>Add Students Into Account Database</h2>
        </div>
        <div class="main-content">
            <div class="total-students filter-section">
                <div class="search-bar">
                    <input type="text" name="" id="search" placeholder="Search by Roll No. or Batch">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <div class="table-selector">
                    <select id="table-select" class="table-filter" onchange="loadTableData(this.value)">
                        <option value="">Select Batch Name</option>
                        <?php foreach ($tables as $table) : ?>
                            <option value="<?php echo $table; ?>"><?php echo $table; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="data-section">
                <form id="fee-form" method="post">
                    <div class="total-students filter-section" id="table-top">
                        <button type="submit" id="fine-btn">Add Students</button>
                    </div>
                    <input type="hidden" name="table_name" id="table_name_input">
                    <table>
                        <thead>
                            <tr>
                                <th>Roll No.</th>
                                <th>Name</th>
                                <th>Batch</th>
                                <th>Semester</th>
                                <th>Hosteller</th>
                                <th>Day Scholar</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                        </tbody>
                    </table>
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

        function loadTableData(tableName) {
            if (!tableName) return;

            document.getElementById('table_name_input').value = tableName;

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'add-table-data.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                    document.getElementById('table-body').innerHTML = this.responseText;
                }
            };
            xhr.send('table_name=' + tableName);
        }
    </script>
</body>

</html>
