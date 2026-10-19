<?php
// Includes/accept.inc.php - FINAL WORKING VERSION

// Your Discord webhook URL
$discordwebhookurl = 'YOUR_WEBHOOK_URL_HERE';

// Main function called when winner confirms the match
function confirmChallenge($conn, $challenge)
{
    $winnerComment = $_POST['winnerComment'] ?? 'GG';

    // 1. Update challenge with winner comment and status
    $query = "UPDATE challenges SET status = 'confirmed', winnerComment = ? WHERE id = ?";
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $query)) {
        mysqli_stmt_bind_param($stmt, 'si', $winnerComment, $challenge['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // 2. Move to games table if not already there
    $query = "SELECT id FROM games WHERE id = ?";
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, $query);
    mysqli_stmt_bind_param($stmt, 'i', $challenge['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$exists) {
        $query = "INSERT INTO games (
            id, challenger, challenged, type, status, gameplayed, mission, mission1, mission2,
            winner, winnerKills, winnerDeaths, loser, loserKills, loserDeaths, combinedSuicides,
            winnerComment, loserComment, winner1Kills, winner1Deaths, loser1Kills, loser1Deaths,
            winner2Kills, winner2Deaths, loser2Kills, loser2Deaths,
            winner1, winner2, loser1, loser2, gamesCombined, score
        )
        SELECT 
            id, challenger, challenged, type, status, gameplayed, mission, mission1, mission2,
            winner, winnerKills, winnerDeaths, loser, loserKills, loserDeaths, combinedSuicides,
            ?, loserComment, winner1Kills, winner1Deaths, loser1Kills, loser1Deaths,
            winner2Kills, winner2Deaths, loser2Kills, loser2Deaths,
            winner1, winner2, loser1, loser2, gamesCombined, score
        FROM challenges WHERE id = ?";
        
        $stmt = mysqli_stmt_init($conn);
        if (mysqli_stmt_prepare($stmt, $query)) {
            mysqli_stmt_bind_param($stmt, 'si', $winnerComment, $challenge['id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    // 3. Update ranks and stats
    updateRanks($conn, $challenge['winner'], $challenge['loser']);
    updateStats($conn, $challenge['winner'], [
        'wins'   => 1,
        'kills'  => $challenge['winnerKills'],
        'deaths' => $challenge['loserKills']   // Note: winner's deaths = loser's kills
    ]);
    updateStreaks($conn, $challenge['winner'], true);

    updateStats($conn, $challenge['loser'], [
        'losses' => 1,
        'kills'  => $challenge['loserKills'],
        'deaths' => $challenge['winnerKills']
    ]);
    updateStreaks($conn, $challenge['loser'], false);

    // 4. Generate image and send to Discord
    generateAndSendMatchImage($challenge, $winnerComment, getUserId());
}

// ──────────────────────────────────────────────────────────────
// IMAGE GENERATION + DISCORD SEND FUNCTIONS
// ──────────────────────────────────────────────────────────────
function generateAndSendMatchImage($challenge, $winnerComment, $confirmedBy)
{
    global $discordwebhookurl; // or pass as parameter

    /* ---------------- IMAGE SETTINGS ---------------- */
    $width    = 320;
    $height   = 225;
    $radius   = 14;
    $fontSize = 14;
    $fontPath = __DIR__ . '/verdana.ttf';
    $useTTF   = file_exists($fontPath);

    /* ---------------- CREATE IMAGE ---------------- */
    $img = imagecreatetruecolor($width, $height);
    imagesavealpha($img, true);
    imagealphablending($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    /* ---------------- COLORS ---------------- */
    $bgColor     = imagecolorallocate($img, 24, 24, 24);
    $outlineBlue = imagecolorallocate($img, 72, 142, 209);
    $accentBar   = imagecolorallocate($img, 72, 142, 209);
    $white       = imagecolorallocate($img, 220, 220, 220);
    $orange      = imagecolorallocate($img, 255, 140, 0);
    $blue        = imagecolorallocate($img, 72, 142, 209);
    $muted       = imagecolorallocate($img, 72, 142, 209);

    /* ---------------- ROUNDED BACKGROUND & OUTLINE ---------------- */
    drawRoundedRect($img, 0, 0, $width - 1, $height - 1, $radius, $bgColor);
    drawRoundedRectOutline($img, 0, 0, $width - 1, $height - 1, $radius, $outlineBlue);

    /* ---------------- ACCENT BAR ---------------- */
    imagefilledrectangle($img, 0, 0, 3, $height, $accentBar);

    /* ---------------- TEXT ---------------- */
$y = 30;                    // Slightly higher starting point
$lineHeight = 36;           // Main spacing between rows (was 32)

drawCenteredText($img, "Confirmed by $confirmedBy!", $y, $blue, 16, $fontPath, $useTTF);
$y += $lineHeight;          // No extra +10 — cleaner progression

drawLabelValue(
    $img,
    $challenge['winner'],
    "{$challenge['winnerKills']} - $winnerComment",
    $y,
    $orange,
    $blue,
    $fontSize,
    $fontPath,
    $useTTF
);

drawLabelValue(
    $img,
    $challenge['loser'],
    "{$challenge['loserKills']} - " . ($challenge['loserComment'] ?? '—'),
    $y,
    $orange,
    $blue,
    $fontSize,
    $fontPath,
    $useTTF
);

drawLabelValue(
    $img,
    'Mission',
    $challenge['mission'] ?: '—',
    $y,
    $orange,
    $blue,
    $fontSize,
    $fontPath,
    $useTTF
);

drawLabelValue(
    $img,
    'Game',
    $challenge['gameplayed'] ?: '—',
    $y,
    $orange,
    $blue,
    $fontSize,
    $fontPath,
    $useTTF
);

// Timestamp at bottom
$timestamp = gmdate('M j, Y \a\t g:i A \U\T\C');
drawCenteredText($img, $timestamp, $height - 14, $muted, 9, $fontPath, $useTTF);

    /* ---------------- SAVE & SEND ---------------- */
    $tempFile = __DIR__ . '/temp_match_' . time() . '.png';
    imagepng($img, $tempFile);
    imagedestroy($img);

    if (file_exists($tempFile)) {
        sendDiscordImage($discordwebhookurl, $tempFile);
		unlink($tempFile);
    }
}

// ──────────────────────────────────────────────────────────────
// HELPER FUNCTIONS
// ──────────────────────────────────────────────────────────────

function drawCenteredText($img, $text, $y, $color, $size, $font, $useTTF)
{
    $imgWidth = imagesx($img);

    if ($useTTF) {
        $bbox = imagettfbbox($size, 0, $font, $text);
        $textWidth = $bbox[2] - $bbox[0];
        $x = (int) (($imgWidth - $textWidth) / 2);
        imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
    } else {
        $textWidth = imagefontwidth(4) * strlen($text);
        $x = (int) (($imgWidth - $textWidth) / 2);		
        imagestring($img, 4, $x, $y - 14, $text, $color);
    }
}

function wrapText($text, $fontSize, $fontPath, $maxWidth)
{
    $words = explode(' ', $text);
    $lines = [];
    $currentLine = '';

    foreach ($words as $word) {
        $testLine = $currentLine === '' ? $word : "$currentLine $word";
        $box = imagettfbbox($fontSize, 0, $fontPath, $testLine);
        $lineWidth = $box[2] - $box[0];

        if ($lineWidth > $maxWidth && $currentLine !== '') {
            $lines[] = $currentLine;
            $currentLine = $word;
        } else {
            $currentLine = $testLine;
        }
    }

    if ($currentLine !== '') {
        $lines[] = $currentLine;
    }

    return $lines;
}

function drawText($img, $text, $x, $y, $color, $size, $font, $useTTF)
{
    if ($useTTF) {
        imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
    } else {
        imagestring($img, 4, $x, $y - 14, $text, $color);
    }
}

function drawLabelValue($img, $label, $value, &$y, $labelColor, $valueColor, $fontSize, $fontPath, $useTTF)
{
    $labelX = 16;
    $valueX = 120;
    $maxWidth = imagesx($img) - $valueX - 12;
    $lineSpacing = 16;

    // Draw label
    drawText($img, "$label:", $labelX, $y, $labelColor, $fontSize, $fontPath, $useTTF);

    // Wrap value text
    $lines = wrapText($value, $fontSize, $fontPath, $maxWidth);
    $firstLineY = $y;

    foreach ($lines as $line) {
        drawText($img, $line, $valueX, $firstLineY, $valueColor, $fontSize, $fontPath, $useTTF);
        $firstLineY += $lineSpacing;
    }

    // Advance Y cursor based on wrapped height
    $y = $firstLineY + 6;
}

function drawRoundedRect($img, $x1, $y1, $x2, $y2, $r, $color)
{
    imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
    imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);
    imagefilledellipse($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
}

function drawRoundedRectOutline($img, $x1, $y1, $x2, $y2, $r, $color)
{
    imagesetthickness($img, 2);
    imagearc($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, 180, 270, $color);
    imagearc($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, 270, 360, $color);
    imagearc($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, 90, 180, $color);
    imagearc($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, 0, 90, $color);
    imageline($img, $x1 + $r, $y1, $x2 - $r, $y1, $color);
    imageline($img, $x1 + $r, $y2, $x2 - $r, $y2, $color);
    imageline($img, $x1, $y1 + $r, $x1, $y2 - $r, $color);
    imageline($img, $x2, $y1 + $r, $x2, $y2 - $r, $color);
}

function sendDiscordImage($url, $imagePath)
{
    $boundary = "----Boundary" . md5(uniqid());

    $body  = "--$boundary\r\n";
    $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"match_result.png\"\r\n";
    $body .= "Content-Type: image/png\r\n\r\n";
    $body .= file_get_contents($imagePath) . "\r\n";
    $body .= "--$boundary--\r\n";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST            => true,
        CURLOPT_HTTPHEADER      => ["Content-Type: multipart/form-data; boundary=$boundary"],
        CURLOPT_POSTFIELDS      => $body,
        CURLOPT_RETURNTRANSFER  => true
    ]);

    curl_exec($ch);
    curl_close($ch);
}

function updateStats($conn, $player, $stats)
{
    $sets = [];
    foreach ($stats as $col => $val) {
        $sets[] = "$col = $col + " . intval($val);
    }

    if (empty($sets)) return;

    $query = "UPDATE ranks SET " . join(", ", $sets) . " WHERE player = ?";
    $stmt = mysqli_stmt_init($conn);

    if (mysqli_stmt_prepare($stmt, $query)) {
        mysqli_stmt_bind_param($stmt, 's', $player);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function updateStreaks($conn, $player, $isWin)
{
    $query = $isWin
        ? "UPDATE ranks SET streaks = streaks + 1 WHERE player = ?"
        : "UPDATE ranks SET streaks = 0 WHERE player = ?";

    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $query)) {
        mysqli_stmt_bind_param($stmt, 's', $player);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function getRank($conn, $player)
{
    $query = 'SELECT `rank` FROM ranks WHERE player = ?';
    $stmt = mysqli_stmt_init($conn);

    if (mysqli_stmt_prepare($stmt, $query)) {
        mysqli_stmt_bind_param($stmt, 's', $player);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $row ? (int)$row['rank'] : 0;
    }

    return 0;
}

function updateRanks($conn, $winner, $loser)
{
    $winnerRank = getRank($conn, $winner);
    $loserRank  = getRank($conn, $loser);

    // Assign new rank if player is not in ranks table
    if ($winnerRank === 0) {
        $winnerRank = insertNewPlayerRank($conn, $winner);
    }
    if ($loserRank === 0) {
        $loserRank = insertNewPlayerRank($conn, $loser);
    }

    // Winner already has better (lower) rank → no change
    if ($winnerRank < $loserRank && $winnerRank !== 0) {
        return;
    }

    // Shift ranks between winner and loser
    $query = 'UPDATE ranks SET `rank` = `rank` + 1 WHERE `rank` >= ? AND `rank` < ?';
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $query)) {
        mysqli_stmt_bind_param($stmt, 'ii', $loserRank, $winnerRank);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Give winner the loser's previous rank
    $query = 'UPDATE ranks SET `rank` = ? WHERE player = ?';
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $query)) {
        mysqli_stmt_bind_param($stmt, 'is', $loserRank, $winner);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Move loser one rank down
    $newLoserRank = $loserRank + 1;
    $query = 'UPDATE ranks SET `rank` = ? WHERE player = ?';
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $query)) {
        mysqli_stmt_bind_param($stmt, 'is', $newLoserRank, $loser);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function insertNewPlayerRank($conn, $player)
{
    $query = 'SELECT COALESCE(MAX(`rank`), 0) + 1 AS new_rank FROM ranks';
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $newRank = (int)($row['new_rank'] ?? 1);

    $query = 'INSERT INTO ranks (player, `rank`, wins, losses, kills, deaths, streaks) 
              VALUES (?, ?, 0, 0, 0, 0, 0)';
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $query)) {
        mysqli_stmt_bind_param($stmt, 'si', $player, $newRank);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    return $newRank;
}

function confirmGame($conn, $challengeId)
{
    // If you still need this helper (currently very minimal)
    $query = "UPDATE challenges SET status = 'confirmed' WHERE id = ?";
    $stmt = mysqli_stmt_init($conn);
    if (mysqli_stmt_prepare($stmt, $query)) {
        mysqli_stmt_bind_param($stmt, 'i', $challengeId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}