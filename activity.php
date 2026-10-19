<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

$title = 'Status';
include 'Includes/header.inc.php';

// Get logged-in user ID
$userId = getUserId();
if (!$userId) {
    header("Location: login.php");
    exit;
}

// Fetch active challenges
$query = "SELECT id, challenger, challenged, type, status, gameplayed 
          FROM challenges 
          WHERE (challenger = ? OR challenged = ?) AND status IN ('challenged', 'accepted', 'reported')";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'ss', $userId, $userId);
mysqli_stmt_execute($stmt);
$challenges = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);



?>

<div class="namesize">    
 <br />
 <br />
 <?php
 $userId = getUserId(); // Store the result to avoid calling getUserId() twice
 if ($userId !== null && !empty($userId)) {
   echo $userId; // Echo raw user ID (matching your first echo)
   ?><!-- Escaped user ID (matching your second echo) -->
<?php
 } else {
   echo "Please login!"; // Display for non-logged-in users
 }

 ?>
    <br />
    <p>Welcom to Status!<p></table>
    <br />
    <h2>Your Challenges</h2>
    <?php if (mysqli_num_rows($challenges) == 0): ?>
        <p style="font-size: 20px; font-style: italic; font-weight: bold;">No active challenges.</p>
    <?php endif; ?>
</div>
    <div class=container>
    <style>
        div.container { display: block; text-align: center; }
        div.container table.games-table { display: inline-table; width: auto !important; margin: 0 auto; border-collapse: collapse; text-align: left; }
        table.games-table th, table.games-table td { width: auto !important; padding: 4px 14px; white-space: nowrap; text-align: left; }
        table.games-table td a { color: #815108 !important; }
    </style>
    
    <table class="games-table">
        <?php if (mysqli_num_rows($challenges) > 0): ?>
        
            <tr>
                <th>Opponent</th>
                <th>Game</th>
                <th>Type</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php while ($challenge = mysqli_fetch_assoc($challenges)): ?>
                <tr>
                    <td>


                        <?php 
                         
                        $opponent = ($challenge['challenger'] == $userId) ? $challenge['challenged'] : $challenge['challenger'];
                        echo htmlspecialchars($opponent);
                        ?>
                    </td>
                    <td><?= htmlspecialchars($challenge['gameplayed']) ?></td>
                    <td><?= htmlspecialchars($challenge['type']) ?></td>
                    <td><?= htmlspecialchars($challenge['status']) ?></td>
                    <td>
                        <?php if ($challenge['status'] == 'challenged' && $challenge['challenged'] == $userId): ?>
                            <a href="accept.php?id=<?= $challenge['id'] ?>">Accept/Decline</a>
                        <?php elseif ($challenge['status'] == 'accepted'): ?>
                            <a href="<?= $challenge['type'] == 'homevshome' ? 'homevshome.php?id=' . $challenge['id'] : 'report.php?id=' . $challenge['id'] ?>">Report</a>
                        <?php elseif ($challenge['status'] == 'reported'): ?>
                            <a href="accept.php?id=<?= $challenge['id'] ?>">Confirm/Kick Back</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>

<?php include 'Includes/footer.inc.php'; ?>