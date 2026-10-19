<!DOCTYPE html>
    <?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDL-Login</title>

    <!-- Discord & Open Graph Tags -->
    <meta property="og:title" content="Welcome to the EDL" />
    <meta property="og:description" content="Come join the Elite Ranks" />
    <meta property="og:url" content="https://edl.ascentroid.com" />
    
    <!-- CRITICAL: The full, absolute URL to your image -->
    <meta property="og:image" content="https://edl.ascentroid.com/Images/edlpage.png" />
    
    <!-- Optional: Makes the preview image display larger in Discord -->
    <meta name="twitter:card" content="summary_large_image">
</head>
<body>

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
		<br><p>Elite Descent Ladder Rules</p>
		
<br><p>1. Purpose</p>

The ladder is designed to rank players based on head-to-head competition.</p></br>

<p>2. Match Format</p>
Traditional:</br>
All matches are 1 vs 1</br>
Games are played to 20 points</br>
Players must win by 2 points</br>
One game decides the match (unless playing Home Vs Home format)</br>

<br>Home Vs Home:</br>
Back to Back to 20</br>
Play Game 1 your home first,</br>
Game 2 in theirs!</br>
Comparing the score difference for the win!</br>

<br>Random Home:</br>
A single match to 20, win by 2.</br>
The system randomly selects one home</br>
from both players' five homes</br>
upon challenge acceptance.</br>

<br>Players are free to agree on any available settings</br>
Original settings take presidence over altered settings</br>

<br><p>3. Who You Can Challenge</p>

A player may challenge any player</br>

Challenges are made and accepted in good faith</br>

<br><p>4. Scheduling & Acceptance</p>

A challenged player must respond within 48 hours</br>

The match should be played within 7 days of acceptance</br>

If the challenged player cannot play within that time, they must propose an alternative date</br>

<br><p>5. Match Results & Ladder Movement</p>

If the challenger wins, the challenger (the lower ranked of the two players)</br> takes the challenged players position</br>

If the challenged player wins, rankings remain the same</br>

<br><p>6. Scoring</p>

Scores should be reported by the losing player immediately after the match</br>

Scoring includes mission</br>20 as the assumed score for the winner</br>losers score</br>the combined suicides of both players</br> and your comment with 'GG' as the default</br>

<br><p>7. No-Shows & Forfeits</p>

No-Show or Forfiet scenerios are to be brought to an admins attention for review and handling</br>

A player who fails to show up without notice or quits without returning for an unreasonable ammount</br> of time (over 24hrs) after review may be subject to forfeiting the match</br>

A forfeit counts as a loss and ladder positions are adjusted accordingly</br>

Repeated no-shows may result in temporary removal from the ladder</br>

<br><p>8. Rematches</p>

Immediate rematches are unlimited</br> but only allowed if there are no other players who wish to join the challenge que</br>

If another player wishes to join the challenge que</br> the first party of players are limited to a maximum of 3 matches in a row</br> so long as there is another player who wishes to join the challenge que</br>
the original party must yeild after the 3rd match</br> until the 3rd party player has atleast played one match before the </br>original party can re-challenging the same opponent</br>
Please be mindful of other players who wish to play</br>

<br><p>9. Inactivity</p>

Players who do not play or accept challenges for 2 weeks may be moved down or removed</br>

Inactive players can rejoin at the bottom of the ladder</br>

<br><p>10. Fair Play</p>

All matches should be played honestly and respectfully</br>

Disputes should be settled by replaying the point or, if necessary, the match</br>

Unsportsmanlike behavior may result in penalties or removal</br>

<br><p>11. Rule Changes</p>

Rules may be updated if needed, with notice given to all players</p>
        
           
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