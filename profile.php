<?php
session_start();


require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

// Get user id from link
$userId = $_GET['user'];

// Load data from the database
$query = "SELECT home1, home2, home3, home4, home5, favorite, location, quote FROM users";
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
$favorite = $userData['favorite'];
$location = $userData['location'];
$quote = $userData['quote'];

$title = 'Profile';
include 'Includes/header.inc.php';
?>

<style>
td, th { width:25% }
</style>

<!--
<div class="table_cell">
<table class="">
<tr>
    <td><?php echo htmlspecialchars($home1); ?></td>
    <td><?php echo htmlspecialchars($home2); ?></td>
    <td><?php echo htmlspecialchars($home3); ?></td>
    <td><?php echo htmlspecialchars($home4); ?></td>
    <td><?php echo htmlspecialchars($home5); ?></td>
</tr>
</table>
</div>
-->

<div class="container">

		<h1 class="profile_header retired"> 
		<?php echo htmlspecialchars($userId); ?>
		</h1> 

		<div class="retired" style="margins: 0 auto; text-align: center; font-style: italic; font-size: 1.5em;">
                <?php echo htmlspecialchars($quote); ?>
		</div><br><br>
		<p class="profile_subheader  retired">Legacy</p>
		<p class="profile_subsubheader"> - Lifetime</p> 
		 
		<table class="pilot_details retired">
						<tbody><tr>
				<td class="detail_category">Home:</td>
				<td class="detail_content">
                                    <?php echo htmlspecialchars($home1); ?><br>
                                    <?php echo htmlspecialchars($home2); ?><br>
                                    <?php echo htmlspecialchars($home3); ?><br>
                                    <?php echo htmlspecialchars($home4); ?><br>
                                    <?php echo htmlspecialchars($home5); ?><br>
				</td>
			</tr>
			 

						<tr>
				<td class="detail_category detail_vspacer">Location:</td>
				<td class="detail_content detail_vspacer">
                                    <?php echo htmlspecialchars($location); ?><br>
				</td>
			</tr>

			 										

		</tbody></table>
		 

		 
		<table class="medals_table"><tbody><tr>
			</tr><tr> 
				</tr></tbody></table>

				

				<table class="medals_table"><tbody><tr>
				</tr><tr>
			</tr></tbody></table><table class="medals_table">

				
		</table>
		
		<table class="match_history">
			<tbody><tr><td></td><td colspan="3"><div class="heading retired">Match History</div></td></tr>
			 

		</tbody></table> 
	</div>
</table>
</div>
<?php
    include 'Includes/footer.inc.php';
?>
	