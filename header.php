<?php
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

session_start();

?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
 <head>

    <link rel="stylesheet" type="text/css" href="reset.css">
    <link rel="stylesheet" type="text/css" href="style.css">
<style>

 .navbar {
  display: flex;
  justify-content: center;
}


    .navbar {
  position: fixed;
  top: 360px;
  left: 50%;
  transform: translateX(-50%);
}


.navbar a {
  margin: 5px; /* Optional: Add margin between navigation links */
}
.navbar li a {
  text-decoration: none;
  padding: 10px;
  font-weight: bold;
}

.navbar a {
    font-family: Verdana, sans-serif;
}
.navbar li {
  display: inline-block;
  background: #10101;
  }



      <?php
        if (isset($_SESSION["useruid"])) {
          echo "<li><a href='profile.php'>Profile page</a></li>";
          echo "<li><a href='includes/logout.php'>Log Out</a></li>";

        }
        else {
          echo "<li><a href='signup.php'>Sign Up</a></li>";
          echo "<li><a href='login.php'>Log In</a></li>";
        }
      ?>    

