<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

include 'navigation.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="content">
        <div class="header">
            <h2>Dashboard</h2>
        </div>
        <div class="main-content">
            <div class="total-students">
                <div class="box same">
                    <div class="logo"><i class="fa-solid fa-user-graduate"></i></i></div>
                    <div class="details">
                        <h3>420</h3>
                        <h5>Total Students</h5>
                    </div>
                </div>
                <div class="box same">
                    <div class="logo"><i class="fa-solid fa-users-viewfinder"></i></div>
                    <div class="details">
                        <h3>130</h3>
                        <h5>Students in 2022</h5>
                    </div>
                </div>
                <div class="box same">
                    <div class="logo"><i class="fa-solid fa-users-viewfinder"></i></div>
                    <div class="details">
                        <h3>150</h3>
                        <h5>Students in 2023</h5>
                    </div>
                </div>
                <div class="box same">
                    <div class="logo"><i class="fa-solid fa-users-slash"></i></div>
                    <div class="details">
                        <h3>0</h3>
                        <h5>Students in 2024</h5>
                    </div>
                </div>
            </div>
            <div class="bar-graph">
                <div class="heading">
                    <h3>Comparision Chart</h3>
                </div>
                <div id="columnchart_material" style="height: 350px"></div>
            </div>
            <div class="division">
                <div class="statistics bar-graph">
                    <div class="heading">
                        <h3>Statistics</h3>
                    </div>
                    <div id="donutchart" style="height: 350px;"></div>
                </div>
                <div class="batch bar-graph">
                    <div class="heading">
                        <h3>Batch Analysis</h3>
                    </div>
                    <div id="barchart_material" style="width: 100%; height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="bar-graph.js"></script>
    <script src="donut-chart.js"></script>
    <script src="area-chart.js"></script>
</body>

</html>
