<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

$title = 'Report Traditional Match';
include 'Includes/header.inc.php';

// Check for an active, accepted challenge
$challenge = findChallenge($conn);
if (!$challenge || $challenge['status'] != 'accepted') {
    error_log("Invalid challenge, redirecting to challenge.php from report.php");
    redirectChallenge($challenge);
    exit;
}

//Redorect Randomhome challenges to randomhome.php
if ($challenge['type'] == 'randomhome') {
    error_log("RandomHome challenge, redirecting to randomhome.php from report.php");
    header("Location: randomhome.php");
    exit;
}

// Redirect homevshome challenges to homevshome.php
if ($challenge['type'] == 'homevshome') {
    error_log("Homevshome challenge, redirecting to homevshome.php from report.php");
    header("Location: homevshome.php");
    exit;
}

// Loser is the current user reporting
$loser = getUserId();
if (!$loser) {
    error_log("No user ID, redirecting to login.php from report.php");
    header("Location: login.php");
    exit;
}
$challenger = $challenge['challenger'];
$challenged = $challenge['challenged'];
$winner = ($loser == $challenger) ? $challenged : $challenger;

 // Set deaths equal to opponent's kills   

    

// Function to calculate stats for traditional-type games
function calculateStats($loserKills, $combinedSuicides, $targetScore = 20) {
    $loserKills = (int)$loserKills;
    
    $combinedSuicides = (int)$combinedSuicides;
    $winnerKills = $loserKills > ($targetScore - 2) ? $loserKills + 2 : $targetScore;
    $loserDeaths = floor($combinedSuicides / 2);
    $winnerDeaths = $combinedSuicides - $loserDeaths;
    return [$winnerKills, $winnerDeaths, $loserKills, $loserDeaths];
}

// Handle form submission
$reported = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report'])) {
    $loserKills = $_POST['loserKills'] ?? '';
    $combinedSuicides = $_POST['combinedSuicides'] ?? '';
    $mission = $_POST['mission'] ?? '';
    $gameplayed = $_POST['gameplayed'] ?? ($challenge['gameplayed'] ?? 'Traditional');
    $loserComment = $_POST['loserComment'] ?? '';

    $errors = [];
    if (empty($gameplayed)) {
        $errors[] = "Game type is required.";
    }
    if (empty($mission)) {
        $errors[] = "Mission is required.";
    }
    if (!is_numeric($loserKills) || (int)$loserKills < 0 || (int)$loserKills > 50) {
        $errors[] = "Loser kills must be between 0 and 50.";
    }
    if (!is_numeric($combinedSuicides) || (int)$combinedSuicides < 0 || (int)$combinedSuicides > 100) {
        $errors[] = "Combined suicides must be between 0 and 100.";
    }

    if (empty($errors)) {
        list($winnerKills, $winnerDeaths, $loserKills, $loserDeaths) = calculateStats($loserKills, $combinedSuicides);
        $combinedSuicides = (int)$combinedSuicides;
        try {
            reportGame(
                $conn,
                $challenge['id'],
                $winner,
                $loser,
                $winnerKills,
                $loserKills,
                $winnerDeaths,
                $loserDeaths,
                $combinedSuicides,
                $gameplayed,
                $mission,
                $loserComment
            );
            $discordWebhookUrl = 'YOUR_WEBHOOK_URL_HERE';
			$linkUrl = "https://edl.ascentroid.com/accept.php";
            $message = "$loser has reported a {$challenge['type']} match!\n$winner please confirm your win!";
            $embeds = [['title' => 'Confirm', 'url' => $linkUrl]];
			$data = ['content' => $message, 'embeds' => $embeds];
            $options = ['http' => ['header' => "Content-Type: application/json\r\n", 'method' => 'POST', 'content' => json_encode($data)]];
            file_get_contents($discordWebhookUrl, false, stream_context_create($options));
            $reported = true;
        } catch (Exception $e) {
            $errors[] = "Error reporting game: " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <style>
        input[type="submit"], button { color: #a37941; }
        table { width: 30%; }
        td, th { width: 50%; }
        p { margin: 1em 0; }
    </style>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($reported): ?>
        <p>Match reported successfully.</p>
        <p><?php echo htmlspecialchars($loser); ?> reported: </p>
        <table>
            <tr><th>Game</th><td><?php echo htmlspecialchars($gameplayed); ?></td></tr>
            <tr><th>Mission</th><td><?php echo htmlspecialchars($mission); ?></td></tr>
            <tr><th>Winner</th><td><?php echo htmlspecialchars($winner); ?></td></tr>
            <tr><th>Kills</th><td><?php echo htmlspecialchars($winnerKills); ?></td></tr>
            <tr><th>Loser</th><td><?php echo htmlspecialchars($loser); ?></td></tr>
            <tr><th>Kills</th><td><?php echo htmlspecialchars($loserKills); ?></td></tr>
            <tr><th>Comment</th><td><?php echo htmlspecialchars($loserComment); ?></td></tr>
        </table>
        <p>Waiting for <?php echo htmlspecialchars($winner); ?> to confirm!</p>
        <form method="post" action="accept.php">
            <input type="submit" value="Continue to Confirmation">
        </form>


        
    <?php else: ?>
        <div class="namesize">
            <br />
            <br />
            <?php echo htmlspecialchars(getUserId()); ?>
            <p style="margin-top: 2px;">Loser reports the match!</p>
        </div>
        <?php
        $url = "https://retro-tracker.game-server.cc/";
        $linkText = "Visit Tracker";
        echo "<a style='color: #815108;' href='$url' target='_blank' rel='noopener'>$linkText</a>";
        ?>
        <br><br>  
        <form method="POST" action="report.php">
            <label for="mission">Mission:</label>
            <input type="text" name="mission" id="mission" required>
            <label for="loserKills">Your Kills:</label>
            <input type="number" name="loserKills" id="loserKills" min="0" max="50" placeholder="0" required>
            <label for="combinedSuicides">Combined Suicides:</label>
            <input type="number" name="combinedSuicides" id="combinedSuicides" min="0" max="100" value="0" placeholder="0">
            <label for="loserComment">Comment:</label>
            <input id="loserComment" name="loserComment" placeholder="Comment"value="GG">
            <button type="submit" name="report">Report</button>
        </form>       
    <?php endif; ?>
</div>

<?php include 'Includes/footer.inc.php'; ?>