      
<?php

// Send Discord webhook notification for challenges
        $webhookUrl = 'YOUR_WEBHOOK_URL_HERE';
		$linkUrl = "https://edl.ascentroid.com";
		$linkText = "TheEDL";
        $message = "$challenger has challenged $challenged to a Match!\n$challenged, please Login to Accept.";
        $embeds = array(
            array( 'title' => 'Accept', 'url' => 'https://edl.ascentroid.com/accept.php')
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
        $discordWebhookUrl = 'YOUR_WEBHOOK_URL_HERE';
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











