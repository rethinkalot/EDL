<?php 
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

$query = "SELECT player, rank, wins, losses, kills, deaths, streaks FROM ranks ORDER BY rank";
$rankRows = mysqli_query($conn, $query);

$title = 'Ranks';
include 'Includes/header.inc.php';
?>
<div class="table-wrapper">
<table class="games-table">
  <tr>
    <th>Rank</th>
    <th>Pilot</th>
    <th>KD%</th>
	<th>Streaks</th>
    <th>Wins</th>
	
	
   </tr>
<?php
  foreach ($rankRows as $rankRow) {
    $player = $rankRow['player'];
    $rank = $rankRow['rank'];
    $kdr = $rankRow['deaths'] ? $rankRow['kills'] / $rankRow['deaths'] : 0;
	$streaks = $rankRow['streaks'];
    $wins = $rankRow['wins'];
    $loss = $rankRow['losses'];
	
    ?>
            
    <tr>
      <td><?php echo $rank; ?></td>
      <td><?php echo htmlspecialchars($player); ?></td>
      <td><?php echo is_nan($kdr) ? '' : number_format($kdr, 2); ?></td>
	  <td><?php echo $streaks; ?></td>
      <td><?php echo $wins . '/' . $loss; ?></td>
	  
    </tr>
  <?php
  }
  ?>
</table>
</div>
<?php
    include 'Includes/footer.inc.php';
?>
