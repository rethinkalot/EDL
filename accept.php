<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';
require 'Includes/accept.inc.php';




// Check for an active, accepted challenge
$challenge = findChallenge($conn);



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

// Retrieve the challenge for this player
$challenge = findChallenge($conn);
if (!$challenge) {
    error_log("No challenge found, redirecting to challenge.php from accept.php");
    header("Location: challenge.php");
    exit;
}

// Get current user's ID and fetch their data
$currentUserId = getUserId();
if (!$currentUserId) {
    error_log("No user ID, redirecting to login.php from accept.php");
    header("Location: login.php");
    exit;
}
$query = "SELECT usersUid, home1, home2, home3, home4, home5 FROM users WHERE usersUid = ?";
$stmt = mysqli_stmt_init($conn);
if (!mysqli_stmt_prepare($stmt, $query)) {
    echo "<p>Error preparing query: " . mysqli_error($conn) . "</p>";
    exit;
}
mysqli_stmt_bind_param($stmt, 's', $currentUserId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$userData = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$userData) {
    echo "<p>No user data found for ID: " . htmlspecialchars($currentUserId) . "</p>";
    exit;
}

$currentUserName = $userData['usersUid'] ?? 'Unknown';

// Load homes for the current user (challenged)
$challengedHomes = [];
$possibleHomes = [
    $userData['home1'] ?? '',
    $userData['home2'] ?? '',
    $userData['home3'] ?? '',
    $userData['home4'] ?? '',
    $userData['home5'] ?? ''
];
foreach ($possibleHomes as $home) {
    if (!empty($home) && $home !== '') {
        $challengedHomes[] = $home;
    }
}
if (empty($challengedHomes)) {
    $challengedHomes[] = "No homes set";
}

// Determine the other player's ID and name for notifications
$otherPlayerId = ($challenge['challenger'] == $currentUserId) ? $challenge['challenged'] : $challenge['challenger'];
$query = "SELECT usersUid FROM users WHERE usersUid = ?";
$stmt = mysqli_stmt_init($conn);
if (mysqli_stmt_prepare($stmt, $query)) {
    mysqli_stmt_bind_param($stmt, 's', $otherPlayerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $otherPlayerData = mysqli_fetch_assoc($result);
    $otherPlayerName = $otherPlayerData['usersUid'] ?? 'Unknown';
    mysqli_stmt_close($stmt);
} else {
    $otherPlayerName = 'Unknown';
}

$title = 'Accept';
include 'Includes/header.inc.php';



// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received in accept.php, action: " . json_encode($_POST));
    if (isset($_POST['loserComment'])) {
        $loserComment = $_POST['loserComment'] ?? '';
        storeLoserComment($conn, $challenge['id'], $loserComment);
        exit;
    }
    if (isset($_POST["kickback"]) && $challenge['status'] == 'reported') {
        setChallengeStatus($conn, $challenge['id'], 'accepted');
        $message = htmlspecialchars($currentUserName) . " has kicked back the " . htmlspecialchars($challenge['type']) . " challenge result.";
        sendDiscordNotification($message);
        $redirectPage = ($challenge['type'] == 'homevshome') ? 'homevshome.php' : 'report.php';
        error_log("Kicking back, redirecting to $redirectPage from accept.php");
        header("Location: $redirectPage");
        exit;
    }
    if (isset($_POST["confirm"]) && $challenge['winner'] == $currentUserId && $challenge['status'] == 'reported') {
        confirmChallenge($conn, $challenge);
        $confirmed = 1;
    }
    if (isset($_POST["accept"]) && $challenge['challenged'] == $currentUserId && $challenge['status'] == 'challenged') {
        $message = htmlspecialchars($currentUserName) . " has accepted your " . htmlspecialchars($challenge['type']) . " Challenge.";
        sendDiscordNotification($message);
        if ($challenge['type'] == 'homevshome') {
            $mission2 = $_POST['mission2'] ?? '';
            if (!in_array($mission2, $challengedHomes)) {
                $error_message = "Please select a valid home!";
            } else {

                // Handle Traditional, Homevs Home, 
                $query = "UPDATE challenges SET mission2 = ?, status = 'accepted' WHERE id = ?";
                $stmt = mysqli_stmt_init($conn);
                mysqli_stmt_prepare($stmt, $query);
                mysqli_stmt_bind_param($stmt, 'si', $mission2, $challenge['id']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                error_log("Homevshome accepted, redirecting to homevshome.php from accept.php");
                header("Location: homevshome.php");
                exit;
            }
        } else {
            setChallengeStatus($conn, $challenge['id'], 'accepted');
            $redirectPage = $challenge['type'] == 'randomhome' ? 'randomhome.php' : 'report.php';
            error_log("Challenge accepted, redirecting to $redirectPage from accept.php");
            header("Location: $redirectPage");
            exit;
        }
    }
    if (isset($_POST["decline"]) && $challenge['challenged'] == $currentUserId && $challenge['status'] == 'challenged') {
        setChallengeStatus($conn, $challenge['id'], 'cancelled');
        $message = htmlspecialchars($currentUserName) . " has Declined your " . htmlspecialchars($challenge['type']) . " Challenge.";
        sendDiscordNotification($message);
        error_log("Challenge declined, redirecting to challenge.php from accept.php");
        header("Location: challenge.php");
        exit;
    }
    if (isset($_POST["cancel"]) && $challenge['challenger'] == $currentUserId && $challenge['status'] == 'challenged') {
        setChallengeStatus($conn, $challenge['id'], 'cancelled');
        error_log("Challenge cancelled, redirecting to challenge.php from accept.php");
        header("Location: challenge.php");
        exit;
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

    <div class="namesize">
        <br />
        <br />
        <?php echo htmlspecialchars($currentUserName); ?>
        <?php if ($challenge['challenged'] == $currentUserId && $challenge['status'] == 'challenged'): ?>
            <?php
            $challengeTypeLabels = ["traditional" => "Traditional", "homevshome" => "Home Vs Home", "randomhome" => "Random Home"];
            $challengeTypeLabel = $challengeTypeLabels[$challenge['type']] ?? $challenge['type'];
            ?>
            <p><?php echo htmlspecialchars($currentUserName); ?>, <?php echo htmlspecialchars($challenge['challenger']); ?> has challenged you to a <?php echo htmlspecialchars($challengeTypeLabel); ?> match.<br />Accept or decline.</p>
        <?php elseif ($challenge['status'] == 'accepted'): ?>
            <p style="margin-top: 2px;"><?php echo htmlspecialchars($currentUserName); ?>, you have accepted a match with<br /><?php echo htmlspecialchars(($challenge['challenger'] == $currentUserId) ? $challenge['challenged'] : $challenge['challenger']); ?>!</p>
        <?php elseif ($challenge['status'] == 'reported'): ?>
            <p><?php echo htmlspecialchars($challenge['loser'] ?? 'Unknown'); ?> reported: </p>
        <?php elseif (isset($confirmed)): ?>
            <p>Game Confirmed.</p>
        <?php elseif (isset($kickedback)): ?>
            <p>Game Kicked Back.</p>
        <?php elseif ($challenge['challenger'] == $currentUserId && $challenge['status'] == 'challenged'): ?>
            <p>Waiting for <?php echo htmlspecialchars($challenge['challenged']); ?> to accept your Challenge.</p>
        <?php endif; ?>
    </div>



    <?php
    if (isset($error_message)) {
        echo "<p class='error-message'>" . htmlspecialchars($error_message) . "</p>";
    }
    if ($challenge['challenger'] == $currentUserId && $challenge['status'] == 'challenged') {
        echo "<form method='post'>";
        echo "<input type='submit' name='cancel' value='Cancel'>";
        echo "</form>";
    } else if ($challenge['challenged'] == $currentUserId && $challenge['status'] == 'challenged') {
        echo "<form method='post'>";
        if ($challenge['type'] == 'homevshome') {
            echo "<label for='mission2'>Your Home (Game 2):</label>";
            echo "<select name='mission2' id='mission2' required>";
            echo "<option value=''>Choose a home</option>";
            foreach ($challengedHomes as $home) {
                echo "<option value='" . htmlspecialchars($home) . "'>" . htmlspecialchars($home) . "</option>";
            }
            echo "</select>";
        }

        echo "<input type='submit' name='accept' value='Accept'>";
        echo "<input type='submit' name='decline' value='Decline'>";
        echo "</form>";
    } else if ($challenge['status'] == 'reported') {
        error_log("Reported challenge, winner: " . ($challenge['winner'] ?? 'null') . ", currentUserId: $currentUserId");


        if ($challenge['type'] == 'homevshome') {
            echo "<table class='challenge-table'>";
            echo "<tr><th>Challenger Home</th><td>" . htmlspecialchars($challenge['mission1'] ?? 'None') . "</td></tr>";
            echo "<tr><th>Your Home</th><td>" . htmlspecialchars($challenge['mission2'] ?? 'None') . "</td></tr>";
            echo "<tr><th>Winner<br><span style='display:inline-block;margin-top:6px;padding-left:12px;'>Loser</span></th><td>" . htmlspecialchars($challenge['winner'] ?? 'N/A') . " " . htmlspecialchars($challenge['winnerKills'] ?? '0') . "<br><span style='display:inline-block;margin-top:6px;'>" . htmlspecialchars($challenge['loser'] ?? 'N/A') . " " . htmlspecialchars($challenge['loserKills'] ?? '0') . "</span></td></tr>";
            // Split combined "Winner1 W-L | Winner2 W-L" into two rows.
            $game1 = trim($challenge['winner1'] ?? '');
            $game2 = trim($challenge['winner2'] ?? '');
            if ($game1 !== '' && isset($challenge['winner1Kills'], $challenge['loser1Kills'])) {
                $game1Text = $game1 . ' ' . $challenge['winner1Kills'] . '-' . $challenge['loser1Kills'];
            } else {
                $parts = explode('|', (string)($challenge['gamesCombined'] ?? ''));
                $game1Text = trim($parts[0] ?? 'N/A');
            }
            if ($game2 !== '' && isset($challenge['winner2Kills'], $challenge['loser2Kills'])) {
                $game2Text = $game2 . ' ' . $challenge['winner2Kills'] . '-' . $challenge['loser2Kills'];
            } else {
                $parts = explode('|', (string)($challenge['gamesCombined'] ?? ''));
                $game2Text = trim($parts[1] ?? 'N/A');
            }
            echo "<tr><th>Game 1</th><td>" . htmlspecialchars($game1Text) . "</td></tr>";
            echo "<tr><th>Game 2</th><td>" . htmlspecialchars($game2Text) . "</td></tr>";
            echo "<tr><th>Combined Suicides</th><td>" . htmlspecialchars($challenge['combinedSuicides'] ?? '0') . "</td></tr>";
            echo "<tr><th>Reporter Comment</th><td>" . htmlspecialchars($challenge['loserComment'] ?? 'N/A') . "</td></tr>";
            echo "</table>";
        } else {
            echo "<table class='challenge-table'>";
            echo "<tr><th>Game</th><td>" . htmlspecialchars($challenge['gameplayed'] ?? '') . "</td></tr>";
            echo "<tr><th>Mission</th><td>" . htmlspecialchars($challenge['mission'] ?? '') . "</td></tr>";
            echo "<tr><th>Winner</th><td>" . htmlspecialchars($challenge['winner'] ?? '') . "</td></tr>";
            echo "<tr><th>Kills</th><td>" . htmlspecialchars($challenge['winnerKills'] ?? '0') . "</td></tr>";
            echo "<tr><th>Loser</th><td>" . htmlspecialchars($challenge['loser'] ?? '') . "</td></tr>";
            echo "<tr><th>Kills</th><td>" . htmlspecialchars($challenge['loserKills'] ?? '0') . "</td></tr>";
            echo "<tr><th>GameScores</th><td>" . htmlspecialchars($challenge['gamesCombined'] ?? '') . "</td></tr>";
            echo "</table>";
        }
        if ($challenge['winner'] == $currentUserId && !empty($challenge['winner'])) {
            echo "<form method='post'>";
            echo "<p>Waiting for you to confirm!</p>";
            echo "<label for='winnerComment'>Winner's Comment:</label><br>";
            echo "<input id='winnerComment' name='winnerComment' placeholder='GG' value='GG'><br><br>";
            echo "<input type='submit' name='confirm' value='Confirm'>";
            echo "<input type='submit' name='kickback' value='Kick Back'>";
            echo "</form>";
        } else {
            echo "<form method='post'>";
            echo "<p>Waiting for " . htmlspecialchars($challenge['winner'] ?? 'Unknown') . " to confirm!</p>";
            echo "<input type='hidden' name='kickback' value='Kick Back'>";
            echo "</form>";
        }
    }
    ?>
</div>

<?php include 'Includes/footer.inc.php'; ?>
