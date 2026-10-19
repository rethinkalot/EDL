<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';
require 'Includes/accept.inc.php';



function getValidChallengeTypes()
{
    return ['traditional', 'homevshome', 'randomhome'];
}
function validGameTypes()
{
    return ['Descent', 'Descent2', 'Descent3', 'Ascentroid', 'Overload', 'Geocore'];
}

// Debug: Confirm functions.inc.php is loaded
if (!function_exists('getValidChallengeTypes')) {
    error_log("getValidChallengeTypes() not found in functions.inc.php");
    die("Error: Required functions not loaded. Check Includes/functions.inc.php.");
}

$title = 'AcceptRandom';
include 'Includes/header.inc.php';

$currentUserId = getUserId();
$currentUserName = $_SESSION['useruid'] ?? '';
$challenge = findChallenge($conn);
$validChallengeTypes = getValidChallengeTypes();
$games = validGameTypes();
$errors = [];

if (!$currentUserId || !$currentUserName) {
    error_log("No user ID or username, redirecting to login.php from acceptrandom.php");
    header("Location: login.php");
    exit;
}

if (isset($_POST["accept"]) && $challenge && $challenge['challenged'] == $currentUserId && $challenge['status'] == 'challenged') {
    if (!in_array($challenge['type'], $validChallengeTypes)) {
        $errors[] = "Invalid challenge type.";
    } elseif (!in_array($challenge['gameplayed'], $games)) {
        $errors[] = "Invalid game type.";
    } else {
        if ($challenge['type'] == 'randomhome') {
            // Fetch homes for both players
            $query = "SELECT usersUid, home1, home2, home3, home4, home5 FROM users WHERE usersUid IN (?, ?)";
            $stmt = mysqli_stmt_init($conn);
            if (!mysqli_stmt_prepare($stmt, $query)) {
                $errors[] = "Error preparing query: " . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param($stmt, 'ss', $challenge['challenger'], $challenge['challenged']);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $allHomes = [];
                $challengerHomes = [];
                $challengedHomes = [];
                $defaultHomes = ['Intel2', 'Logic2', 'PowerPro2', 'Purplex2', 'Take2'];
                while ($row = mysqli_fetch_assoc($result)) {
                    $userHomes = [];
                    foreach (['home1', 'home2', 'home3', 'home4', 'home5'] as $homeField) {
                        if (!empty($row[$homeField])) {
                            $userHomes[] = $row[$homeField];
                        }
                    }
                    if (empty($userHomes)) {
                        $userHomes = $defaultHomes;
                        error_log("No custom homes available for {$row['usersUid']}, using default seasonal maps in randomhome.php");
                    }
                    if ($row['usersUid'] == $challenge['challenger']) {
                        $challengerHomes = array_map(function ($home) use ($challenge) {
                            return ['home' => $home, 'owner' => $challenge['challenger']];
                        }, $userHomes);
                    } else {
                        $challengedHomes = array_map(function ($home) use ($challenge) {
                            return ['home' => $home, 'owner' => $challenge['challenged']];
                        }, $userHomes);
                    }
                    $allHomes = array_merge($allHomes, $userHomes);
                }
                mysqli_stmt_close($stmt);

                if (empty($allHomes)) {
                    $errors[] = "No valid homes available!";
                    error_log("No valid homes available for randomhome in acceptrandom.php");
                } else {
                    // Select a random home
                    $randomHome = $allHomes[array_rand($allHomes)];
                    // Determine owner
                    $owner = array_reduce(array_merge($challengerHomes, $challengedHomes), function ($carry, $item) use ($randomHome) {
                        return ($item['home'] == $randomHome) ? $item['owner'] : $carry;
                    }, '');
                    $ownerLabel = ($owner == $challenge['challenger']) ? $challenge['challenger'] : $challenge['challenged'];
                    // Update challenge with selected home
                    $query = "UPDATE challenges SET mission = ?, status = 'accepted' WHERE id = ?";
                    $stmt = mysqli_stmt_init($conn);
                    if (mysqli_stmt_prepare($stmt, $query)) {
                        mysqli_stmt_bind_param($stmt, 'si', $randomHome, $challenge['id']);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                        // Send Discord notification
                        $message = htmlspecialchars($challenge['challenged']) . " has accepted " . htmlspecialchars($challenge['challenger']) . "'s randomhome challenge. " . htmlspecialchars($ownerLabel) . "'s home " . htmlspecialchars($randomHome) . " was selected at random to be played.";
                        sendDiscordNotification($message);
                        error_log("Randomhome accepted, redirecting to randomhome.php from acceptrandom.php");
                        header("Location: randomhome.php");
                        exit;
                    } else {
                        $errors[] = "Error updating challenge: " . mysqli_error($conn);
                    }
                }
            }
        } else {
            // Handle RandomHome Acceptance
            $query = "UPDATE challenges SET status = 'accepted' WHERE id = ?";
            $stmt = mysqli_stmt_init($conn);
            if (!mysqli_stmt_prepare($stmt, $query)) {
                $errors[] = "Error preparing query: " . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param($stmt, 'i', $challenge['id']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $message = htmlspecialchars($currentUserName) . " has accepted your " . htmlspecialchars($challenge['type']) . " challenge.";
                sendDiscordNotification($message);
                if ($challenge['type'] == 'randomhome') {
                    error_log("RandomHome accepted, redirecting to randomhome.php from acceptrandom.php");
                    header("Location: randomhome.php");
                }
                exit;
            }
        }
    }
}

if (isset($_POST["confirm"]) && $challenge && $challenge['winner'] == $currentUserId && $challenge['status'] == 'reported') {
    try {
        confirmChallenge($conn, $challenge);
        error_log("Game confirmed, redirecting to games.php from acceptrandom.php");
        header("Location: games.php");
        exit;
    } catch (Exception $e) {
        $errors[] = "Error confirming game: " . $e->getMessage();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received in randomhome.php, action: " . json_encode($_POST));
    if (isset($_POST['loserComment'])) {
        $loserComment = $_POST['loserComment'] ?? '';
        storeLoserComment($conn, $challenge['id'], $loserComment);
        exit;
    }
    if (isset($_POST["kickback"]) && $challenge['status'] == 'reported') {
        setChallengeStatus($conn, $challenge['id'], 'accepted');
        $message = htmlspecialchars($currentUserName) . " has kicked back the " . htmlspecialchars($challenge['type']) . " challenge result.";
        sendDiscordNotification($message);
        $redirectPage = ($challenge['type'] == 'randomhome') ? 'randomhome.php' : 'randomhome.php';
        error_log("Kicking back, redirecting to $redirectPage from acceptrandom.php");
        header("Location: $redirectPage");
        exit;
    }
    if (isset($_POST["confirm"]) && $challenge['winner'] == $currentUserId && $challenge['status'] == 'reported') {
        $winnerComment = $_POST['winnerComment'] ?? '';
        // Update challenges with winnerComment and status
        $query = "UPDATE challenges SET status = 'confirmed', winnerComment = ? WHERE id = ?";
        $stmt = mysqli_stmt_init($conn);
        if (mysqli_stmt_prepare($stmt, $query)) {
            mysqli_stmt_bind_param($stmt, 'si', $winnerComment, $challenge['id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            error_log("Prepare failed for updating challenges: " . mysqli_error($conn));
            exit;
        }
        // Call confirmGame to set status (redundant but included for function usage)
        confirmGame($conn, $challenge['id']);
        // Check if game already exists in games table
        $query = "SELECT id FROM games WHERE id = ?";
        $stmt = mysqli_stmt_init($conn);
        if (mysqli_stmt_prepare($stmt, $query)) {
            mysqli_stmt_bind_param($stmt, 'i', $challenge['id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $exists = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            if (!$exists) {
                // Insert into games
                $query = "INSERT INTO games (id, challenger, challenged, type, status, gameplayed, mission, mission1, mission2, winner, winnerKills, winnerDeaths, loser, loserKills, loserDeaths, combinedSuicides, winnerComment, loserComment, winner1Kills, winner1Deaths, loser1Kills, loser1Deaths, winner2Kills, winner2Deaths, loser2Kills, loser2Deaths, winner1, winner2, loser1, loser2, gamesCombined, score)
                          SELECT id, challenger, challenged, type, status, gameplayed, mission, mission1, mission2, winner, winnerKills, winnerDeaths, loser, loserKills, loserDeaths, combinedSuicides, ?, loserComment, winner1Kills, winner1Deaths, loser1Kills, loser1Deaths, winner2Kills, winner2Deaths, loser2Kills, loser2Deaths, winner1, winner2, loser1, loser2, gamesCombined, score
                          FROM challenges WHERE id = ?";
                $stmt = mysqli_stmt_init($conn);
                if (mysqli_stmt_prepare($stmt, $query)) {
                    mysqli_stmt_bind_param($stmt, 'si', $winnerComment, $challenge['id']);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                } else {
                    error_log("Insert into games failed: " . mysqli_error($conn));
                }
            }
        }

        // Update ranks
        updateRanks($conn, $challenge['winner'], $challenge['loser']);
        // Update winner's stats and streaks
        updateStats($conn, $challenge['winner'], [
            'wins' => 1,
            'kills' => $challenge['winnerKills'],
            'deaths' => $challenge['loserKills']
        ]);
        updateStreaks($conn, $challenge['winner'], true);
        // Update loser's stats and streaks
        updateStats($conn, $challenge['loser'], [
            'losses' => 1,
            'kills' => $challenge['loserKills'],
            'deaths' => $challenge['winnerKills']
        ]);
        updateStreaks($conn, $challenge['loser'], false);

        $discordWebhookUrl = 'YOUR_WEBHOOK_URL_HERE';
        // Send Discord notification for confirmed match
        $message = htmlspecialchars($currentUserName) . " confirmed the match! \n{$challenge['winner']} - {$challenge['winnerKills']} - $winnerComment\n{$challenge['loser']} - {$challenge['loserKills']} - {$challenge['loserComment']}\nMission - {$challenge['mission']}\nGame - {$challenge['gameplayed']}";
        sendDiscordNotification($message);
        $confirmed = 1;
    }

}    if (isset($_POST["accept"]) && $challenge['challenged'] == $currentUserId && $challenge['status'] == 'challenged') {
        $message = htmlspecialchars($currentUserName) . " has accepted your " . htmlspecialchars($challenge['type']) . " Challenge.";
        sendDiscordNotification($message); 
    
           if ($challenge['type'] == 'randomhome') 
                $mission = $challenge['mission'] ?? ''; {
         

                // Handle Traditional, Homevs Home, and randomHome
                $query = "UPDATE challenges SET mission = ?, status = 'accepted' WHERE id = ?";
                $stmt = mysqli_stmt_init($conn);
                mysqli_stmt_prepare($stmt, $query);
                mysqli_stmt_bind_param($stmt, 'si', $mission, $challenge['id']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                error_log("randomhome accepted, redirecting to randomhome.php from acceptrandom.php");
                header("Location: randomhome.php"); 



              
    
        if (isset($_POST["decline"]) && $challenge['challenged'] == $currentUserId && $challenge['status'] == 'challenged') {
        setChallengeStatus($conn, $challenge['id'], 'cancelled');
        $message = htmlspecialchars($currentUserName) . " has Declined your " . htmlspecialchars($challenge['type']) . " Challenge.";
        sendDiscordNotification($message);
        error_log("Challenge declined, redirecting to challenge.php from acceptrandom.php");
        header("Location: challenge.php");
        exit;
    }
        if (isset($_POST["cancel"]) && $challenge['challenger'] == $currentUserId && $challenge['status'] == 'challenged') {
        setChallengeStatus($conn, $challenge['id'], 'cancelled');
        error_log("Challenge cancelled, redirecting to challenge.php from acceptrandom.php");
        header("Location: challenge.php");
        exit;
    }
}
}
?>

<div class="container">
    <style>
        input[type="submit"] {
            color: #a37941;
        }

        table {
            width: 30%;
        }

        td,
        th {
            width: 50%;
        }

        p {
            margin: 1em 0;
        }

        .error-message {
            color: red;
        }

        table.challenge-table {
            border-bottom: 1px solid #ccc;
            /* Bottom border for reported challenge table */
        }

        /* Alternative: Border per row */
        /* table.challenge-table tr { border-bottom: 1px solid #ccc; } */
        /* Alternative: Border for comment cell */
        /* table.challenge-table td:last-child { border-bottom: 1px solid #ccc; } */
    </style>





    <?php
    if (isset($error_message)) {
        echo "<p class='error-message'>" . htmlspecialchars($error_message) . "</p>";
    }
    if (isset($confirmed)) {
        echo "<p>Game Confirmed.</p>";
    } else if (isset($kickedback)) {
        echo "<p>Game Kicked Back.</p>";
    } else if ($challenge['challenger'] == $currentUserId && $challenge['status'] == 'challenged') {
        echo "<p>Waiting for {$challenge['challenged']} to accept your Challenge.</p>";
        echo "<form method='post'>";
        echo "<input type='submit' name='cancel' value='Cancel'>";
        echo "</form>";
    } else if ($challenge['challenged'] == $currentUserId && $challenge['status'] == 'challenged') {
        echo "<p><b>$currentUserName</b> New challenge from {$challenge['challenger']}</p>";
        echo "<form method='post'>";
    } else if ($challenge['type'] == 'randomhome' && $challenge['status'] == 'challenged') {
            echo "<p>One player's home will be selected at random to be the playing venue.</p>";
        }
        
     if ($challenge['status'] == 'reported') {
        error_log("Reported challenge, winner: " . ($challenge['winner'] ?? 'null') . ", currentUserId: $currentUserId");
        echo "<p>" . htmlspecialchars($challenge['loser'] ?? 'Unknown') . " reported: </p>";

}   if (isset($_POST["decline"]) && $challenge && $challenge['challenged'] == $currentUserId && $challenge['status'] == 'challenged') {
    try {
        setChallengeStatus($conn, $challenge['id'], 'declined');
        $message = htmlspecialchars($currentUserName) . " has declined your " . htmlspecialchars($challenge['type']) . " challenge.";
        sendDiscordNotification($message);
        error_log("Challenge declined, redirecting to challenge.php from acceptrandom.php");
        header("Location: challenge.php");
        exit;
    } catch (Exception $e) {
        $errors[] = "Error declining challenge: " . $e->getMessage();
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

<?php
if ($challenge['status'] == 'challenged') {
?>
    <div class="namesize">
        <br />
        <br />
        <p><?php echo htmlspecialchars($currentUserName); ?></p>
        <h2>Accept Challenges</h2>
    </div>
   <?php
}  
?>
    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($challenge && $challenge['challenged'] == $currentUserId && $challenge['status'] == 'challenged'): ?>
        <table class="games-table">
            <tr>
                <th>Challenger</th>
                <td><?php echo htmlspecialchars($challenge['challenger']); ?></td>
            </tr>
            <tr>
                <th>Game</th>
                <td><?php echo htmlspecialchars($challenge['gameplayed']); ?></td>
            </tr>
            <tr>
                <th>Mission</th>
                <td><?php echo htmlspecialchars($challenge['mission'] ?: 'Not set'); ?></td>
            </tr>
            <tr>
                <th>Type</th>
                <td><?php echo htmlspecialchars($challenge['type']); ?></td>
            </tr>
        </table>
        <p>Accept this challenge from <?php echo htmlspecialchars($challenge['challenger']); ?>?</p>
        <form method="post" action="accept.php">
            <input type="submit" name="accept" value="Accept">
            <input type="submit" name="decline" value="Decline">
        </form>
    <?php elseif ($challenge && $challenge['winner'] == $currentUserId && $challenge['status'] == 'reported'): ?>
        <p>Confirm this win against <?php echo htmlspecialchars($challenge['loser']); ?>!</p>
        <table class="games-table">
            <tr>
                <th>Game</th>
                <td><?php echo htmlspecialchars($challenge['gameplayed']); ?></td>
            </tr>
            <tr>
                <th>Mission</th>
                <td><?php echo htmlspecialchars($challenge['mission']); ?></td>
            </tr>
            <tr>
                <th>Winner</th>
                <td><?php echo htmlspecialchars($challenge['winner']); ?></td>
            </tr>
            <tr>
                <th>Kills</th>
                <td><?php echo htmlspecialchars($challenge['winnerKills']); ?></td>
            </tr>
            <tr>
                <th>Loser</th>
                <td><?php echo htmlspecialchars($challenge['loser']); ?></td>
            </tr>
            <tr>
                <th>Kills</th>
                <td><?php echo htmlspecialchars($challenge['loserKills']); ?></td>
            </tr>
            <tr>
                <th>Comment</th>
                <td><?php echo htmlspecialchars($challenge['loserComment'] ?: 'None'); ?></td>
            </tr>
        </table>
        <form method="post">
            <input type="submit" name="confirm" value="Confirm">
        </form>
    <?php else: ?>
        <p>No challenges to accept or confirm.</p>
        <p><a href="challenge.php">Create a Challenge</a></p>
    <?php endif; ?>
</div>

<?php include 'Includes/footer.inc.php'; ?>