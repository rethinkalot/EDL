<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

$title = 'Challenge';
include 'Includes/header.inc.php';

// Check if there's already an unaccepted challenge for this player
$challenge = findChallenge($conn);

// If there is already an active challenge, redirect to the appropriate page
if ($challenge) {
    redirectChallenge($challenge);
    exit;
}

// Check if challenge form submitted
if (!$challenge && isset($_POST["challenged"])) {
    // Check if all fields are filled
    if (!empty($_POST["challenged"]) && !empty($_POST["type"])) {
        $challenger = getUserId();
        $challenged = $_POST["challenged"];
        $gameplayed = $_POST["gameplayed"];
        $type = $_POST["type"];
        createChallenge($conn, $challenged, $gameplayed, $type);

// Send Discord webhook notification for challenges
        $webhookUrl = 'https://discord.com/api/webhooks/1124540114169770075/4HGcYrEFuSdldR5E_3ptLibSbf2IxX3yWKgl8etnFE1YdGrIebnUpt1hojEh-5atl00j';
		$linkUrl = "https://elitedescentladder.ddns.net";
		$linkText = "TheEDL";
        $message = "$challenger has challenged $challenged to a Match!\n$challenged, please Login to Accept.";
        $embeds = array(
            array( 'title' => 'Accept', 'url' => 'https://elitedescentladder.ddns.net/accept.php')
            );

        $data = array(
            'content' => $message,
            'embeds' => $embeds
        );

        //print_r($data); exit;

        $options = array(
            'http' => array(
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data)
            ),
        );

        $context  = stream_context_create($options);
        $result = file_get_contents($webhookUrl, false, $context);

        if ($result === false) {
            $error_message = "Failed to send Discord webhook notification.";
}

        // Redirect to the accept.php page
        header("Location: accept.php");
        exit;
    } else {
        $error_message = "Please fill in all fields!";
    }
}
?>

<div class="container">
    <?php
    // Display error message if set
    if (isset($error_message)) {
        echo '<p class="error-message">' . $error_message . '</p>';
    }
    ?>

    <form method="POST">
        <label for="challenged">Select Opponent:</label>
        <select name="challenged" id="challenged">
            <?php foreach (getUsers($conn) as $useruid) if ($useruid != getUserId()) { ?>
                <option value="<?php echo $useruid; ?>"><?php echo $useruid; ?></option>
            <?php } ?>
        </select>
                            <select name="gameplayed" id="gameplayed">
												    <option selected="">
						    Descent						    </option>
						    						    <option select="">
						    Descent2						    </option>
						    						    <option select="">
						    Descent3						    </option>
						    						    <option select="">
						    Ascentroid						    </option>
						    						    <option select="">
						    Overload						    </option>
	       				    					</select>
                            
       

        <style>
            input[type="radio"]~.info {
                display: none;
            }

            input[type="radio"]:checked~.info {
                display: block;
            }

            .info {
                position: absolute;
                margin-left: 240px;
                max-width: 22em;
                font-size: smaller;
            }
        </style>

        <label class="radio"><input type="radio" name="type" checked value="traditional">Traditional
            <div class="info">
                Traditional:<br>(all options below behave as Traditional until EDL website is completed)
                Ranked Match First to 20 Win by 2.<br>
                Players Decide Terms.
            </div>
        </label>

        <label class="radio"><input type="radio" name="type" value="5050roll">50/50 Flip to Pick
            <div class="info">
                50/50 Flip to Pick:<br>(Ladder option not functional yet)
                Ranked Match First to 20 Win by 2.<br>
                Flip to decide who picks from opposing players home list.
            </div>
        </label>

        <label class="radio"><input type="radio" name="type" value="5050roll">Random
            <div class="info">
                Random:<br>(Random wheel is in Discord)
                Ranked Match First to 20 Win by 2.<br>
                After the match is accepted, the mission to be played is selected at random from each player's home list.
                Your challenge and acceptance of 'Random' expect honor by both parties.
            </div>
        </label>

        <label class="radio"><input type="radio" name="type" value="homevshome">Home Vs Home
            <div class="info">
                Home Vs Home:<br>
                Best of 2 Matches played to 20 win by 2.(Ladder option not functional yet)
                Challenger's home choice gets played first.
                Combined highest KDR decides the winner who ranks.
            </div>
        </label>

        <label class="radio"><input type="radio" name="type" value="usemarker">Will you be using a Marker?
            <div class="info">
                Marker Challenge:<br>(Ladder option not functional yet.)
                Ranked Game First to 20 Win by 2
                Must achieve at least ranks 7 after earning
                a Marker to issue a non-declinable
                challenge to the #1Ranked player.
                Challenger picks from #1's home list.
            </div>
        </label>

        <input type="submit" name="submit" value="Send It">
    </form>
</div>

<?php
include 'Includes/footer.inc.php';
?>
