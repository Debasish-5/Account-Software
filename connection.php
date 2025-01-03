<?php

$username = "root";
$password = "";
$server = 'localhost';
$db = 'account';

$con = mysqli_connect($server,$username,$password,$db);

if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

?>