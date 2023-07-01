<?php
    session_start();
    if (isset($_SESSION['useruid']))
        $public = true;
    unset($_SESSION['useruid']);
    $title = 'Logout';
    include 'Includes/header.inc.php';
?>
    <div class="container">
        <div class="center">
            <p>You are logged out.</p>
            <p><a href="login.php">Login</a></p>
        </div>
    </div>
<?php
    include 'Includes/footer.inc.php';
?>
