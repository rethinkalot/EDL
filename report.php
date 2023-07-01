<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

$challenge = findChallenge($conn);

if (!$challenge || $challenge['status'] != 'accepted') {
    redirectChallenge($challenge);
    exit;
}

$useruids = getUsers($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mission = $_POST['mission'];
    $winnerDeaths = intval($_POST['winner_deaths']);
    $winnerSuicides = intval($_POST['winner_suicides']);
    
    $forfeit = 0;

    $loserKills = $winnerDeaths;
    $winnerKills = 20;
    if ($loserKills >= 19) {
        $winnerKills = $loserKills + 2;
    }

    $loserDeaths = $loserKills;
    $loserSuicides = 0;

    reportGame($conn, $challenge['id'], $mission, $winnerKills, $forfeit, $loserKills,
        $winnerDeaths, $winnerSuicides, $loserDeaths, $loserSuicides);
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
        <label for="mission">Winner Reports</label>
        <label for="mission">Mission:</label>
        <input type="text" name="mission" id="mission" reqired:>
        <br>
        <label for="winner_deaths">Winner Deaths</label>
        <input type="number" name="winner_deaths" id="deaths" required>
        <br>
        <!--<label for="winner_suicides">Winner Suicides?</label>
        <input type="number" name="winner_suicides" id="winner_suicides" required>
        <br>-->
        <button type="submit">Report</button>
        </form>
    <?php endif; ?>
</div>

<?php
include 'Includes/footer.inc.php';
?>

