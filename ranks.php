<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

$query = "SELECT player, `rank`, wins, losses, kills, deaths, streaks FROM ranks ORDER BY `rank`";
$rankRows = mysqli_query($conn, $query);
error_log("Ranks query fetched " . mysqli_num_rows($rankRows) . " rows");
if (!$rankRows) {
    error_log("Ranks query failed: " . mysqli_error($conn));
}

$query = "SELECT usersUid FROM users LEFT JOIN ranks ON ranks.player = users.usersUid" .
  " WHERE ranks.player IS NULL";
$unranked = iterator_to_array(mysqli_query($conn, $query));
error_log("Unranked query fetched " . count($unranked) . " users");

$title = 'Ranks';
include 'Includes/header.inc.php';
?>
<div class="container">
  <style>
        .table-wrapper { display: flex; flex-direction: column-reverse; align-items: center; gap: 10px; }
        .games-table { border-collapse: collapse; width: 40%; height: stretch; margin: 0 auto; }
        .games-table th, .games-table td { border: 1px solid #404e5d; padding: 8px; text-align: center; }
        .games-table th { text-align: center; }
        .winner-text, .loser-text { font-size: 10%; }
    </style>

 <div class="namesize">
<br />
<br />
<?php
$userId = getUserId(); // Store the result to avoid calling getUserId() twice
if ($userId !== null && !empty($userId)) {
    echo htmlspecialchars($userId); // Echo raw user ID
    ?><!-- Escaped user ID -->
    <?php
} else {
    echo "Please login!"; // Display for non-logged-in users
}
?>

 
  <p>Welcome to Ranks!<p>
    <br />
    <br />
    <h2>Seasonal Homes</h2>
    <p>Season 3 homes: Calamity, Carbon, Crimson, Cronos, Cryptic</p>
    <br />
        <table class="games-table">
    <style>
        .table-wrapper { display: flex; flex-direction: column-reverse; align-items: center; gap: 10px; }
        .games-table { border-collapse: collapse; width: 40%; margin: 0 auto; }
        .games-table th, .games-table td { border: 1px solid #404e5d; padding: 12px; text-align: center; }
        .games-table th { text-align: center; }
        .winner-text, .loser-text { font-size: 10%; }
    </style>
    <tr>
        <th>Rank</th>
        <th>Pilot</th>
        <th>Wins</th>
        <th>Losses</th>
        <th>Kills</th>
        <th>Deaths</th>
        <th>K.D.</th>
        <th>Streaks</th>
    </tr>
    <?php
    foreach ($rankRows as $rankRow) {
      $player = $rankRow['player'];
      $rank = $rankRow['rank'];
      $wins = $rankRow['wins'];
      $losses = $rankRow['losses'];
      $kills = $rankRow['kills'];
      $deaths = $rankRow['deaths'];
      $streaks = $rankRow['streaks'];
      // Calculate K.D. ratio
      $kdRatio = ($deaths > 0) ? number_format($kills / $deaths, 2) : 'N/A';
      ?>
        <tr>
            <td><?php echo $rank; ?></td>
            <td>
                <a href="profile.php?user=<?= urlencode($player) ?>">
                    <?= htmlspecialchars($player) ?>
                </a>
            </td>
            <td><?php echo $wins; ?></td>
            <td><?php echo $losses; ?></td>
            <td><?php echo $kills; ?></td>
            <td><?php echo $deaths; ?></td>
            <td><?php echo $kdRatio; ?></td>
            <td><?php echo $streaks; ?></td>
        </tr>
        <?php
    }
    ?>
</table>

    <?php
    if (!empty($unranked)) {
      ?>
        <br>
        <br>
        <h2>Unranked</h2>
        <br>
        <div class="table-container">    
        <table class="games-table">
            <tr>
                <th>Pilot</th>
            </tr>
            <?php
            foreach ($unranked as $row) {
              $player = $row['usersUid'];
              ?>
                <tr>
                    <td>
                        <a href="profile.php?user=<?= urlencode($player) ?>">
                            <?= htmlspecialchars($player) ?>
                        </a>
                    </td>
                </tr>
                <?php
            }
            ?>
        </table>
        </div>
    <?php
    }
    ?>

    </div>
    <?php
    include 'Includes/footer.inc.php';
    ?>
</div>