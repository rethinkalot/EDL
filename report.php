<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

// Check if there's an active challenge for this player
$challenge = findChallenge($conn);

// If there is no challenge or not just challenged, redirect to the appropriate page
if (!$challenge || $challenge['status'] != 'accepted') {
  redirectChallenge($challenge);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST["accept"])) {
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
    $result = @file_get_contents($discordWebhookUrl, false, $context);

    if ($result === false) {
        // Error handling if the request to the Discord webhook fails
        // You can add your own error handling logic here
    }

    // Redirect to the report.php page
    header("Location: report.php");
    exit;
} elseif (isset($_POST["decline"])) {
    $status = "declined";
    setChallengeStatus($conn, $challenge['id'], $status);
}

function calculateStats($loserKills, $loserSuicides, $targetScore) {
    $winnerKills = $targetScore;
    $winnerDeaths = $loserKills;
    $loserDeaths = $winnerKills;
    $winnerSuicides = 0;

    return array($winnerKills, $winnerDeaths, $winnerSuicides, $loserKills, $loserDeaths, $loserSuicides);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the reported deaths from the form
    $mission = $_POST['mission'];
    $loserKills = $_POST['loserKills'];
    $loserSuicides = $_POST['loserSuicides'];

    // Perform any additional validation or database operations here
    $errors = [];

    if (!ctype_digit($loserKills) || $loserKills < 0) {
        $errors[] = "Invalid number of kills for the loser.";
    }

    if (!ctype_digit($loserSuicides) || $loserSuicides < 0) {
        $errors[] = "Invalid number of suicides for the loser.";
    }

    if (empty($errors)) {
        $targetScore = 20; // Replace with your actual target score

        // Calculate kills, deaths, and suicides based on the winner's report
        list($winnerKills, $winnerDeaths, $winnerSuicides, $loserKills, $loserDeaths, $loserSuicides) = calculateStats($loserKills, $loserSuicides, $targetScore);

        // Perform the reporting logic using the calculated kills, deaths, and suicides

        // Example output (you can modify this based on your needs)
        //echo "Winner: $winnerKills kills, $winnerDeaths deaths, $winnerSuicides suicides. ";
        //echo "Loser: $loserKills kills, $loserDeaths deaths, $loserSuicides suicides. ";
        
        // Set forfeit always off for now
       
      reportGame($conn, $challenge['id'], $mission, $winnerKills, $loserKills, $winnerDeaths, $winnerSuicides, $loserDeaths, $loserSuicides);

      // Redirect or display success message
      $success = true;
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

    <?php
    $status = "reported";
    setChallengeStatus($conn, $challenge['id'], $status);

    $discordWebhookUrl = 'https://discord.com/api/webhooks/1124540114169770075/4HGcYrEFuSdldR5E_3ptLibSbf2IxX3yWKgl8etnFE1YdGrIebnUpt1hojEh-5atl00j';
    $message = "Your opponent has reported, please confirm the match!!";
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
      // Handle any errors here
    }
    ?>

  <?php else: ?>

  <form method="POST" action="report.php">
    <br>
    <br>
    <?php
    echo getUserId();
    ?>
    <br>
    <br>
    <label for="mission">Mission:</label>
    <input type="text" name="mission" id="mission" required>
    <br>
    <label for="loserKills">Report your Kills:</label>
    <input type="number" name="loserKills" id="loserKills" required>
    <br>
    <label for="loserSuicides">Report your Suicides:</label>
    <input type="number" value="0" name="loserSuicides" id="loserSuicides" required>
    <br>
    <button type="submit" name="report">Report</button>
  </form>

  <?php endif; ?>

</div>
<?php
include 'Includes/footer.inc.php';
?>
