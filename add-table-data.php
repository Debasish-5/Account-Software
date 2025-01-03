<?php
include 'connection-student-database.php';
include 'connection.php'; // Include connection for the account database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tableName = $_POST['table_name'];

    // Fetch students whose data are not in the payment table
    $sql = "SELECT * FROM $tableName WHERE `COL 2` NOT IN (SELECT ID FROM `account`.`payment`)";
    $result = mysqli_query($std_con, $sql);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td><input type='hidden' name='roll_numbers[]' value='" . $row['COL 2'] . "'>" . $row['COL 2'] . "</td>";
            echo "<td>" . $row['COL 3'] . "</td>";
            echo "<td>" . $row['COL 6'] . "</td>";
            echo "<td>" . $row['COL 5'] . "</td>";
            echo "<td><input type='radio' name='student_type[" . $row['COL 2'] . "]' value='Hosteller'></td>";
            echo "<td><input type='radio' name='student_type[" . $row['COL 2'] . "]' value='Day scholar'></td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='6'>No data found</td></tr>";
    }
}
?>
