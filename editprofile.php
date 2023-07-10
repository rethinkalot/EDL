<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

$userId = getUserId();

// Load data from the database
$query = "SELECT home1, home2, home3, home4, home5, game, location, quote FROM users";
$query .= " WHERE usersUid=?";
$stmt = mysqli_stmt_init($conn);
mysqli_stmt_prepare($stmt, $query);
mysqli_stmt_bind_param($stmt, 's', $userId);
mysqli_stmt_execute($stmt);
$userData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$home1 = $userData['home1'];
$home2 = $userData['home2'];
$home3 = $userData['home3'];
$home4 = $userData['home4'];
$home5 = $userData['home5'];
$game = $userData['game'];
$location = $userData['location'];
$quote = $userData['quote'];


if (isset($_POST["home1"])) {
    $home1 = $_POST["home1"];
    $home2 = $_POST["home2"];
    $home3 = $_POST["home3"];
    $home4 = $_POST["home4"];
    $home5 = $_POST["home5"];
    $game = $_POST["game"];
    $location = $_POST["location"];
    $quote = $_POST["quote"];

    // Store data in the database

    $query = "UPDATE users SET home1=?, home2=?, home3=?, home4=?, home5=?, game=?, location=?, quote=?";
    $query .= " WHERE usersUid=?";
    
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, $query);
    mysqli_stmt_bind_param($stmt, 'sssssssss',
        $home1, $home2, $home3, $home4, $home5, $game, $location, $quote,
        $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

}

$title = 'editprofile';
include 'Includes/header.inc.php';

?>
<div class="container">
<form action="editprofile.php" method="POST">
	 
						<td class="field_cell field_header">Welcome to Edit Profile</td>
						<br>
						<br>
</br>
</br>

</break>
</div>
<div  class="container">
	
						<td class="field_cell">Homes</td>
					<tr>	
						<input class="field" type="text" name="home1" value="<?php echo htmlspecialchars($home1); ?>" placeholder="Home 1">
					</tr>
					<tr>	
						<input class="field" type="text" name="home2" value="<?php echo htmlspecialchars($home2); ?>" placeholder="Home 2">
					</tr>
					<tr>	
						<input class="field" type="text" name="home3" value="<?php echo htmlspecialchars($home3); ?>" placeholder="Home 3">
					</trait		>
						<input class="field" type="text" name="home4" value="<?php echo htmlspecialchars($home4); ?>" placeholder="Home 4">
					<tr>
							<td class="field_cell"><hd>Season Home</td>
							<select name="home5" placeholder="EDL Home 5">
							<?php
                                                        $seasonHomes = array('Ascend', 'BlackRose', 'Crimson', 'Dromos', 'Ethylen');
                                                        foreach ($seasonHomes as $home) {
                                                        ?>
                                                            <option <?php if ($home == $home5) { ?> selected<?php } ?>>
                                                            <?php echo htmlspecialchars($home); ?>
                                                            </option>
                                                            <?php
                                                        }
                                                        ?>
						</select>
					</td>
				</tr>		
			</tr>
<div class = "field_header">
			
				<td class="field_cell field_header">Favorite Game</td>
			</tr>
			<tr>
				<td class="field_cell">
					<select name="game">
						<?php
						$games = array('Descent', 'Descent2', 'Descent3', 'Ascentroid', 'Overload');
						foreach ($games as $gameitem) {
						    ?>
						    <option <?php if ($gameitem == $game) { ?> selected<?php } ?>>
						    <?php echo htmlspecialchars($gameitem); ?>
						    </option>
						    <?php
                                                }
                                                ?>
					</select>
				</td>
			</tr>
			<br>
			
			<tr>
				<td class="field_cell field_header"><hr></td>
			</tr>

			<tr>
				<td class="field_cell field_header">Location</td>
			</tr>
			<tr>
				<td class="field_cell"><input class="field" type="text" name="location" value="<?php echo htmlspecialchars($location); ?>" placeholder="City/St/Planet?"></td>
	
				</tr>

			<tr>
				<td class="field_cell field_header"><hr></td>
			</tr>
			<tr>
				<td class="field_cell field_header">Quote</td>
			</tr>

			<tr>
				<td class="field_cell"><input class="field" type="text" name="quote" value="<?php echo htmlspecialchars($quote); ?>" placeholder="Tagline or Quote"></td>
			</tr>
						<tr>
					<input type="submit" class="submit" value="submit">
				</td>
			</tr>

		</tbody></table>
	</form>
	</div>

<?php
include 'Includes/footer.inc.php';
?>
