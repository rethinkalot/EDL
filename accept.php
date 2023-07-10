<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

$title = 'accept';
include 'Includes/header.inc.php';

// Check if there's an active challenge for this player
$challenge = findChallenge($conn);

// If there is no challenge or not just challenged, redirect to the appropriate page
if (!$challenge || $challenge['status'] != 'challenged') {
    redirectChallenge($challenge);
    exit;
}

if ($challenge && isset($_POST["accept"])) {
    $status = "accepted";
    setChallengeStatus($conn, $challenge['id'], $status);

    // Send message to Discord webhook
    $discordWebhookUrl = 'https://discord.com/api/webhooks/1124540114169770075/4HGcYrEFuSdldR5E_3ptLibSbf2IxX3yWKgl8etnFE1YdGrIebnUpt1hojEh-5atl00j';
    $message = $challenge['challenged'] . ' has accepted the match!';
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

    // Redirect to the report.php page
    header("Location: report.php");
    exit;
} elseif ($challenge && isset($_POST["decline"])) {
    $status = "declined";
    setChallengeStatus($conn, $challenge['id'], $status);

    // Send message to Discord webhook
    $discordWebhookUrl = 'https://discord.com/api/webhooks/1124540114169770075/4HGcYrEFuSdldR5E_3ptLibSbf2IxX3yWKgl8etnFE1YdGrIebnUpt1hojEh-5atl00j';
    $message = $challenge['challenged' | 'challenger'] . ' has declined the match!';
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

    // Redirect to the report.php page
    header("Location: report.php");
    exit;
}
?>

<div class="container">

    <style> 
        input[type="submit"] {
            color: #a37941;
        }            
    </style>

    <?php
    if (isset($status)) {
        ?><p>Challenge <?php echo htmlspecialchars($status); ?>.</p>
    <?php
    } else if ($challenge['challenger'] == getUserId()) { // player is the challenger
    ?>

    <p>Your challenge has been sent. Would you like to Cancel?</p>
    <form method="POST">
    <input type="submit" name="decline" value="Cancel">
    </form>

    <?php
    } else if ($challenge['challenged'] == getUserId()) { // player is challenged
    ?>

    <p>You have been issued a challenge by <?php echo htmlspecialchars($challenge['challenger']); ?>. Would you like to Accept or Decline?</p>
    <form method="POST">
    <input type="submit" name="accept" value="Accept">
    <input type="submit" name="decline" value="Decline">
    </form>

    <?php
    }
    ?>

</div>

<?php
include 'Includes/footer.inc.php';
?>
