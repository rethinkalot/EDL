<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

$title = 'Report Random Home Match';
include 'Includes/header.inc.php';

function getValidChallengeTypes()
{
    return ['traditional', 'homevshome', 'randomhome'];
}
function validGameTypes()
{
    return ['Descent', 'Descent2', 'Descent3', 'Ascentroid', 'Overload', 'Geocore'];
}

// Check for an active, accepted randomhome challenge
$challenge = findChallenge($conn);
if (!$challenge || $challenge['status'] != 'accepted' || $challenge['type'] != 'randomhome') {
    error_log("Invalid challenge, redirecting to acceptrandom.php from randomhome.php");
    redirectChallenge($challenge);
    exit;
}

// Loser is the current user reporting
$loser = getUserId();
if (!$loser) {
    error_log("No user ID, redirecting to login.php from randomhome.php");
    header("Location: login.php");
    exit;
}
$challenger = $challenge['challenger'];
$challenged = $challenge['challenged'];
$winner = ($loser == $challenger) ? $challenged : $challenger;


function calculateStats($loserKills, $combinedSuicides, $targetScore = 20)
{
    $loserKills = (int)$loserKills;

    $combinedSuicides = (int)$combinedSuicides;
    $winnerKills = $loserKills > ($targetScore - 2) ? $loserKills + 2 : $targetScore;
    $loserDeaths = floor($combinedSuicides / 2);
    $winnerDeaths = $combinedSuicides - $loserDeaths;
    return [$winnerKills, $winnerDeaths, $loserKills, $loserDeaths];
}

// Handle form submission
$reported = false;
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report'])) {
    $loserKills = $_POST['loserKills'] ?? '';
    $combinedSuicides = $_POST['combinedSuicides'] ?? '';
    $mission = $challenge['mission'] ?? '';
    $gameplayed = $challenge['gameplayed'] ?? '';
    $loserComment = $_POST['loserComment'] ?? '';

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
            $message = htmlspecialchars($loser) . " has reported a randomhome match. " . htmlspecialchars($winner) . " please confirm your win!";
            $linkUrl = "https://edl.ascentroid.com/accept.php";
            $embeds = [['title' => 'Confirm', 'url' => $linkUrl]];
            sendDiscordNotification($message, $embeds);
            $reported = true;
            error_log("Randomhome match reported, redirecting to randomhome.php from randomhome.php");
        } catch (Exception $e) {
            $errors[] = "Error reporting game: " . $e->getMessage();
        }
    }
}
?>



<div class="container">
    <style>
        input[type="submit"],
        button {
            color: #815108;
        }

        .games-table {
            border-collapse: collapse;
            width: 30%;
            margin: 0 auto;
        }

        .games-table th,
        .games-table td {
            border: 1px solid #404e5d;
            padding: 12px;
            text-align: center;
        }

        .games-table th {
            text-align: center;
            background-color: rgb(34, 34, 34);
        }

        p {
            margin: 1em 0;
        }

        .error-message {
            color: red;
        }
    </style>

    <div class="namesize">
        <p><?php echo htmlspecialchars($loser); ?></p>
        <h2>Report Random Home Match</h2>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($reported): ?>
        <p>Match reported successfully.</p>
        <table class="games-table">
            <tr>
                <th>Game</th>
                <td><?php echo htmlspecialchars($gameplayed); ?></td>
            </tr>
            <tr>
                <th>Mission</th>
                <td><?php echo htmlspecialchars($mission); ?></td>
            </tr>
            <tr>
                <th>Winner</th>
                <td><?php echo htmlspecialchars($winner); ?></td>
            </tr>
            <tr>
                <th>Kills</th>
                <td><?php echo htmlspecialchars($winnerKills); ?></td>
            </tr>
            <tr>
                <th>Loser</th>
                <td><?php echo htmlspecialchars($loser); ?></td>
            </tr>
            <tr>
                <th>Kills</th>
                <td><?php echo htmlspecialchars($loserKills); ?></td>
            </tr>
            <tr>
                <th>Comment</th>
                <td><?php echo htmlspecialchars($loserComment); ?></td>
            </tr>
        </table>
        <p>Waiting for <?php echo htmlspecialchars($winner); ?> to confirm!</p>
        <form method="post" action="accept.php">
            <input type="submit" value="Continue to Confirmation">
        </form>
    <?php else: ?>
        <p>Loser reports the match!</p>
        <p><a href="https://retro-tracker.game-server.cc/" target="_blank" rel="noopener">Visit Tracker</a></p>
        <form method="POST" action="randomhome.php">
            <label for="mission">Mission:</label>
            <input type="text" name="mission" id="mission" value="<?php echo htmlspecialchars($challenge['mission']); ?>" readonly>
            <label for="gameplayed">Game:</label>
            <input type="text" name="gameplayed" id="gameplayed" value="<?php echo htmlspecialchars($challenge['gameplayed']); ?>" readonly>
            <label for="loserKills">Your Kills:</label>
            <input type="number" name="loserKills" id="loserKills" min="0" max="50" placeholder="0" required>
            <label for="combinedSuicides">Combined Suicides:</label>
            <input type="number" name="combinedSuicides" id="combinedSuicides" min="0" max="100" value="0" placeholder="0">
            <label for="loserComment">Comment:</label>
            <input id="loserComment" name="loserComment" placeholder="GG" value="GG">
            <button type="submit" name="report">Report</button>
        </form>
    <?php endif; ?>
</div>

<?php include 'Includes/footer.inc.php'; ?>