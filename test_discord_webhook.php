<?php



/*
|--------------------------------------------------------------------------
| FAKE CHALLENGE DATA (SAFE TEST)
|--------------------------------------------------------------------------
*/
$challenge = [
    'winner'        => 'Rethink',
    'loser'         => 'TestPlayer',
    'winnerKills'   => 12,
    'loserKills'    => 8,
    'mission'       => 'Deep Mines',
    'gameplayed'    => 'Descent 3',
    'loserComment'  => 'GG'
];

$confirmedBy = 'WebhookTester';
$winnerComment = 'Good match';

/*
|--------------------------------------------------------------------------
| GENERATE & SEND TEST IMAGE + WEBHOOK
|--------------------------------------------------------------------------
*/
generateAndSendMatchImage($challenge, $winnerComment, $confirmedBy);

echo "✅ Discord webhook test sent successfully.";

function sendDiscordImage($url, $imagePath, $challenge, $confirmedBy)
{
    $baseUrl = "https://edl.ascentroid.com";

    $winner = $challenge['winner'];
    $loser  = $challenge['loser'];

    $winnerUrl = $baseUrl . "/profile.php?user=" . urlencode($winner);
    $loserUrl  = $baseUrl . "/profile.php?user=" . urlencode($loser);
    $gamesUrl  = $baseUrl . "/games.php";

    // Discord markdown content
    $content =
        "🏆 **[Match Confirmed!]($gamesUrl)**\n" .
        "👤 **[$winner]($winnerUrl)** defeated **[$loser]($loserUrl)**\n" .
        "🕒 Confirmed by **$confirmedBy**";

    $payload = json_encode([
        "content" => $content
    ]);

    $boundary = "----Boundary" . md5(uniqid());

    $body  = "--$boundary\r\n";
    $body .= "Content-Disposition: form-data; name=\"payload_json\"\r\n\r\n";
    $body .= $payload . "\r\n";

    $body .= "--$boundary\r\n";
    $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"match_result.png\"\r\n";
    $body .= "Content-Type: image/png\r\n\r\n";
    $body .= file_get_contents($imagePath) . "\r\n";
    $body .= "--$boundary--\r\n";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ["Content-Type: multipart/form-data; boundary=$boundary"],
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true
    ]);

    curl_exec($ch);
    curl_close($ch);
}
