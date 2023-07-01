<?php
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';
$public = true;
$title = 'SignUp';
include 'Includes/header.inc.php'
?>
    <div class="container">
        <?php 
        // Check if signup form submitted
        if (isset($_POST["name"])) {
            $name = $_POST["name"];
            $email = $_POST["email"];
            $username = $_POST["uid"];
            $pwd = $_POST["pwd"];
            $pwdrepeat = $_POST["pwdrepeat"];

            if (emptyInputSignup($name, $email, $username, $pwd, $pwdrepeat)) {
               $error_message = "Please fill in all fields!";
            } else if (invalidUid($username)) {
               $error_message = "Invalid characters in name!";
            } else if (invalidemail($email)) {
               $error_message = "Invalid email address!";
            } else if (pwdMatch($pwd, $pwdrepeat)) {
               $error_message = "Passwords don't match!";
            } else if (uidExists($conn, $username, $email)) {
               $error_message = "Username is already taken!";
            } else if (!createUser($conn, $name, $email, $username, $pwd)) {
               $error_message = "Server error, sign up failed!";
            } else {
               $success = 1;
            }
        } else {
           $name = $email = $username = "";
           if (isset($_GET["error"]))
              $error_message = "Server error, sign up failed!";
        }
        ?>

        <?php
        if (isset($success)) {
            ?>
            <p>Welcome to the EDL.</p>
            <p><a href="login.php">Login</a></p>
            <?php
        } else {
           ?>
           
           <form method="POST" action="signup.php">
               <label for="name">Name:</label>
               <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($name); ?>" placeholder="Name">

               <label for="email">Email:</label>
               <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Email">

               <label for="uid">Username:</label>
               <input type="text" name="uid" id="uid" value="<?php echo htmlspecialchars($username); ?>" placeholder="Username">

               <label for="pwd">Password:</label>
               <input type="password" name="pwd" id="pwd" placeholder="Password">

               <label for="pwdrepeat">Repeat Password:</label>
               <input type="password" name="pwdrepeat" id="pwdrepeat" placeholder="Repeat Password">

               <input type="submit" name="submit" value="Signup">
           </form>

           <?php
           // Display error message if set
           if (isset($error_message)) {
               echo '<p class="error-message">' . $error_message . '</p>';
           }
       }
       ?>
    </div>
</body>
</html>
