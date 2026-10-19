<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

// Get logged-in user ID
$userId = getUserId();
if (!$userId) {
    header("Location: login.php");
    exit;
}

// Fetch current user data
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

// Handle form submission
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $home1 = $_POST['home1'] ?? '';
    $home2 = $_POST['home2'] ?? '';
    $home3 = $_POST['home3'] ?? '';
    $home4 = $_POST['home4'] ?? '';
    $home5 = $_POST['home5'] ?? '';
    $game = $_POST['game'] ?? '';
    $location = $_POST['location'] ?? '';
    $quote = $_POST['quote'] ?? '';
    
    // Validate home5 is a seasonal choice
    $seasonalHomes = ['Calamity', 'Carbon', 'Crimson', 'Cronos', 'Cryptic'];
    if (!in_array($home5, $seasonalHomes)) {
        $error_message = "Please select a valid seasonal home for Home 5.";
    } else {



       // Update user homes
$query = "UPDATE users SET home1 = ?, home2 = ?, home3 = ?, home4 = ?, home5 = ?, game = ?, location = ?, quote = ? WHERE usersUid = ?";
$stmt = mysqli_stmt_init($conn);
if (!mysqli_stmt_prepare($stmt, $query)) {
    error_log("Prepare failed for updating homes: " . mysqli_error($conn));
    die("Error updating homes.");
}
mysqli_stmt_bind_param($stmt, 'sssssssss', $home1, $home2, $home3, $home4, $home5, $game, $location, $quote, $userId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
$success = true;
    }
}

$title = 'Edit Profile';
include 'Includes/header.inc.php';
?>

<div class="container">
    <div class="namesize">
          <br />
          <br />
          <a href="profile.php?user=<?= urlencode($userId) ?>">
                            <?= htmlspecialchars($userId) ?>
                        </a>
       
               <br /><br />
        <h2>Edit Your Profile</h2>
        <?php if (isset($error_message)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p>Homes updated successfully! Visit <a href="ranks.php">Ranks</a> to check out the action!</p>
            or Click your name to visit your profile!
        <?php else: ?>                     
               <form method="POST">               
                <table style="font-size: 14px;">
                    <tr>
                        <th>Homes 1-3</th>
                        <th>Homes 4-5 & Game</th>
                    </tr>
                    <tr>
                        <td>
                            <label for="home1">Home 1:</label><br>
                            <input type="text" name="home1" id="home1" value="<?php echo htmlspecialchars($home1); ?>" placeholder="Enter Home 1"><br>
                            <label for="home2">Home 2:</label><br>
                            <input type="text" name="home2" id="home2" value="<?php echo htmlspecialchars($home2); ?>" placeholder="Enter Home 2"><br>
                            <label for="home3">Home 3:</label><br>
                            <input type="text" name="home3" id="home3" value="<?php echo htmlspecialchars($home3); ?>" placeholder="Enter Home 3"><br>
                        </td>
                        <td>
                            <label for="home4">Home 4:</label><br>
                            <input type="text" name="home4" id="home4" value="<?php echo htmlspecialchars($home4); ?>" placeholder="Enter Home 4"><br>
                            <label for="home5">Home 5 (Season 3):</label><br>
                <select name="home5" id="home5" required>
                <option value="Seasonal">Choose Seasonal</option>
                <?php    
				$seasonalHomes = ['Calamity', 'Carbon', 'Crimson', 'Cronos', 'Cryptic'];
				foreach ($seasonalHomes as $home) {
				$selected = ($home5 === $home) ? 'selected' : '';
				// Clean, plain-text concatenation with zero backslash pollution
				echo "<option value='" . htmlspecialchars($home) . "' $selected>" . htmlspecialchars($home) . "</option>";
				}				
				?>
                            </select><br>
                            <label for="game">Game:</label><br>
                            <select name="game" id="game" required>
                                <option value="Descent" selected>Descent</option>
                                <option value="Descent2">Descent2</option>
                                <option value="Descent3">Descent3</option>
                                <option value="Ascentroid">Ascentroid</option>
                                <option value="Geocore">Geocore</option>
                                <option value="Overload">Overload</option>
                            </select><br>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="location">Location:</label><br>
                            <input type="text" name="location" id="location" placeholder="Location"><br>
                        </td>
                        <td>
                            <label for="quote">Quote:</label><br>
                            <input type="text" name="quote" id="quote"  placeholder="Quote"><br>
                        </td>
                    </tr>
                </table>
                <br>

             <input type="hidden" name="status" value="Save Homes">
            <input type="submit" name="submit" value="Submit">
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'Includes/footer.inc.php'; ?>