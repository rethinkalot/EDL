<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

$title = 'accept';
include 'Includes/header.inc.php';

// Check if there's a challenge for this player
$challenge = findChallenge($conn);

if (!$challenge) { 
    // Redirect to the challenge.php page
    header("Location: challenge.php");
    exit;
}

if ($challenge && $challenge['reporter'] != getUserId() && isset($_POST["confirm"])) {
    confirmGame($conn, $challenge['id']);
    $confirmed = 1;
}
?>

<div class="container">

    <style> 
        input[type="submit"] {
            color: #a37941;
        }
        table {
            width: 30%;
        }
        td, th {
            width: 50%;
        }
        p {
            margin: 1em 0;
        }
    </style>

    <?php
    if (isset($confirmed)) {
    ?>
        <p>Game confirmed.</p>
    <?php
    } else if ($challenge['reporter'] == getUserId()) {
    ?>
        <p>Your game is awaiting confirmation.</p>
    <?php
    } else {
    ?>
    <p><b>Reported Game</b></p>
    <table>
    <tr><th>GamePlayed</th><td><?php echo htmlspecialchars($challenge['gameplayed']); ?></td></th>
    <tr><th>Mission</th><td><?php echo htmlspecialchars($challenge['mission']); ?></td></th>
    <tr><th>Winner Kills</th><td><?php echo $challenge['winnerKills']; ?></td></th>
     <tr><th>Loser Kills</th><td><?php echo $challenge['loserKills']; ?></td></th>
    </table>

    <form method="POST">
    <input type="submit" name="confirm" value="Confirm">
    </form>

    <?php
    }
    ?>

</div>

<?php
include 'Includes/footer.inc.php';
?>
