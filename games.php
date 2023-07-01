<?php
session_start();


require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

$query = "SELECT time, mission, winner, loser, winnerKills, forfeit, loserKills FROM games";
$query .= " ORDER BY time DESC";
$games = mysqli_query($conn, $query);

$title = 'Games';
include 'Includes/header.inc.php';
?>

<style>
td, th { width:25% }
</style>

<div class="table-wrapper">
<table class="games-table">

<tr>
    <th>Winner</th>
    <th>Score</th>
    <th>Loser</th>
	  <th>Mission</th>
<?php
foreach ($games as $game) {
    $winner = $game['winner'];
    $winnerKills= $game['winnerKills'];
    $loserKills = $game['loserKills'];
    $loser = $game['loser'];
    $mission = $game['mission'];
  ?>
  <tr>
    <td><?php echo htmlspecialchars($winner); ?></td>
    <td><?php echo htmlspecialchars($winnerKills . '/' . $loserKills); ?></td>
    <td><?php echo htmlspecialchars($loser); ?></td>
    <td><?php echo htmlspecialchars($mission); ?></td>
  </tr>
<?php
  }
?>
</table>
</div>
<?php
    include 'Includes/footer.inc.php';
?>
