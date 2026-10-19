<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

// Define getRank function (same as in accept.php)
function getRank($conn, $player) {
    $query = 'SELECT `rank` FROM ranks WHERE player = ?';
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $query)) {
        error_log("Prepare failed for getRank: " . mysqli_error($conn));
        exit;
    }
    mysqli_stmt_bind_param($stmt, 's', $player);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($row) {
        return $row['rank'];
    }
    // Return 0 for unranked users instead of assigning a new rank
    return 0;
}
// Check for remember me cookie
if (autoLogin()) {
    $success = 1;
}

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
        
        // Handle remember me
        if (isset($_POST['remember']) && $_POST['remember'] == 'on') {
            $token = createRememberMeToken($conn, $username);
            setcookie('remember_me', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        }

        // prevent repost with back button
        header('Location: login.php');
        exit();
    }
} else if (isset($_SESSION['useruid'])) {
    $username = $_SESSION['useruid'];
    $success = 1;
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
      
        <div class="namesize">

   <br />
    <br /><?php echo htmlspecialchars(getUserId()); ?>
        <p>Login Successful!</p>
        
           
        </div>
        <?php
    } else {
        ?>
        <form method="POST">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Username">
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" placeholder="Password">
            <div class="remember-me">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">Keep me logged in</label>
            </div>
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
        
         <p><a href="reset_password_request.php"></a></p>
        
        <?php
    
    }

    include 'Includes/footer.inc.php';
?>