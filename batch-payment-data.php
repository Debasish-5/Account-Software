<?php
include 'connection.php';

$sql = "SELECT * FROM payment";
$result = $con->query($sql);

$data = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

$con->close();

header('Content-Type: application/json');
echo json_encode($data);
?>