<?php

function getUserId() {
	return isset($_SESSION) && isset($_SESSION['useruid']) ? $_SESSION['useruid'] : NULL;
}

function addGame($conn, $mission, $winner, $loser, $winnerKills, $forfeit, $loserKills,
    $winnerDeaths, $winnerSuicides, $loserDeaths, $loserSuicides) {
    $sql = "INSERT INTO games (mission, winner, loser, winnerKills, forfeit, loserKills, " .
        "winnerDeaths, winnerSuicides, loserDeaths, loserSuicides" .
        ") VALUES (?,?,?,?,?,?,?,?,?,?);";
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt, "sssiiiiiii", $mission, $winner, $loser, $winnerKills, $forfeit, $loserKills,
        $winnerDeaths, $winnerSuicides, $loserDeaths, $loserSuicides);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function emptyInputSignup($name, $email, $username, $pwd, $pwdrepeat) {
    if (empty($name) || empty($email) || empty($username) || empty($pwd) || empty($pwdrepeat)) {
        $result = true;
    } 
    else {
     $result = false;
    }
    return $result;
}


function invalidUid($username) {
    $result;
    if (!preg_match("/^[a-zA-Z0-9]*$/", $username)) {
    $result = true;
    } 
    else {
     $result = false;
    }
    return $result;
}

function invalidEmail($email) {
    $result;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $result = true;
    } 
    else {
      $result = false;
    }
    return $result;
}

function pwdMatch($pwd, $pwdRepeat) {
    $result;
    if ($pwd !== $pwdRepeat) {
      $result = true;
    } 
    else {
    $result = false;
    }
    return $result;
}

function uidExists($conn, $username, $email) {
   $sql = "SELECT * FROM users WHERE usersUid = ? OR usersEmail = ?;";
   $stmt = mysqli_stmt_init($conn);
   if (!mysqli_stmt_prepare($stmt, $sql)) {
    die("DB prepare failed: " . mysqli_stmt_error($stmt));
   }

   mysqli_stmt_bind_param($stmt, "ss", $username, $email);
   mysqli_stmt_execute($stmt);

   $resultData = mysqli_stmt_get_result($stmt);

   if ($row = mysqli_fetch_assoc($resultData)) {
     return $row;
   }

   else {
     $result = false;
     return $result;
   }

   mysqli_stmt_close($stmt);
}

function createUser($conn, $name, $email, $username, $pwd) {
    $sql = "INSERT INTO users (usersName, usersEmail, usersUid, usersPwd) VALUES (?,?,?,?);";
    $stmt = mysqli_stmt_init($conn);
if (!mysqli_stmt_prepare($stmt, $sql)) {
    die("DB failed: " . mysqli_error());
}

 $hashedPwd = password_hash($pwd, PASSWORD_DEFAULT);
 
 mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $username, $hashedPwd);
 mysqli_stmt_execute($stmt);
 mysqli_stmt_close($stmt);
 return true;
}

 
function checkUser($conn, $username, $pwd) {
   $sql = "SELECT usersPwd FROM users WHERE usersUid = ?;";
   $stmt = mysqli_stmt_init($conn);
   if (!mysqli_stmt_prepare($stmt, $sql)) {
    die("DB prepare failed: " . mysqli_stmt_error($stmt));
   }

   mysqli_stmt_bind_param($stmt, "s", $username);
   mysqli_stmt_execute($stmt);

   $resultData = mysqli_stmt_get_result($stmt);

   $row = mysqli_fetch_assoc($resultData);
   
   mysqli_stmt_close($stmt);

   if (!$row) {   
     return false;
   }

   return password_verify($pwd, $row['usersPwd']);
}

function getUsers($conn) {
    // Fetch the list of useruids from the test_db
    $query = "SELECT usersUid FROM users";
    $result = mysqli_query($conn, $query);

    // Store the useruids in an array
    $useruids = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $useruids[] = $row['usersUid'];
    }

    return $useruids;
}

function validChallengeType($type) {
    return $type == "traditional" ||
        $type == "5050roll" ||
        $type == "homevshome" ||
        $type == "usemarker";
}

function validChallengeStatus($status) {
    return $status == "challenged" ||
        $status == "accepted" ||
        $status == "declined" ||
        $status == "reported" ||
        $status == "confirmed";
}

function createChallenge($conn, $challenged, $type) {
    if (!validChallengeType($type))
        die('Invalid challenge type ' . $type);

    $status = 'challenged';

    // Create challenge
    $query = 'INSERT INTO challenges (challenger, challenged, type, status) VALUES (?, ?, ?, ?);';
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, $query);
    mysqli_stmt_bind_param($stmt, 'ssss', $_SESSION['useruid'], $challenged, $type, $status);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return mysqli_insert_id($conn);
}

function findChallenge($conn) {
    $userId = getUserId();

    // Find challenge by challenged / challenger
    $query = "SELECT * FROM challenges WHERE (challenger = ? OR challenged = ?)";
    $query .= " AND status != 'confirmed' AND status != 'declined'";
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $userId, $userId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $row;
}

function setChallengeStatus($conn, $id, $status) {
    if (!validChallengeStatus($status))
        throw new Exception('Invalid challenge status ' . $status);
    $query = 'UPDATE challenges SET status = ? WHERE id = ?';

    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, $query);
    mysqli_stmt_bind_param($stmt, 'si', $status, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function updateStats($conn, $player, $stats) {
    $sets = [];
    foreach ($stats as $col => $val)
        $sets[] = $col . " = " . $col . "+" . intval($val);
    $query = "UPDATE ranks SET " . join(",", $sets) . " WHERE player = ?";
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, $query);
    mysqli_stmt_bind_param($stmt, 's', $player);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function updateStreaks($conn, $player, $isWin) {
    if ($isWin)
        $query = "UPDATE ranks SET streaks = streaks + 1 WHERE player = ?";
    else
        $query = "UPDATE ranks SET streaks = 0 WHERE player = ?";
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, $query);
    mysqli_stmt_bind_param($stmt, 's', $player);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function getRank($conn, $player) {
    $query = 'SELECT rank FROM ranks WHERE player = ?';
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, $query);
    mysqli_stmt_bind_param($stmt, 's', $player);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($row)
        return $row['rank'];

    // New player gets highest rank + 1
    $query = 'SELECT max(rank) AS max_rank FROM ranks';
    $row = mysqli_fetch_assoc(mysqli_query($conn, $query));
    if ($row && !is_null($row['max_rank']))
        $newRank = $row['max_rank'] + 1;
    else // First player gets rank 1
        $newRank = 1;

    $query = 'INSERT INTO ranks (player, rank) VALUES (?, ?)';
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, $query);
    mysqli_stmt_bind_param($stmt, 'si', $player, $newRank);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $newRank;
}

function updateRanks($conn, $winner, $loser) {
    $winnerRank = getRank($conn, $winner);
    $loserRank = getRank($conn, $loser);
    if ($winnerRank < $loserRank)
        return;

    // Move players between loser and winner one rank down
    $query = 'UPDATE ranks SET rank = rank + 1 WHERE rank > ? AND rank < ?';
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $loserRank, $winnerRank);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Move winner to loser rank
    $query = 'UPDATE ranks SET rank = ? WHERE player = ?';
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, $query);
    mysqli_stmt_bind_param($stmt, 'is', $loserRank, $winner);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Move loser one position down
    $query = 'UPDATE ranks SET rank = ? WHERE player = ?';
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, $query);
    $newLoserRank = $loserRank + 1;
    mysqli_stmt_bind_param($stmt, 'is', $newLoserRank, $loser);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function confirmGame($conn, $challengeId) {
	mysqli_begin_transaction($conn);
	try {
		$data = findChallenge($conn);
		if (!$data || $data['status'] != 'reported')
			throw new Exception('Invalid challenge status ' . ($data ? $data['status'] : 'none'));
		
        if ($data['challenger'] == $data['reporter'])
            $winner = $data['challenged']; // reporter=challenger, other=challenged
        else
            $winner = $data['challenger']; // reporter=challenged, other=challenger
        $loser = $data['reporter'];

		addGame($conn, $data['mission'], $winner, $loser, $data['winnerKills'], $data['forfeit'], $data['loserKills'],
		    $data['winnerDeaths'], $data['winnerSuicides'], $data['loserDeaths'], $data['loserSuicides']);
		setChallengeStatus($conn, $data['id'], 'confirmed');
		if ($data['forfeit']) {
			updateStats($conn, $winner, array('forfeits' => 1));
			updateStats($conn, $loser, array('forfeits' => 1));
		} else {
			updateRanks($conn, $winner, $loser);
			updateStats($conn, $winner, array('games' => 1, 'wins' => 1, 'kills' => $data['winnerKills'],
				'deaths' => $data['loserKills'], 'suicides' => $data['winnerSuicides']));
			updateStats($conn, $loser, array('games' => 1, 'losses' => 1, 'kills' => $data['loserKills'],
				'deaths' => $data['winnerKills'], 'suicides' => $data['loserSuicides']));
			updateStreaks($conn, $winner, true);
			updateStreaks($conn, $loser, false);
		}
		mysqli_commit($conn);
	} catch (Throwable $e) {
		mysqli_rollback($conn);
		throw $e;
	}
        notifyNewGame($conn, $data['mission'], $winner, $loser, $data['winnerKills'], $data['forfeit'], $data['loserKills']);
}

function reportGame($conn, $challengeId, $mission, $winnerKills, $forfeit, $loserKills,
    $winnerDeaths, $winnerSuicides, $loserDeaths, $loserSuicides) {

    $reporter = getUserId();
    $status = "reported";

    // Add reported data to the existing challenge row

    $query = "UPDATE challenges SET status = ?, reporter = ?, mission = ?, winnerKills = ?, forfeit = ?, loserKills = ?, " .
        "winnerDeaths = ?, winnerSuicides = ?, loserDeaths = ?, loserSuicides = ?" .
        " WHERE id = ?";

    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, $query);
    mysqli_stmt_bind_param($stmt, "sssiiiiiiii", $status, $reporter, $mission,
        $winnerKills, $forfeit, $loserKills,
        $winnerDeaths, $winnerSuicides, $loserDeaths, $loserSuicides,
        $challengeId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function redirectChallenge($challenge) {
	if (!$challenge) {
        header("Location: challenge.php");
        return;
	}
	$status = $challenge['status'];
	if ($status == 'challenged') {
        header("Location: accept.php");
        return;
	}
	if ($status == 'accepted') {
        header("Location: report.php");
        return;
	}
	if ($status == 'reported') {
        header("Location: confirm.php");
        return;
	}
	throw new Exception('Unknown challenge status ' . $status);
}

function notifyNewGame($conn, $mission, $winner, $loser, $winnerKills, $forfeit, $loserKills) {
    $discordWebhookUrl = 'https://discord.com/api/webhooks/1124540937993998346/rjQ-QWgLSqHOLR6tEQmwHpCqe2rj1cfdP6bP9pHfEta-wUmvK8ug74xkUmJTiwvXt1Gl';
    $message = "$winner - $winnerKills\n$loser - $loserKills\nMission: $mission";
    $data = array('content' => $message);
    $options = array(
        'http' => array(
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data),
        ),
    );
    $context = stream_context_create($options);
    $result = file_get_contents($discordWebhookUrl, false, $context);

    if ($result === false) {
    }
}
