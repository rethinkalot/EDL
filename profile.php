<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

// Get user from GET parameter or show error
if (!isset($_GET['user']) || empty($_GET['user'])) {
    die("Error: No user specified.");
}
$userId = $_GET['user'];

// Fetch user's rank data (filter by user)
$query = "SELECT player, rank, wins, losses, kills, deaths, streaks FROM ranks WHERE player = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $userId);
mysqli_stmt_execute($stmt);
$rankRows = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Fetch user's rank data (filter by user)
$query = "SELECT player, rank, wins, losses, kills, deaths, streaks FROM ranks2 WHERE player = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $userId);
mysqli_stmt_execute($stmt);
$rank2Rows = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Load user data from the database
$query = "SELECT home1, home2, home3, home4, home5, game, location, quote FROM users WHERE usersUid = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$userData = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$userData) {
    die("Error: User not found.");
}

$home1 = $userData['home1'] ?? '';
$home2 = $userData['home2'] ?? '';
$home3 = $userData['home3'] ?? '';
$home4 = $userData['home4'] ?? '';
$home5 = $userData['home5'] ?? '';
$game = $userData['game'] ?? '';
$location = $userData['location'] ?? '';
$quote = $userData['quote'] ?? '';

$title = 'Profile';

// Fetch detailed statistics
// Total Games Played
$query = "SELECT COUNT(*) as total_games FROM games WHERE challenger = ? OR challenged = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'ss', $userId, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_games = mysqli_fetch_assoc($result)['total_games'] ?? 0;
mysqli_stmt_close($stmt);

// Home Games (HomeVsHome and RandomHome)
$query = "SELECT 
    SUM(CASE WHEN winner = ? THEN 1 ELSE 0 END) as home_wins,
    SUM(CASE WHEN loser = ? THEN 1 ELSE 0 END) as home_losses
    FROM games WHERE (challenger = ? OR challenged = ?) AND type IN ('HomeVsHome', 'RandomHome')";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'ssss', $userId, $userId, $userId, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$home_games = mysqli_fetch_assoc($result);
$home_wins = $home_games['home_wins'] ?? 0;
$home_losses = $home_games['home_losses'] ?? 0;
$home_total = $home_wins + $home_losses;
mysqli_stmt_close($stmt);

// Game-specific Wins/Losses (Descent, Descent2, etc.)
$games_list = ['Descent', 'Descent2', 'Descent3', 'Ascentroid', 'Geocore', 'Overload'];
$game_stats = [];
foreach ($games_list as $game_type) {
    $query = "SELECT 
        SUM(CASE WHEN winner = ? THEN 1 ELSE 0 END) as wins,
        SUM(CASE WHEN loser = ? THEN 1 ELSE 0 END) as losses
        FROM games WHERE (challenger = ? OR challenged = ?) AND gameplayed = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'sssss', $userId, $userId, $userId, $userId, $game_type);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats = mysqli_fetch_assoc($result);
    $game_stats[$game_type] = [
        'wins' => $stats['wins'] ?? 0,
        'losses' => $stats['losses'] ?? 0
    ];
    mysqli_stmt_close($stmt);
}

// Unique Maps
$query = "SELECT COUNT(DISTINCT mission)  as unique_maps 
    FROM games WHERE challenger = ? OR challenged = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'ss', $userId, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$unique_maps = mysqli_fetch_assoc($result)['unique_maps'] ?? 0;
mysqli_stmt_close($stmt);

// Highest Win Streak
$query = "SELECT winner FROM games WHERE challenger = ? OR challenged = ? ORDER BY id";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'ss', $userId, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$highest_streak = 0;
$current_streak = 0;
while ($game = mysqli_fetch_assoc($result)) {
    if ($game['winner'] === $userId) {
        $current_streak++;
        $highest_streak = max($highest_streak, $current_streak);
    } else {
        $current_streak = 0;
    }
}
mysqli_stmt_close($stmt);

$title = 'Profile';
include 'Includes/header.inc.php';
?>
<div class="container">

        <style>
        table.header {
            margin: 0 auto; /* Center table */
            width: 10%; /* Restored back to your custom compact styling anchor */
            text-align: center; /* Center text */
            border: 1px solid #111111; 
            background: #111111; /* Darkened background color */
            border-color: rgb(64, 78, 93);
            border-bottom: 1px solid #332233; 
        }
        /* Forces the top header blocks AND all data cells inside to use the exact same dark gray */
        table.header th, table.header td {
            background-color: #111111 !important;
        }
        .winner-text, .loser-text { 
            font-size: 19px; 
        }
        .comment-text { 
            font-size: 13px;
            word-wrap: break-word;                
            max-width: 120px; 
        }
    </style>

    <div class="namesize">
        <br />
        <br />
        <h2><?php echo htmlspecialchars($userId); ?>'s Profile!</h2>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($location); ?></p>    
        <p><strong>Quote:</strong> <?php echo htmlspecialchars($quote); ?></p>
    </div>

    <?php while ($rankRow = mysqli_fetch_assoc($rankRows)): ?>
    <div class="namesize">
        <br><h3>Season 3 Current Stats</h3>
    </div>
   
    <table class="header">
        <tr><th>Rank</th><td><?php echo htmlspecialchars($rankRow['rank']); ?></td></tr>            
        <tr><th>Wins</th><td><?php echo htmlspecialchars($rankRow['wins']); ?></td>
        <th>Losses</th><td><?php echo htmlspecialchars($rankRow['losses']); ?></td></tr>
        <tr><th>Kills</th><td><?php echo htmlspecialchars($rankRow['kills']); ?></td>
        <th>Deaths</th><td><?php echo htmlspecialchars($rankRow['deaths']); ?></td></tr>
        <tr><th>Streaks</th><td><?php echo htmlspecialchars($rankRow['streaks']); ?></td></tr>
    </table>
    <?php endwhile; ?>

        <div class="namesize">
        <br><h4>Detailed Statistics</h4>
    </div>
        
        <div class="namesize">
        <br><h4>Detailed Statistics</h4>
    </div>
        
    <table class="header">
        <!-- Peak Career Achievements Header Row -->
        <tr>
            <td colspan="2">Total Career Matches Played</td>
            <th colspan="1"><?php echo htmlspecialchars($total_games); ?></th>
            <td colspan="2">Highest Win Streak</td>
            <th colspan="1"><?php echo htmlspecialchars($highest_streak); ?></th>
        </tr>           
        <tr>
            <td>Descent Wins</td><th><?php echo htmlspecialchars($game_stats['Descent']['wins']); ?></th>
            <td>Descent Losses</td><th><?php echo htmlspecialchars($game_stats['Descent']['losses']); ?></th>
            <td>Descent2 Wins</td><th><?php echo htmlspecialchars($game_stats['Descent2']['wins']); ?></th>
        </tr>
        <tr>
            <td>Descent2 Losses</td><th><?php echo htmlspecialchars($game_stats['Descent2']['losses']); ?></th>
            <td>Descent3 Wins</td><th><?php echo htmlspecialchars($game_stats['Descent3']['wins']); ?></th>
            <td>Descent3 Losses</td><th><?php echo htmlspecialchars($game_stats['Descent3']['losses']); ?></th>
        </tr>
        <tr>
            <td>Ascentroid Wins</td><th><?php echo htmlspecialchars($game_stats['Ascentroid']['wins']); ?></th>
            <td>Ascentroid Losses</td><th><?php echo htmlspecialchars($game_stats['Ascentroid']['losses']); ?></th>
            <td>Geocore Wins</td><th><?php echo htmlspecialchars($game_stats['Geocore']['wins']); ?></th>
        </tr>
        <tr>
            <td>Geocore Losses</td><th><?php echo htmlspecialchars($game_stats['Geocore']['losses']); ?></th>
            <td>Overload Wins</td><th><?php echo htmlspecialchars($game_stats['Overload']['wins']); ?></th>
            <td>Overload Losses</td><th><?php echo htmlspecialchars($game_stats['Overload']['losses']); ?></th>
        </tr>
        <tr>
            <td>Home Games Wins</td><th><?php echo htmlspecialchars($home_wins); ?></th>
            <td>Home Games Losses</td><th><?php echo htmlspecialchars($home_losses); ?></th>
            <td>Home Games Total</td><th><?php echo htmlspecialchars($home_total); ?></th>
        </tr>
        <!-- Variety Tracking Anchored Comfortably at the Bottom -->
        <tr>
            <td colspan="3">Unique Maps Explored</td>
            <th colspan="3"><?php echo htmlspecialchars($unique_maps); ?></th>
        </tr>
    </table>



    <div class="namesize"><br>
        <h5>Home Levels</h5>
    </div>
    <table class="header">
        <tr><th>Home1</th><td><?php echo htmlspecialchars($home1); ?></td>
        <th>Home2</th><td><?php echo htmlspecialchars($home2); ?></td>
        <th>Home3</th><td><?php echo htmlspecialchars($home3); ?></td>
        <th>Home4</th><td><?php echo htmlspecialchars($home4); ?></td>
        <th>Seasonal</th><td><?php echo htmlspecialchars($home5); ?></td></tr>
    </table>
		<div class="container">
	    <div class="namesize"><br>
        <h6>Finished Season 2</h6>
    </div>
    <table class="games-table" style="border-collapse: collapse; width: 40%; margin: 0 auto;">
        <style>
            .games-table th, .games-table td { border: 1px solid #404e5d; padding: 12px; text-align: center; }
            .games-table th { text-align: center; background-color: #111111; }
            .games-table td { background-color: #111111; } /* Darkens the background behind the numbers */
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
        foreach ($rank2Rows as $rankRow2) {
          $player = $rankRow2['player'];
          $rank = $rankRow2['rank'];
          $wins = $rankRow2['wins'];
          $losses = $rankRow2['losses'];
          $kills = $rankRow2['kills'];
          $deaths = $rankRow2['deaths'];
          $streaks = $rankRow2['streaks'];
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

    <div class="namesize"><br><h6>Match History</h6></div>
    <table class="header">
        <tr>
            <th>Winner</th>
            <th>Score</th>
            <th>The Loser</th>
            <th>Mission</th>            
            <th>Loser Comment</th>
            <th>Winner Comment</th>
        </tr>
        <?php
        // Query games for the user
        $query = "SELECT id, winner, winnerKills, loser, loserKills, mission, mission1, mission2, loserComment, winnerComment FROM games WHERE challenger = ? OR challenged = ? ORDER BY id DESC";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'ss', $userId, $userId);
        mysqli_stmt_execute($stmt);
        $games = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);

        while ($game = mysqli_fetch_assoc($games)) {
            $winner = htmlspecialchars($game['winner'] ?? '');
            $winnerKills = htmlspecialchars($game['winnerKills'] ?? '');
            $loserKills = htmlspecialchars($game['loserKills'] ?? '');
            $loser = htmlspecialchars($game['loser'] ?? '');
            $mission = htmlspecialchars($game['mission'] ?? '');
            $loserComment = htmlspecialchars($game['loserComment'] ?? '');
            $winnerComment = htmlspecialchars($game['winnerComment'] ?? '');
            $score_display = "$winnerKills-$loserKills";
            ?>
            <tr>
                <td class="winner-text"><a href="profile.php?user=<?= urlencode($winner) ?>"><?= $winner ?></a></td>
                <td class="comment-text"><?= $score_display ?></td>
                <td class="loser-text"><a href="profile.php?user=<?= urlencode($loser) ?>"><?= $loser ?></a></td>
                <td class="comment-text"><?= $mission ?></td>
                <td class="comment-text"><?= $loserComment ?></td>
                <td class="comment-text"><?= $winnerComment ?></td>
            </tr>
        <?php } ?>
    </table>
</div>
<?php include 'Includes/footer.inc.php'; ?>

