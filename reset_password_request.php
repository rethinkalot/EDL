<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

// Check if form submitted
if (isset($_POST["email"])) {
    $email = $_POST["email"];
    
    $query = 'UPDATE users SET resetToken=?, resetTime=NOW() WHERE usersEmail=?';
    
    $token = md5('reset'.uniqid().rand(1000000, 9999999));
    
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $token, $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $count = mysqli_affected_rows($conn);

    if ($count) {
        $subject = "Password reset for Elite Descent Ladder";
        $msg = "You have requested a pasword reset on elitedescentladder.ddns.net.\n\n";
        $msg .= "Follow this link to reset your password:\n\n";
        $msg .= "https://elitedescentladder.ddns.net/reset_password.php?token=$token";
        
        $headers = array('From' => 'noreply@elitedescentladder.ddns.net');
        
        mail($email, $subject, $msg, $headers);
    }
    
    $success = 1;    
} else {
    $email = '';
}

$title = 'Reset Password';
$public = true;
include 'Includes/header.inc.php';
?>

<div class="container">
    <?php
    if (isset($success)) {
        ?>
        <p>Reset link sent.</p>
        <?php
    } else {
        ?>
        <form method="POST">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Email">
            <input type="submit" name="submit" value="Reset Password">

        </form>
        <?php
        // Display error message if set
        if (isset($error_message)) {
            echo '<p class="error-message">' . $error_message . '</p>';
        }
    }

    include 'Includes/footer.inc.php';
?>

