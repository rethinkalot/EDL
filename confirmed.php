<?php
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';
// Assuming you have established a connection to the MySQL database

// Fetch the list of games from the test_db
$query = "SELECT time, player1, player2, player1Score, player2Score FROM games";
$query .= " ORDER BY time DESC";
$games = mysqli_query($conn, $query);

$title = 'Games';
include 'Includes/header.inc.php';
?>
<div class="table-wrapper">
<table class="games-table">
  <tr>
    <th>Time</th>
    <th>Winner</th>
    <th>Loser</th>
    <th>Score</th>
  </tr>
<?php
  foreach ($games as $game) {
    $time = $game['time'];
    $winnerScore = $game['winnerScore'];
    $loserScore = $game['loserScore'];
    $i = strrpos($time, ':');
    if ($i >= 0)
      $time = substr($time, 0, $i);
?>
  <tr>
    <td><?php echo htmlspecialchars($time); ?></td>
    <td><?php echo htmlspecialchars($winner); ?></td>
    <td><?php echo htmlspecialchars($loser); ?></td>
    <td><?php echo htmlspecialchars($winnerScore . '/' . $loserScore); ?></td>
  </tr>
<?php
  }
?>
</table>
</div>
<?php
    include 'Includes/footer.inc.php';
?>
