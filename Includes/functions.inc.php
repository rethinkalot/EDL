<?php

function getUserId()
{
  return isset($_SESSION['useruid']) ? $_SESSION['useruid'] : NULL;
}

function addGame(
  $challenger,
  $challenged,
  $type,
  $status,
  $gameplayed,
  $mission,
  $mission1,
  $mission2,
  $winner,
  $winnerKills,
  $winnerDeaths,
  $winner1,
  $winner1Kills,
  $winner1Deaths,
  $winner2,
  $winner2Kills,
  $winner2Deaths,
  $loser,
  $loserKills,
  $loserDeaths,
  $loser1,
  $loser1Kills,
  $loser1Deaths,
  $loser2,
  $loser2Kills,
  $loser2Deaths,
  $combinedSuicides,
  $winnerComment,
  $loserComment,
  $gamesCombined,
  $score
) {
  global $conn;
  $query = "INSERT INTO games (
        challenger, challenged, type, status, gameplayed, mission, mission1, mission2,
        winner, winnerKills, winnerDeaths, winner1, winner1Kills, winner1Deaths,
        winner2, winner2Kills, winner2Deaths, loser, loserKills, loserDeaths,
        loser1, loser1Kills, loser1Deaths, loser2, loser2Kills, loser2Deaths,
        combinedSuicides, winnerComment, loserComment, gamesCombined, score    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

  $stmt = mysqli_stmt_init($conn);
  if (!mysqli_stmt_prepare($stmt, $query)) {
    throw new mysqli_sql_exception("SQL error: " . mysqli_error($conn));
  }

  mysqli_stmt_bind_param(
    $stmt,
    'sssssssssissssisissssisisssisii', // 30 types
    $challenger,
    $challenged,
    $type,
    $status,
    $gameplayed,
    $mission,
    $mission1,
    $mission2,
    $winner,
    $winnerKills,
    $winnerDeaths,
    $winner1,
    $winner1Kills,
    $winner1Deaths,
    $winner2,
    $winner2Kills,
    $winner2Deaths,
    $loser,
    $loserKills,
    $loserDeaths,
    $loser1,
    $loser1Kills,
    $loser1Deaths,
    $loser2,
    $loser2Kills,
    $loser2Deaths,
    $combinedSuicides,
    $winnerComment,
    $loserComment,
    $gamesCombined,
    $score
  );

  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}

function emptyInputSignup($name, $email, $username, $pwd, $pwdrepeat)
{
  return empty($name) || empty($email) || empty($username) || empty($pwd) || empty($pwdrepeat);
}

function invalidUid($username)
{
  return !preg_match("/^[a-zA-Z0-9]*$/", $username);
}

function invalidEmail($email)
{
  return !filter_var($email, FILTER_VALIDATE_EMAIL);
}

function pwdMatch($pwd, $pwdRepeat)
{
  return $pwd !== $pwdRepeat;
}

function uidExists($conn, $username, $email)
{
  $sql = "SELECT * FROM users WHERE usersUid = ? OR usersEmail = ?";
  $stmt = mysqli_stmt_init($conn);
  if (!mysqli_stmt_prepare($stmt, $sql)) {
    die("DB prepare failed: " . mysqli_stmt_error($stmt));
  }
  mysqli_stmt_bind_param($stmt, "ss", $username, $email);
  mysqli_stmt_execute($stmt);
  $resultData = mysqli_stmt_get_result($stmt);
  if ($row = mysqli_fetch_assoc($resultData)) {
    return $row;
  } else {
    return false;
  }
  mysqli_stmt_close($stmt);
}

function createUser($conn, $name, $email, $username, $pwd) {
    // Add your 5 custom map columns right into your core user insertion query
    $sql = "INSERT INTO users (usersName, usersEmail, usersUid, usersPwd, home1, home2, home3, home4, home5) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);";
    $stmt = mysqli_stmt_init($conn);
    
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        return false;
    }

    // Hash the password securely
    $hashedPwd = password_hash($pwd, PASSWORD_DEFAULT);

    // Hardcode your Season 3 "C" defaults straight into the parameter binding execution
    $home1 = 'Calamity';
    $home2 = 'Carbon';
    $home3 = 'Crimson';
    $home4 = 'Cronos';
    $home5 = 'Cryptic';

    // 4 strings for user data + 5 strings for default maps = 9 's' types total
    mysqli_stmt_bind_param($stmt, "sssssssss", $name, $email, $username, $hashedPwd, $home1, $home3, $home4, $home5);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return true;
    } else {
        mysqli_stmt_close($stmt);
        return false;
    }
}

function checkUser($conn, $username, $pwd)
{
  $sql = "SELECT usersPwd FROM users WHERE usersUid = ?";
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

function getUsers($conn)
{
  $query = "SELECT usersUid FROM users";
  $result = mysqli_query($conn, $query);
  $useruids = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $useruids[] = $row['usersUid'];
  }
  return $useruids;
}

function validChallengeType($type)
{
  return $type == "traditional" ||
    $type == "5050roll" ||
    $type == "randomhome" ||
    $type == "homevshome" ||
    $type == "usemarker";
}

function validChallengeStatus($status)
{
  return $status == "challenged" ||
    $status == "accepted" ||
    $status == "declined" ||
    $status == "cancelled" ||
    $status == "reported" ||
    $status == "confirmed";
}

function createChallenge($conn, $challenged, $gameplayed, $type, $status, $mission1 = '', $mission2 = '')
{
  if (!validChallengeType($type))
    die('Invalid challenge type ' . $type);
  if (!validChallengeStatus($status))
    die('Invalid challenge status ' . $status);

  $challenger = getUserId();
  $query = 'INSERT INTO challenges (challenger, challenged, gameplayed, type, status, mission1, mission2) VALUES (?, ?, ?, ?, ?, ?, ?)';
  $stmt = mysqli_stmt_init($conn);
  if (!mysqli_stmt_prepare($stmt, $query)) {
    die('SQL error: ' . mysqli_error($conn));
  }
  mysqli_stmt_bind_param($stmt, 'sssssss', $challenger, $challenged, $gameplayed, $type, $status, $mission1, $mission2);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
  return mysqli_insert_id($conn);
}


function findChallenge($conn)
{
  $userId = getUserId();
  $challengeId = isset($_GET['id']) ? (int)$_GET['id'] : null;

  if ($challengeId) {
    $query = "SELECT * FROM challenges WHERE id = ? AND (challenger = ? OR challenged = ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'iss', $challengeId, $userId, $userId);
  } else {
    $query = "SELECT * FROM challenges WHERE (challenger = ? OR challenged = ?)";
    $query .= " AND status != 'confirmed' AND status != 'declined' AND status != 'cancelled'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $userId, $userId);
  }

  mysqli_stmt_execute($stmt);
  $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
  mysqli_stmt_close($stmt);
  return $row;
}

function setChallengeStatus($conn, $id, $status)
{
  if (!validChallengeStatus($status)) {
    throw new Exception('Invalid challenge status ' . $status);
  }
  $query = 'UPDATE challenges SET status = ? WHERE id = ?';
  $stmt = mysqli_stmt_init($conn);
  mysqli_stmt_prepare($stmt, $query);
  mysqli_stmt_bind_param($stmt, 'si', $status, $id);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}
//
//function updateStats($conn, $player, $stats)
//{
//  $sets = [];
//  foreach ($stats as $col => $val) {
//    $sets[] = $col . " = " . $col . "+" . intval($val);
//  }
//  $query = "UPDATE ranks SET " . join(",", $sets) . " WHERE player = ?";
//  $stmt = mysqli_stmt_init($conn);
//  mysqli_stmt_prepare($stmt, $query);
//  mysqli_stmt_bind_param($stmt, 's', $player);
//  mysqli_stmt_execute($stmt);
//  mysqli_stmt_close($stmt);
//}
//
//function updateStreaks($conn, $player, $isWin)
//{
//  if ($isWin) {
//    $query = "UPDATE ranks SET streaks = streaks + 1 WHERE player = ?";
//  } else {
//    $query = "UPDATE ranks SET streaks = 0 WHERE player = ?";
//  }
//  $stmt = mysqli_stmt_init($conn);
//  mysqli_stmt_prepare($stmt, $query);
//  mysqli_stmt_bind_param($stmt, 's', $player);
//  mysqli_stmt_execute($stmt);
//  mysqli_stmt_close($stmt);
//}
//
//function getRank($conn, $player)
//{
//  $query = 'SELECT `rank` FROM ranks WHERE player = ?';
//  $stmt = mysqli_stmt_init($conn);
//  mysqli_stmt_prepare($stmt, $query);
//  mysqli_stmt_bind_param($stmt, 's', $player);
//  mysqli_stmt_execute($stmt);
//  $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
//  mysqli_stmt_close($stmt);
//  if ($row) {
//    return $row['rank'];
//  }
//  $query = 'SELECT max(`rank`) AS max_rank FROM ranks';
//  $row = mysqli_fetch_assoc(mysqli_query($conn, $query));
//  if ($row && !is_null($row['max_rank'])) {
//    $newRank = $row['max_rank'] + 1;
//  } else {
//    $newRank = 1;
//  }
//  $query = 'INSERT INTO ranks (player, `rank`) VALUES (?, ?)';
//  $stmt = mysqli_stmt_init($conn);
//  mysqli_stmt_prepare($stmt, $query);
//  mysqli_stmt_bind_param($stmt, 'si', $player, $newRank);
//  mysqli_stmt_execute($stmt);
//  mysqli_stmt_close($stmt);
//  return $newRank;
//}

//function updateRanks($conn, $winner, $loser)
//{
//  $winnerRank = getRank($conn, $winner);
//  $loserRank = getRank($conn, $loser);
//  if ($winnerRank < $loserRank) {
//    return;
//  }
//  $query = 'UPDATE ranks SET `rank` = `rank` + 1 WHERE `rank` > ? AND `rank` < ?';
//  $stmt = mysqli_stmt_init($conn);
//  mysqli_stmt_prepare($stmt, $query);
//  mysqli_stmt_bind_param($stmt, 'ii', $loserRank, $winnerRank);
//  mysqli_stmt_execute($stmt);
//  mysqli_stmt_close($stmt);
//  $query = 'UPDATE ranks SET `rank` = ? WHERE player = ?';
//  $stmt = mysqli_stmt_init($conn);
//  mysqli_stmt_prepare($stmt, $query);
//  mysqli_stmt_bind_param($stmt, 'is', $loserRank, $winner);
//  mysqli_stmt_execute($stmt);
//  mysqli_stmt_close($stmt);
//  $query = 'UPDATE ranks SET `rank` = ? WHERE player = ?';
//  $stmt = mysqli_stmt_init($conn);
//  mysqli_stmt_prepare($stmt, $query);
//  $newLoserRank = $loserRank + 1;
//  mysqli_stmt_bind_param($stmt, 'is', $newLoserRank, $loser);
//  mysqli_stmt_execute($stmt);
//  mysqli_stmt_close($stmt);
//}

//function confirmGame($conn, $challengeId)
//{
//  setChallengeStatus($conn, $challengeId, 'confirmed');
//}



function reportGame($conn, $challengeId, $winner, $loser, $winnerKills, $loserKills, $winnerDeaths, $loserDeaths, $combinedSuicides, $gameplayed, $mission, $loserComment)
{
  $query = "UPDATE challenges SET winner = ?, loser = ?, winnerKills = ?, loserKills = ?, winnerDeaths = ?, loserDeaths = ?, combinedSuicides = ?, gameplayed = ?, mission = ?, loserComment = ?, status = 'reported' WHERE id = ?";
  $stmt = mysqli_stmt_init($conn);
  if (!mysqli_stmt_prepare($stmt, $query)) {
    error_log("SQL prepare failed: " . mysqli_stmt_error($stmt));
    throw new Exception("DB failed: " . mysqli_stmt_error($stmt));
  }
  mysqli_stmt_bind_param($stmt, "ssiiiissssi", $winner, $loser, $winnerKills, $loserKills, $winnerDeaths, $loserDeaths, $combinedSuicides, $gameplayed, $mission, $loserComment, $challengeId);
  if (!mysqli_stmt_execute($stmt)) {
    error_log("SQL execute failed: " . mysqli_stmt_error($stmt));
    throw new Exception("DB failed: " . mysqli_stmt_error($stmt));
  }
  mysqli_stmt_close($stmt);
}


function redirectChallenge($challenge)
{
  if (!$challenge) {
    header("Location: challenge.php");
    exit();
  }
  $status = $challenge['status'];
  if ($status == 'challenged') {
    header("Location: accept.php");
    exit();
  }
  if ($status == 'accepted' && $challenge['type'] == 'randomhome') {
    header("Location: randomhome.php");
    exit();
  }
  if ($status == 'accepted' && $challenge['type'] == 'homevshome') {
    header("Location: homevshome.php");
    exit();
  }
  if ($status == 'accepted' && $challenge['type'] == 'traditional') {
    header("Location: report.php");
    exit();
  }
  if ($status == 'reported' && $challenge['type'] == 'randomhome') {
    header("Location: accept.php");
    exit();
  }
  if ($status == 'reported' && $challenge['type'] == 'homevshome') {
    header("Location: accept.php");
    exit();
  }
  if ($status == 'reported' && $challenge['type'] == 'traditional') {
    header("Location: accept.php");
    exit();
  }
}

function storeWinnerComment($conn, $challengeId, $winnerComment)
{
  $query = "UPDATE challenges SET winnerComment = ? WHERE id = ?";
  $stmt = mysqli_stmt_init($conn);
  if (!mysqli_stmt_prepare($stmt, $query)) {
    echo "SQL error: " . mysqli_error($conn);
    return false;
  }
  mysqli_stmt_bind_param($stmt, 'si', $winnerComment, $challengeId);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}

function storeLoserComment($conn, $challengeId, $loserComment)
{
  $query = "UPDATE challenges SET loserComment = ? WHERE id = ?";
  $stmt = mysqli_stmt_init($conn);
  if (!mysqli_stmt_prepare($stmt, $query)) {
    echo "SQL error: " . mysqli_error($conn);
    return false;
  }
  mysqli_stmt_bind_param($stmt, 'si', $loserComment, $challengeId);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}

function getLoserComment($conn, $challengeId)
{
  $query = "SELECT loserComment FROM challenges WHERE id = ?";
  $stmt = mysqli_stmt_init($conn);
  if (!mysqli_stmt_prepare($stmt, $query)) {
    echo "SQL error: " . mysqli_error($conn);
    return false;
  }
  mysqli_stmt_bind_param($stmt, 'i', $challengeId);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($result);
  mysqli_stmt_close($stmt);
  return $row['loserComment'];
}

function getChallengeLoserComment($conn, $challengeId)
{
  $query = "SELECT loserComment FROM challenges WHERE id = ?";
  $stmt = mysqli_stmt_init($conn);
  if (!mysqli_stmt_prepare($stmt, $query)) {
    echo "SQL error: " . mysqli_error($conn);
    return null;
  }
  mysqli_stmt_bind_param($stmt, 'i', $challengeId);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($result);
  mysqli_stmt_close($stmt);
  return $row['loserComment'] ?? null;
}

function getChallengeWinnerComment($conn, $challengeId)
{
  $query = "SELECT winnerComment FROM challenges WHERE id = ?";
  $stmt = mysqli_stmt_init($conn);
  if (!mysqli_stmt_prepare($stmt, $query)) {
    echo "SQL error: " . mysqli_error($conn);
    return null;
  }
  mysqli_stmt_bind_param($stmt, 'i', $challengeId);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($result);
  mysqli_stmt_close($stmt);
  return $row['winnerComment'] ?? null;
}

function createRememberMeToken($conn, $username)
{
  $selector = bin2hex(random_bytes(8));
  $validator = bin2hex(random_bytes(32));
  $token = $selector . ':' . $validator;
  $hashedValidator = password_hash($validator, PASSWORD_DEFAULT);
  $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
  $query = "INSERT INTO auth_tokens (selector, hashedValidator, username, expires) VALUES (?, ?, ?, ?)";
  $stmt = mysqli_stmt_init($conn);
  mysqli_stmt_prepare($stmt, $query);
  mysqli_stmt_bind_param($stmt, 'ssss', $selector, $hashedValidator, $username, $expiry);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
  return $token;
}

function validateRememberMeToken($conn, $token)
{
  list($selector, $validator) = explode(':', $token);
  $query = "SELECT * FROM auth_tokens WHERE selector = ? AND expires > NOW()";
  $stmt = mysqli_stmt_init($conn);
  mysqli_stmt_prepare($stmt, $query);
  mysqli_stmt_bind_param($stmt, 's', $selector);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  if ($row = mysqli_fetch_assoc($result)) {
    if (password_verify($validator, $row['hashedValidator'])) {
      return $row['username'];
    }
  }
  return false;
}

function deleteRememberMeToken($conn, $username)
{
  $query = "DELETE FROM auth_tokens WHERE username = ?";
  $stmt = mysqli_stmt_init($conn);
  mysqli_stmt_prepare($stmt, $query);
  mysqli_stmt_bind_param($stmt, 's', $username);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
}

function autoLogin()
{
  global $conn;
  if (!isset($_COOKIE['remember_me']))
    return false;
  $username = validateRememberMeToken($conn, $_COOKIE['remember_me']);
  if ($username) {
    $_SESSION['useruid'] = $username;
    getRank($conn, $username);
    return true;
  } else {
    setcookie('remember_me', '', time() - 3600, '/');
    return false;
  }
}

function getWinnerComment($conn, $challengeId)
{
  $sql = "SELECT winnerComment FROM challenges WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $challengeId);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    return $row['winnerComment'];
  }
  return null;
}


function sendDiscordNotification($message, $embeds = null)
{
  $webhookUrl = 'YOUR_WEBHOOK_URL_HERE';
  $data = ['content' => $message];
  if ($embeds !== null) {
    $data['embeds'] = $embeds;
  }
  $options = [
    'http' => [
      'method' => 'POST',
      'header' => 'Content-Type: application/json',
      'content' => json_encode($data)
    ]
  ];
  $context = stream_context_create($options);
  file_get_contents($webhookUrl, false, $context);
}