<?php

session_start();


if (isset($_SESSION['useruid'])) {
       header('Location: index.php');
    exit();
}


?>
    <link rel="stylesheet" type="text/css" href="reset.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>

 .navbar {
  display: relative;
  justify-content: center;
  background-color: #f2f2f2
}

.navbar {
      position: relative;
      top: 360px; /* Adjust this value to move the navbar down */
    }

.navbar a {
  margin: 20px; /* Optional: Add margin between navigation links */
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
  display: ;
}    

.nav {
    background: #f2f2f2;
    padding: 30px;
}
     </style>


</head>
<body>
    <?php
        // Get the current page's URL
        $currentURL = $_SERVER['REQUEST_URI'];
    ?>
    

    <ul class="navbar">
    <li class="navitem"><a href="editprofile.php" <?php if($currentURL == "/editprofile.php") echo 'style="background-color: #000;"'; ?>>EditProfile</a></li>
        <li class="navitem"><a href="rank.php" <?php if($currentURL == "/rank.php") echo 'style="background-color: #000;"'; ?>>Rank</a></li>
        <li class="navitem"><a href="challenge.php" <?php if($currentURL == "/challenge.php") echo 'style="background-color: #000;"'; ?>>Challenge</a></li>
        <li class="navitem"><a href="accept.php" <?php if($currentURL == "/accept.php") echo 'style="background-color: #000;"'; ?>>Accept</a></li>
        <li class="navitem"><a href="report.php" <?php if($currentURL == "/report.php") echo 'style="background-color: #000;"'; ?>>Report</a></li>
        <li class="navitem"><a href="games.php" <?php if($currentURL == "/games.php") echo 'style="background-color: #000;"'; ?>>Games</a></li>
        <li class="navitem"><a href="activity.php" <?php if($currentURL == "/games.php") echo 'style="background-color: #000;"'; ?>>Activity</a></li>
        <li class="navitem"><a href="login.php" <?php if($currentURL == "/rank.php") echo 'style="background-color: #000;"'; ?>>Login</a></li>
        <li class="navitem"><a href="Login.php">Logout</a>
    </ul>
</body>
</html>