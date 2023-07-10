<?php
if(!isset($_SESSION)) { 
  session_start(); 
}

    $currentURL = $_SERVER['REQUEST_URI'];
    if (!isset($_SESSION['useruid']) && !isset($public)) {
        header('Location: login.php');
       
        exit();
    }
?><!DOCTYPE html>
<html>
<head>
  <link rel="shortcut icon" type="image/png" href="/favicon.png"/>
  <title>EDL-<?php echo htmlspecialchars($title); ?></title>
  <link rel="stylesheet" type="text/css" href="Css/reset.css">
  <link rel="stylesheet" type="text/css" href="Css/style.css">
</head>
<body>
<div class="background-image"></div>
<header>

  <div class="navbar">
  <?php
    if (isset($_SESSION['useruid'])) {
    ?>
    <br>
    <div>
    <a href="editprofile.php" <?php if(isset($currentURL) && $currentURL == "/editprofile.php") echo 'class="active"'; ?>>EditProfile</a>
   <a href="ranks.php" <?php if(isset($currentURL) && $currentURL == "/ranks.php") echo 'class="active"'; ?>>Ranks</a>    
    <a href="challenge.php" <?php if(isset($currentURL) && $currentURL == "/challenge.php") echo 'class="active"'; ?>>Challenge</a>
    <a href="accept.php" <?php if(isset($currentURL) && $currentURL == "/accept.php") echo 'class="active"'; ?>>Accept</a>
    <a href="report.php" <?php if(isset($currentURL) && $currentURL == "/report.php") echo 'class="active"'; ?>>Report</a>
    <a href="games.php" <?php if(isset($currentURL) && $currentURL == "/games.php") echo 'class="active"'; ?>>Games</a>
    <a href="logout.php" <?php if(isset($currentURL) && $currentURL == "/logout.php") echo 'class="active"'; ?>>Logout</a></div>
  <br>
  </br>
    <?php
    } else {
    ?>
    <a href="login.php" <?php if(isset($currentURL) && $currentURL == "/login.php") echo 'class="active"'; ?>>Login</a>
    <?php
    }
  ?>
  </div>
</header>
<div class="main-footer-wrapper">
<div class="main-wrapper">
<div class="main">
