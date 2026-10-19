<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

$title = 'Challenge';
include 'Includes/header.inc.php';

if (!session_id()) {
    error_log("Session not started in challenge.php");
    header("Location: login.php");
    exit;
}

// Log session data
error_log("Session data: " . json_encode($_SESSION));

// User ID check
$userId = getUserId();
if (!$userId) {
    error_log("No user ID, redirecting to login.php from challenge.php");
    header("Location: login.php");
    exit;
}

// Load user's homes
$query = "SELECT COALESCE(home1, '') AS home1, COALESCE(home2, '') AS home2, COALESCE(home3, '') AS home3, COALESCE(home4, '') AS home4, COALESCE(home5, '') AS home5 FROM users WHERE usersUid=?";
$stmt = mysqli_stmt_init($conn);
$homes = ['No homes set'];
if (mysqli_stmt_prepare($stmt, $query)) {
    mysqli_stmt_bind_param($stmt, 's', $userId);
    mysqli_stmt_execute($stmt);
    $userData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    $homes = array_filter([
        $userData['home1'] ?? '',
        $userData['home2'] ?? '',
        $userData['home3'] ?? '',
        $userData['home4'] ?? '',
        $userData['home5'] ?? ''
    ]);
    if (empty($homes)) {
        $homes = ['No homes set'];
    }
} else {
    error_log("Home query failed: " . mysqli_error($conn));
    echo "<p>Error loading user data: " . htmlspecialchars(mysqli_error($conn)) . "</p>";
}

// Fetch all users
$users = array_filter(getUsers($conn), function($uid) use ($userId) { return $uid != $userId; }) ?? [];

// Check existing challenge
$challenge = findChallenge($conn);

if ($challenge && isset($_POST["challenged"])) {
  ?>
  <div class="container">
  <p>You already have a challenge.</p>
  </div> 
  <?php
  exit;
}

if ($challenge && !isset($_POST["challenged"])) {
    error_log("Existing challenge found, redirecting: " . json_encode($challenge));
    redirectChallenge($challenge);
    exit;

}



// Valid game types
$validGames = ['Descent', 'Descent2', 'Descent3', 'Ascentroid', 'Geocore', 'Overload'];

// Handle form submission
$errors = [];
if (isset($_POST["challenged"])) {
    $challenger = $userId;
    $challenged = $_POST["challenged"] ?? '';
    $gameplayed = $_POST["gameplayed"] ?? '';
    $type = $_POST["type"] ?? '';
    $mission = $_POST["mission"] ?? '';
    $mission1 = $_POST["mission1"] ?? '';
    $mission2 = '';
    $status = "challenged";

    if (empty($challenged) || !in_array($challenged, $users)) {
        $errors[] = "Please select a valid opponent.";
    }
    if (empty($gameplayed) || !in_array($gameplayed, $validGames)) {
        $errors[] = "Please select a valid game.";
    }
    if (!in_array($type, ['traditional', 'homevshome', 'randomhome'])) {
        $errors[] = "Please select a valid challenge type.";
    }
    if ($type === 'homevshome' && (empty($mission1) || !in_array($mission1, $homes))) {
        $errors[] = "Please select a valid home for Home vs Home!";
    }

    if (empty($errors)) {
        createChallenge($conn, $challenged, $gameplayed, $type, $status, $mission1, $mission2);
        $webhookUrl = 'YOUR_WEBHOOK_URL_HERE';
        $linkUrl = "https://edl.ascentroid.com/accept.php";
        $message = "$challenger has challenged $challenged to a $type match!\n$challenged, please login to accept.";
        $embeds = [['title' => 'Accept', 'url' => $linkUrl]];
        $data = ['content' => $message, 'embeds' => $embeds];
        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data)
            ],
        ];
        $context = stream_context_create($options);
        if (file_get_contents($webhookUrl, false, $context) === false) {
            $errors[] = "Failed to send Discord notification.";
        } else {
            header("Location: accept.php");
            exit;
        }
    }
}

// Fetch active challenges
$challenges = [];
$query = "SELECT id, challenger, challenged, type, status, gameplayed FROM challenges 
          WHERE (challenger = ? OR challenged = ?) AND status IN ('challenged', 'accepted', 'reported')
          ORDER BY id DESC";
$stmt = mysqli_stmt_init($conn);
if (!mysqli_stmt_prepare($stmt, $query)) {
    error_log("Challenge query failed: " . mysqli_error($conn));
    echo "<p>Error loading challenges: " . htmlspecialchars(mysqli_error($conn)) . "</p>";
} else {
    mysqli_stmt_bind_param($stmt, 'ss', $userId, $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $challenges[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Log before rendering
error_log("Rendering challenge.php for user: $userId, homes: " . json_encode($homes) . ", users: " . json_encode($users));
?>

<div class="container">
    <div class="namesize">
        <br />
        <br />
        <?php echo htmlspecialchars(getUserId()); ?>
        <br />
        <p>Welcome to Challenges!</p>

        <form method="POST">
            <label for="challenged">Challenge Opponent:</label>
            <select name="challenged" id="challenged" required>
                <?php foreach (getUsers($conn) as $useruid)
                  if ($useruid != getUserId()) { ?>
                    <option value="<?php echo htmlspecialchars($useruid); ?>"><?php echo htmlspecialchars($useruid); ?></option>
                <?php } ?>
            </select>

            <label for="gameplayed">Game:</label>
            <select name="gameplayed" id="gameplayed" required>
                <option value="Descent" selected>Descent</option>
                <option value="Descent2">Descent2</option>
                <option value="Descent3">Descent3</option>
                <option value="Ascentroid">Ascentroid</option>
                <option value="Geocore">Geocore</option>
                <option value="Overload">Overload</option>
            </select>

            <style>
            input[type="radio"]~.info { display: none; }
            input[type="radio"]:checked~.info { display: block; }
            .info { position: absolute; margin-left: 240px; max-width: 22em; font-size: smaller; }
        </style>

            <label class="radio"><input type="radio" name="type" checked value="traditional" id="traditional">Traditional
                <div class="info">
                    First to 20 Win by 2.<br>
                    Players Are Free to Agree
                    on Any Available Settings.
                </div>
            </label><br>

            <label class="radio"><input type="radio" name="type" value="homevshome" id="homevshome">Home Vs Home
                <div class="info">
                    Home Vs Home:<br>
                    Back to Back to 20<br>
                    Play Game 1 your home first,<br>
                    Game 2 in theirs!<br>
                    Comparing the score difference for the win!<br>
                    <div id="home-container">
                        <label for="mission1">Your Home:</label><br>
                        <select name="mission1" id="mission1">
                            <option value="">Choose a home</option>
                            <?php foreach ($homes as $home): ?>
                                <option value="<?php echo htmlspecialchars($home); ?>"><?php echo htmlspecialchars($home); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </label><br>

            <label class="radio"><input type="radio" name="type" value="randomhome" id="randomhome">Random Home
                <div class="info">
                    Random Home:<br>
                    A single match to 20, win by 2.<br>
                    The system randomly selects one home<br>
                    from both players' five homes<br>
                    upon challenge acceptance.
                </div>
            </label><br>

            <input type="hidden" name="status" value="challenged">
            <input type="submit" name="submit" value="Send It">
        </form>

        <h2>Your Challenges</h2>
        <?php if (empty($challenges)): ?>
            <p>No active challenges.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Opponent</th>
                    <th>Game</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($challenges as $challenge): ?>
                    <tr>
                        <td>
                            <?php 
                            $opponent = ($challenge['challenger'] == $userId) ? $challenge['challenged'] : $challenge['challenger'];
                            echo htmlspecialchars($opponent);
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($challenge['gameplayed']); ?></td>
                        <td><?php echo htmlspecialchars($challenge['type']); ?></td>
                        <td><?php echo htmlspecialchars($challenge['status']); ?></td>
                        <td>
                            <?php if ($challenge['status'] == 'challenged' && $challenge['challenged'] == $userId): ?>
                                <a href="accept.php">Accept/Decline</a>
                            <?php elseif ($challenge['status'] == 'accepted'): ?>
                                <a href="<?php echo $challenge['type'] == 'homevshome' ? 'homevshome.php' : 'report.php'; ?>">Report</a>
                            <?php elseif ($challenge['status'] == 'reported'): ?>
                                <a href="accept.php">Confirm/Kick Back</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        
        <script>
            const homeVsHomeRadio = document.getElementById('homevshome');
            const traditionalRadio = document.getElementById('traditional');
            const randomHomeRadio = document.getElementById('randomhome');
            const homeContainer = document.getElementById('home-container');

            function updateHomeContainer() {
                if (homeContainer) {
                    homeContainer.style.display = homeVsHomeRadio.checked ? 'block' : 'none';
                    document.getElementById('mission1').required = homeVsHomeRadio.checked;
                }
            }

            homeVsHomeRadio.addEventListener('change', updateHomeContainer);
            traditionalRadio.addEventListener('change', updateHomeContainer);
            randomHomeRadio.addEventListener('change', updateHomeContainer);
            window.addEventListener('load', updateHomeContainer);
        </script>
    </div>
</div>

<?php include 'Includes/footer.inc.php'; ?>