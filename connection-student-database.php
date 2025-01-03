<?php

$username = "root";
$password = "";
$server = 'localhost';
$db = 'student_database2';

$std_con = mysqli_connect($server,$username,$password,$db);

if ($std_con->connect_error) {
    die("Connection failed: " . $std_con->connect_error);
}

?>