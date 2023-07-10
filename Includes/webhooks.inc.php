      
<?php

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




// Send message to Discord webhook
        $discordWebhookUrl = 'https://discord.com/api/webhooks/1124540114169770075/4HGcYrEFuSdldR5E_3ptLibSbf2IxX3yWKgl8etnFE1YdGrIebnUpt1hojEh-5atl00j';
        $message = $challenge['challenged'] . ' has declined the match!';
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
            $error_message = "Failed to send Discord webhook notification.";
}











