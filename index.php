<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
</head>

<body>
    
<?php
session_start();
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: home.php");
    exit;
}

include 'connection.php';

if (isset($_POST['submit'])) {
    $id = $_POST['id'];
    $password = $_POST['password'];

    $id_search = "SELECT * FROM adminlogin WHERE id='$id'"; // Assuming 'Username' is the correct column name
    $query = mysqli_query($con, $id_search);

    if ($query) {
        $id_count = mysqli_num_rows($query);

        if ($id_count > 0) {
            $id_data = mysqli_fetch_assoc($query);

            $db_pass = $id_data['password']; // Adjust column name if necessary
            $Type_no = $id_data['type'];

            if ($password === $db_pass) {
                // Passwords match
                $pass_decode = true;
            } else {
                // Passwords don't match
                $pass_decode = false;
            }

            if ($pass_decode) {
                $_SESSION["loggedin"] = true;
                $_SESSION["username"] = $id_data['personName'];
                $_SESSION["type"] = $id_data['type'];

                
                if ($Type_no == 1) {
                    header("Location: home.php");
                    exit;
                } elseif ($Type_no == 2) {
                    header("Location: home.php");
                    exit;
                } else {
                    echo "Invalid Type_no";
                }
            } else {
                echo "Password Incorrect";
            }
        } else {
            echo "Invalid Username";
        }
    } else {
        echo "Query execution failed: " . mysqli_error($con);
    }
}

?>
    <section>
        <div class="login">
            <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="POST">
                <h2>Login</h2>
                <div class="input">
                    <span class="icon"><ion-icon name="mail"></ion-icon></span>
                    <input type="text" name="id" required autocomplete="off">
                    <label>ID</label>
                </div>
                <div class="input">
                    <span class="icon"><ion-icon name="lock-closed"></ion-icon></span>
                    <input type="password" name="password" required>
                    <label>Password</label>
                </div>
                <button type="submit" name="submit">Login</button>
            </form>
        </div>
    </section>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>

</html>
