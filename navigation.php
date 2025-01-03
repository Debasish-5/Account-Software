<?php

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);

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
    <!-- <title>Accounts Information System</title> -->
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="side-bar">
        <div class="logo-details">
            <img src="logo.jpg" alt="">
            <span class="logo_name">Accounts Software</span>
        </div>
        <ul class="navlinks">
            <li class="<?php echo ($currentPage === 'home.php') ? 'active' : ''; ?>">
                <a href="home.php">
                    <i class='bx bx-grid-alt'></i>
                    <span class="link-name">Dashboard</span>
                </a>
            </li>

            <li class="dropdown <?php echo ($currentPage === 'batch2022.php' || $currentPage === 'batch2023.php' || $currentPage === 'batch2024.php' || $currentPage === 'payment-update.php') ? 'active' : ''; ?>">
                <a class="dropbtn"><i class='bx bxs-graduation'></i>Batch<i class='bx bx-caret-down'></i></a>
                <div class="dropdown-content">
                    <a href="batch2022.php">All Batch 2022</a>
                    <a href="batch2023.php">All Batch 2023</a>
                    <a href="batch2024.php" id="bottom-batch">All Batch 2024</a>
                </div>
            </li>

            <li class="<?php echo ($currentPage === 'bills.php') ? 'active' : ''; ?>">
                <a href="bills.php">
                    <i class='bx bx-money-withdraw'></i>
                    <span class="link-name">Bills</span>
                </a>
            </li>

            <li class="<?php echo ($currentPage === 'fines.php') ? 'active' : ''; ?>">
                <a href="fines.php">
                    <i class="fa-solid fa-gavel"></i>
                    <span class="link-name">Fines</span>
                </a>
            </li>

            <li class="<?php echo ($currentPage === 'examination-fee.php') ? 'active' : ''; ?>">
                <a href="examination-fee.php">
                    <i class="fa-regular fa-pen-to-square"></i>
                    <span class="link-name">Examination Fee</span>
                </a>
            </li>

            <li class="<?php echo ($currentPage === 'add-students.php') ? 'active' : ''; ?>">
                <a href="add-students.php">
                    <i class="fa-solid fa-user-plus"></i>
                    <span class="link-name">Add Students</span>
                </a>
            </li>

            <div class="user-info">
                <div class="profile"></div>
                <div class="user-name">
                    <div class="name">
                        <?php if (isset($_SESSION["username"])) : ?>
                            <h3><?php echo $_SESSION["username"]; ?></h3>
                        <?php endif; ?>
                    </div>
                    <div class="type">
                        <?php if (isset($_SESSION["type"])) : ?>
                            <?php if ($_SESSION["type"] == 1) : ?>
                                <h5><?php echo "Admin"; ?></h5>
                            <?php elseif ($_SESSION["type"] == 2) : ?>
                                <h5><?php echo "Accountant"; ?></h5>
                            <?php else : ?>
                                <h5>Unknown Type: <?php echo $_SESSION["type"]; ?></h5> <!-- Add debug output -->
                            <?php endif; ?>
                        <?php else : ?>
                            <h5>Type Not Set</h5> <!-- Add debug output -->
                        <?php endif; ?>
                    </div>
                </div>
            </div>


            <a href="logout.php"><button class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i>Logout</button></a>

        </ul>
    </div>

</body>

</html>