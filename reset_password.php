<?php
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

$token = isset($_POST['token']) ? $_POST['token'] :
    (isset($_GET['token']) ? $_GET['token'] : '');

$public = true;
$title = 'SignUp';
include 'Includes/header.inc.php'
?>
    <div class="container">
        <?php 
        $error_message = "";
        if (!$token) {
            $error_message = "Missing token";
        } else {
            $query = 'SELECT usersUid FROM users WHERE resetToken=? AND NOW() < DATE_ADD(resetTime, INTERVAL 1 DAY)';

            $stmt = mysqli_stmt_init($conn);
            mysqli_stmt_prepare($stmt, $query);
            mysqli_stmt_bind_param($stmt, 's', $token);
            mysqli_stmt_execute($stmt);
            $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            if (!$user)
                $error_message = "Invalid token";
        }
        // Check if signup form submitted
        if (isset($user) && isset($_POST["pwd"])) {
            $pwd = $_POST["pwd"];
            $pwdrepeat = $_POST["pwdrepeat"];

            if (pwdMatch($pwd, $pwdrepeat)) {
                $error_message = "Passwords don't match!";
            } else {
                $query = 'UPDATE users SET usersPwd=?, resetToken=NULL, resetTime=NULL WHERE usersUid=?';

                $hashedPwd = password_hash($pwd, PASSWORD_DEFAULT);

                $stmt = mysqli_stmt_init($conn);
                mysqli_stmt_prepare($stmt, $query);
                mysqli_stmt_bind_param($stmt, 'ss', $hashedPwd, $user['usersUid']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                $success = 1;
            }
        }
        ?>

        <?php
        if (isset($success)) {
            ?>
            <p>Your password is changed.</p>
            <p><a href="login.php">Login</a></p>
            <?php
        } else if (isset($user)) {
           ?>
           
           <form method="POST">
               <label for="pwd">Password:</label>
               <input type="password" name="pwd" id="pwd" placeholder="Password">

               <label for="pwdrepeat">Repeat Password:</label>
               <input type="password" name="pwdrepeat" id="pwdrepeat" placeholder="Repeat Password">

               <input type="submit" name="submit" value="Change password">
           </form>

           <?php
       }

       // Display error message if set
       if (isset($error_message)) {
           echo '<p class="error-message">' . $error_message . '</p>';
       }
       ?>
    </div>
</body>
</html>
