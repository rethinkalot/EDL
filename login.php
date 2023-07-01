<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

// Check if login form submitted
if (isset($_POST["submit"])) {
    // Check if username and password are provided
    if (!empty($_POST["username"]) && !empty($_POST["password"])) {
        $_SESSION["useruid"] = 1;
    } else {
        $error_message = "Please provide both username and password!";
    }
}
if (isset($_POST["username"])) {
    $username = $_POST["username"];
    $pwd = $_POST["password"];

    if (!checkUser($conn, $username, $pwd)) {
        $error_message = "Invalid login!";
    } else {
        getRank($conn, $username);
        $success = 1;
        $_SESSION["useruid"] = $username;
    }
} else {
    $username = "";
}

$title = 'Login';
$public = true;
include 'Includes/header.inc.php';
?>

<div class="container">
    <?php
    if (isset($success)) {
        ?>
        <p>Login Successful!</p>
        <div class="middle-name">
            <?php echo htmlspecialchars($_SESSION["useruid"]); ?>
        </div>
        <?php
    } else {
        ?>
        <form method="POST">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Username">
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" placeholder="Password">
            <input type="submit" name="submit" value="Login">

        </form>
        

        <?php
        // Display error message if set
        if (isset($error_message)) {
            echo '<p class="error-message">' . $error_message . '</p>';
        }
	?>
        
        <p><a href="signup.php">Sign Up</a></p>
        
        <p><br></p>
        
        <p><a href="reset_password_request.php">Reset Password</a></p>
        
        <?php
    
    }

    include 'Includes/footer.inc.php';
?>

