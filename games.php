<?php
session_start();
$title = 'Games';
include 'Includes/header.inc.php';
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

// SQL Query pulling from games and challenges tables
$query = "SELECT games.gameplayed, games.mission, games.winner, games.loser, games.winnerKills, games.winnerDeaths, games.loserKills, games.loserDeaths, 
                 games.loser1Kills, games.loser1Deaths, games.loser2Kills, games.loser2Deaths, games.winner1Kills, games.winner1Deaths, games.winner2Kills, games.winner2Deaths, 
                 challenges.loserComment, challenges.winnerComment, challenges.gamesCombined, challenges.score
          FROM games
          LEFT JOIN challenges ON games.id = challenges.id
          ORDER BY games.id DESC";

$games = mysqli_query($conn, $query);
?>
<div class=container>
   <style>
        
        table.header {
            margin: 0 auto; /* Center table */
            width: 10%; /* Fixed width for header-like appearance */
            text-align: center; /* Center text */
            border: 1px solid #11111; /* Header-like border */
            background:  rgb(20, 20, 20); /* Subtle background */
            border-color: rgb(64, 78, 93)
            border-bottom: 1px solid #32233; /* Bottom border for reported challenge table */  
            .winner-text, .loser-text { font-size: 20px; }
            .comment-text { font-size: 15px; }
            word-wrap: break-word;                
            max-width: 15px; /* Narrower colum  ns; change to 15px if intended */
    
    }

        
        table.header th:nth-child(3), table.header td:nth-child(3) { padding-left: 2em; }
        table.header th:nth-child(4), table.header td:nth-child(4) { padding-left: 1em; position: relative; left: -2em; }
        table.header th:nth-child(5), table.header td:nth-child(5) { position: relative; left: -1em; }
        table.header .comment-text { font-size: 15px; /* Smaller text for comments */
            word-wrap: break-word;
             max-width: 120px;   
           
        }    
        </style>
<div class="namesize">
    <br />
    <br />
    <?php echo htmlspecialchars(getUserId()); ?>
    <br />
    <p>Welcome to Games History!</p>
    <br />
    <br />
    <h2>Seasonal Homes</h2>
    <p>Season 3 homes: Calamity, Carbon, Crimson, Cronos, Cryptic</p>
    <br />
    <div class=container>
        <table class="header">
        
            
                <tr>
                    <th>Winner</th>
                    <th>The-
                        Scores</th>
                    <th>Loser</th>
                    <th>Mission</th>
                    <th>The
                        Game</th>

                    <th>LoserComment</th>
                    <th>WinnerComment</th>                   
                </tr>           
            
                <?php while ($game = mysqli_fetch_assoc($games)) {
                  $winner = htmlspecialchars($game['winner'] ?? '');
                  $winnerKills = htmlspecialchars($game['winnerKills'] ?? '');
                  $winnerDeaths = htmlspecialchars($game['winnerDeaths'] ?? '');
                  $loserKills = htmlspecialchars($game['loserKills'] ?? '');
                  $loserDeaths = htmlspecialchars($game['loserDeaths'] ?? '');
                  $winner1Kills = htmlspecialchars($game['winner1Kills'] ?? '');
                  $loser1Kills = htmlspecialchars($game['loser1Kills'] ?? '');
                  $winner2Kills = htmlspecialchars($game['winner2Kills'] ?? '');
                  $loser2Kills = htmlspecialchars($game['loser2Kills'] ?? '');
                  $loser = htmlspecialchars($game['loser'] ?? '');
                  $mission = htmlspecialchars($game['mission'] ?? '');
                  $gameplayed = htmlspecialchars($game['gameplayed'] ?? '');
                  $winner1 = htmlspecialchars($game['winner1'] ?? '');
                  $winner2 = htmlspecialchars($game['winner2'] ?? '');
                  $loserComment = htmlspecialchars($game['loserComment'] ?? '');
                  $winnerComment = htmlspecialchars($game['winnerComment'] ?? '');
                  $gamesCombined = htmlspecialchars($game['gamesCombined'] ?? '');
                  $score_display = "$winnerKills-$loserKills";
                  $score = "$winnerKills-$loserKills"
                  ?>
                <tr>
                    <td class="winner-text"><a href="profile.php?user=<?= urlencode($winner) ?>"><?= $winner ?></a></td>
                    <th><?= $score_display ?></th>
                    <td class="loser-text"><a href="profile.php?user=<?= urlencode($loser) ?>"><?= $loser ?></a></td>
                    <td><?= $mission ?></td>
                    <td><?= $gameplayed ?></td>

                    <td><?= $loserComment ?></td>
                    <td><?= $winnerComment ?></td>
                    
                </tr>
                <?php } ?>
            
        </table>
    </div>
</div>

<?php include 'Includes/footer.inc.php'; ?>