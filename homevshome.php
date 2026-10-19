<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

$title = 'Home vs Home';
include 'Includes/header.inc.php';

// Check for an active, accepted homevshome challenge
$challenge = findChallenge($conn);
if (!$challenge || $challenge['status'] != 'accepted' || $challenge['type'] != 'homevshome') {
  header("Location: homevshome.php");
  exit;
}

// Loser is the current user reporting
$loser = getUserId();
$challenger = $challenge['challenger'];
$challenged = $challenge['challenged'];
$mission1 = $challenge['mission1'] ?? ''; // Should be challenger's home from challenge.php
$mission2 = $challenge['mission2'] ?? ''; // Should be challenged's home from accept.php

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report'])) {
  $winner1 = $_POST['winner1'] ?? '';
  $score1_winner = $_POST['score1_winner'] ?? 0;
  $score1_loser = $_POST['score1_loser'] ?? 0;
  $winner2 = $_POST['winner2'] ?? '';
  $score2_winner = $_POST['score2_winner'] ?? 0;
  $score2_loser = $_POST['score2_loser'] ?? 0;
  $game1Suicides = $_POST['game1Suicides'] ?? 0;
  $game2Suicides = $_POST['game2Suicides'] ?? 0;
  $loserComment = trim($_POST['loserComment'] ?? 'GG');
  if ($loserComment === '') {
    $loserComment = 'GG';
  }

  $errors = [];
  if (!in_array($winner1, [$challenger, $challenged])) {
    $errors[] = "Invalid winner selected for Game 1.";
  }
  if (!ctype_digit((string)$score1_winner) || !ctype_digit((string)$score1_loser) || (int)$score1_winner !== max(20, (int)$score1_loser + 2)) {
    $errors[] = "Invalid scores for Game 1 (winner must score 20 or exactly 2 more than the loser).";
  }
  if (!in_array($winner2, [$challenger, $challenged])) {
    $errors[] = "Invalid winner selected for Game 2.";
  }
  if (!ctype_digit((string)$score2_winner) || !ctype_digit((string)$score2_loser) || (int)$score2_winner !== max(20, (int)$score2_loser + 2)) {
    $errors[] = "Invalid scores for Game 2 (winner must score 20 or exactly 2 more than the loser).";
  }
  if (!ctype_digit((string)$game1Suicides) || !ctype_digit((string)$game2Suicides)) {
    $errors[] = "Game suicides must be zero or a positive whole number.";
  }

  if (empty($errors)) {
    // Game 1 (Challenger’s Home)
    $challengerKills1 = $winner1 == $challenger ? $score1_winner : $score1_loser;
    $challengedKills1 = $winner1 == $challenged ? $score1_winner : $score1_loser;

    // Game 2 (Challenged’s Home)
    $challengedKills2 = $winner2 == $challenged ? $score2_winner : $score2_loser;
    $challengerKills2 = $winner2 == $challenger ? $score2_winner : $score2_loser;

    $game1Suicides = (int)$game1Suicides;
    $game2Suicides = (int)$game2Suicides;
    $combinedSuicides = $game1Suicides + $game2Suicides;

    // Suicide totals are recorded per game without assigning them to a player.
    $game1ChallengerFinalKills = $challengerKills1;
    $game1ChallengedFinalKills = $challengedKills1;
    $game1ChallengerFinalDeaths = $challengedKills1;
    $game1ChallengedFinalDeaths = $challengerKills1;
    $game2ChallengerFinalKills = $challengerKills2;
    $game2ChallengedFinalKills = $challengedKills2;
    $game2ChallengerFinalDeaths = $challengedKills2;
    $game2ChallengedFinalDeaths = $challengerKills2;
    $challengerFinalKills = $game1ChallengerFinalKills + $game2ChallengerFinalKills;
    $challengedFinalKills = $game1ChallengedFinalKills + $game2ChallengedFinalKills;

    $loser1 = $winner1 == $challenger ? $challenged : $challenger;
    $winner1Kills = $winner1 == $challenger ? $game1ChallengerFinalKills : $game1ChallengedFinalKills;
    $winner1Deaths = $winner1 == $challenger ? $game1ChallengerFinalDeaths : $game1ChallengedFinalDeaths;
    $loser1Kills = $winner1 == $challenger ? $game1ChallengedFinalKills : $game1ChallengerFinalKills;
    $loser1Deaths = $winner1 == $challenger ? $game1ChallengedFinalDeaths : $game1ChallengerFinalDeaths;
    $loser2 = $winner2 == $challenger ? $challenged : $challenger;
    $winner2Kills = $winner2 == $challenger ? $game2ChallengerFinalKills : $game2ChallengedFinalKills;
    $winner2Deaths = $winner2 == $challenger ? $game2ChallengerFinalDeaths : $game2ChallengedFinalDeaths;
    $loser2Kills = $winner2 == $challenger ? $game2ChallengedFinalKills : $game2ChallengerFinalKills;
    $loser2Deaths = $winner2 == $challenger ? $game2ChallengedFinalDeaths : $game2ChallengerFinalDeaths;

    // Overall result is determined by adjusted totals across both games.
    if ($challengerFinalKills == $challengedFinalKills) {
      $errors[] = "The total scores are tied; the reporter must have the lower total.";
      $winner = $challenger;
      $loser = $challenged;
    } else {
      $winner = $challengerFinalKills > $challengedFinalKills ? $challenger : $challenged;
      $loser = $winner == $challenger ? $challenged : $challenger;
      if ((string)$loser !== (string)getUserId()) {
        $errors[] = "The player with the lower total must submit the report.";
      }
    }

    // Store totals in winner/loser order, after confirming the reporter is the loser.
    $totalWinnerKills = $winner == $challenger ? $challengerFinalKills : $challengedFinalKills;
    $totalLoserKills  = $winner == $challenger ? $challengedFinalKills : $challengerFinalKills;
    $totalWinnerDeaths = $totalLoserKills;
    $totalLoserDeaths = $totalWinnerKills;

    // Store in challenges table only after validating the reporter against adjusted totals.
    if (empty($errors)) {
    $query = "UPDATE challenges SET 
            status = 'reported', 
            winner = ?, 
            loser = ?, 
            winnerKills = ?, 
            loserKills = ?, 
            winnerDeaths = ?, 
            loserDeaths = ?, 
            combinedSuicides = ?, 
            mission = ?, 
            loserComment = ?,
            winner1 = ?, 
            winner1Kills = ?, 
            winner1Deaths = ?, 
            loser1 = ?,
            loser1Kills = ?,
            loser1Deaths = ?,
            winner2 = ?, 
            winner2Kills = ?, 
            winner2Deaths = ?,
            loser2 = ?,
            loser2Kills = ?,
            loser2Deaths = ?,
            gamesCombined = ?,
            score = ?
            WHERE id = ?";
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $query)) {
      $missionCombined = "$mission1 / $mission2";
      $gamesCombined = "$winner1 $score1_winner-$score1_loser | $winner2 $score2_winner-$score2_loser";
      $score = "$totalWinnerKills-$totalLoserKills";
      mysqli_stmt_bind_param(
        $stmt,
        'ssiiiiisssiisiisiisiissi',
        $winner,
        $loser,
        $totalWinnerKills,
        $totalLoserKills,
        $totalWinnerDeaths,
        $totalLoserDeaths,
        $combinedSuicides,
        $missionCombined,
        $loserComment,
        $winner1,
        $winner1Kills,
        $winner1Deaths,
        $loser1,
        $loser1Kills,
        $loser1Deaths,
        $winner2,
        $winner2Kills,
        $winner2Deaths,
        $loser2,
        $loser2Kills,
        $loser2Deaths,
        $gamesCombined,
        $score,
        $challenge['id']
      );
      mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);

      $webhookUrl = 'https://discord.com';
      $linkUrl = "https://ascentroid.com";
      $message = "$loser has reported a Home vs Home match against $winner. $winner please confirm the match!";
      $embeds = [['title' => 'Confirm', 'url' => $linkUrl]];
      $data = ['content' => $message, 'embeds' => $embeds];
      $options = ['http' => ['header' => "Content-Type: application/json\r\n", 'method' => 'POST', 'content' => json_encode($data)]];
      file_get_contents($webhookUrl, false, stream_context_create($options));

      $success = true;
    } else {
      $errors[] = "Error reporting the game: " . mysqli_error($conn);
    }
    }
  }
}
?>

<div class="container">
    <style> 
        input[type="submit"], button { color: #a37941; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; vertical-align: top; }
        th { background-color:rgb(34, 34, 34); }
    </style>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <p>Your Home vs Home game is awaiting confirmation.</p>
        <p><a href="accept.php">Check Status</a></p>
    <?php else: ?>
        <form method="POST" action="homevshome.php">
            <div class="namesize">
            
                <br />
                <p style="margin-bottom:0"><?php echo htmlspecialchars($loser); ?></p>
            </div>
            <p style="margin-top:0">Report the Home Vs Home match!</p>
            <br>
            <table>
                <tr>
                    <th>Game 1 - <?php echo htmlspecialchars($challenger); ?>'s Home</th>
                    <th>Game 2 - <?php echo htmlspecialchars($challenged); ?>'s Home</th>
                </tr>
                <tr>
                    <td>
                        <label for="mission1">Mission:</label><br>
                        <input type="text" name="mission1" id="mission1" value="<?php echo htmlspecialchars($mission1); ?>" readonly><br>
                        <label for="winner1">Winner:</label><br>
                        <select name="winner1" id="winner1" required>
                            <option value="<?php echo htmlspecialchars($challenger); ?>"><?php echo htmlspecialchars($challenger); ?></option>
                            <option value="<?php echo htmlspecialchars($challenged); ?>"><?php echo htmlspecialchars($challenged); ?></option>
                        </select><br>
                        <label for="score1_winner">Winner's Score:</label><br>
                        <input type="number" name="score1_winner" id="score1_winner" min="20" value="20" required><br>
                        <label for="score1_loser">Loser's Score:</label><br>
                        <input type="number" name="score1_loser" id="score1_loser" min="0" required><br>
                        <label for="game1Suicides">Game 1 Suicides:</label><br>
                        <input type="number" name="game1Suicides" id="game1Suicides" min="0" value="0" required><br>
                    </td>
                    <td>
                        <label for="mission2">Mission:</label><br>
                        <input type="text" name="mission2" id="mission2" value="<?php echo htmlspecialchars($mission2); ?>" readonly><br>
                        <label for="winner2">Winner:</label><br>
                        <select name="winner2" id="winner2" required>
                            <option value="<?php echo htmlspecialchars($challenger); ?>"><?php echo htmlspecialchars($challenger); ?></option>
                            <option value="<?php echo htmlspecialchars($challenged); ?>"><?php echo htmlspecialchars($challenged); ?></option>
                        </select><br>
                        <label for="score2_winner">Winner's Score:</label><br>
                        <input type="number" name="score2_winner" id="score2_winner" min="20" value="20" required><br>
                        <label for="score2_loser">Loser's Score:</label><br>
                        <input type="number" name="score2_loser" id="score2_loser" min="0" required><br>
                        <label for="game2Suicides">Game 2 Suicides:</label><br>
                        <input type="number" name="game2Suicides" id="game2Suicides" min="0" value="0" required><br>
                    </td>
                </tr>
            </table>
            <br>
            <label for="loserComment">Loser's Comment:</label>
            <input type="text" name="loserComment" id="loserComment" value="<?php echo htmlspecialchars($loserComment ?? 'GG'); ?>"><br>
            <br>
            <button type="submit" name="report">Report</button>
        </form>
        <script>
          document.addEventListener('DOMContentLoaded', () => {
            [['score1_winner', 'score1_loser'], ['score2_winner', 'score2_loser']].forEach(([winnerId, loserId]) => {
              const winner = document.getElementById(winnerId);
              const loser = document.getElementById(loserId);
              const updateWinnerLimit = () => {
                const loserScore = loser.value === '' ? 0 : Number(loser.value);
                const requiredWinnerScore = Math.max(20, loserScore + 2);
                winner.min = String(requiredWinnerScore);
                winner.max = String(requiredWinnerScore);
                winner.setCustomValidity(
                  winner.value !== '' && Number(winner.value) !== requiredWinnerScore
                    ? `Winner must score exactly ${requiredWinnerScore}.`
                    : ''
                );
              };
              loser.addEventListener('input', updateWinnerLimit);
              winner.addEventListener('input', updateWinnerLimit);
              updateWinnerLimit();
            });
          });
        </script>
    <?php endif; ?>
</div>

<?php include 'Includes/footer.inc.php'; ?>
