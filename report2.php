<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

// Check if there's an active challenge for this player
$challenge = findChallenge($conn);

// For testing:
// $challenge = ['challenger', array('id' => 1, 'challenged' => isset($_POST['mission']) ? $_POST['mission'] : '')];

// If there is no challenge or not accepted, redirect to the appropriate page
if (!$challenge || $challenge['status'] != 'accepted') {
    redirectChallenge($challenge);
    exit;
}

// Get list of user uids
$useruids = getUsers($conn);

// Check if the form is submitted
if ($challenge && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the form data
    $mission = $_POST['mission'];
    $winnerKills = $_POST['winner_kills'];
    $forfeit = isset($_POST['forfeit']) ? 1 : 0;
    $loserKills = $_POST['loser_kills'];

    // Validate the form data
    $errors = [];

    if (!ctype_digit($winnerKills) || $winnerKills < 0) {
        $errors[] = "Invalid number of kills for winner.";
    }

    if (!ctype_digit($loserKills) || $loserKills < 0) {
        $errors[] = "Invalid number of kills for loser.";
    }

    if (empty($errors)) {
        // Form data is valid, report the game score
        reportGame($conn, $challenge['id'], $mission, $winnerKills, $forfeit, $loserKills);
        $success = true;

        // Determine the winner and loser based on the number of kills
        if ($winnerKills > $loserKills) {
            $winner = $challenge['challenger'];
            $loser = $challenge['challenged'];
        } else {
            $winner = $challenge['challenged'];
            $loser = $challenge['challenger'];
        }

        // Send message to Discord webhook
        $discordWebhookUrl = 'https://discord.com/api/webhooks/1124540937993998346/rjQ-QWgLSqHOLR6tEQmwHpCqe2rj1cfdP6bP9pHfEta-wUmvK8ug74xkUmJTiwvXt1Gl';
        $message = "$winner please confirm your match against $loser!";
        $data = array('content' => $message);
        $options = array(
            'http' => array(
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data),
            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($discordWebhookUrl, false, $context);

        if ($result === false) {
            // Error handling if the request to the Discord webhook fails
            // You can add your own error handling logic here
        }
    }
}

$title = 'Report';
include 'Includes/header.inc.php';
?>

<div class="container">
    <!-- Display errors if any -->
    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <p>Your game is awaiting confirmation.</p>
    <?php else: ?>
        <form method="POST" action="report.php">
            <label for="mission">Mission:</label>
            <input type="text" name="mission" id="mission">
            <br>
            <label for="winner_kills">Winner Kills:</label>
            <input value="20" type="number" name="winner_kills" id="winner_kills">
            <br>
            <label for="forfeit">Forfeit Y/N:</label>
            <input type="checkbox" value="1" name="forfeit">
            <br>
            <label for="loser_kills">Loser Kills:</label>
            <input type="number" name="loser_kills" id="loser_kills">
            <br>
            <button type="submit">Report</button>
        </form>
    <?php endif; ?>
</div>

<?php
include 'Includes/footer.inc.php';
?>
