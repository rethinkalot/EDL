
<?php

require_once __DIR__ . '/Includes/accept.inc.php';


$webhookurl = "YOUR_WEBHOOK_URL_HERE";

<?php



// Construct lines (like the webhook message)
$lines = [
    "$currentUserName confirmed the match!",
    "",
    "{$challenge['winner']} - {$challenge['winnerKills']} - $winnerComment",
    "{$challenge['loser']} - {$challenge['loserKills']} - {$challenge['loserComment']}",
    "Mission - {$challenge['mission']}",
    "Game - {$challenge['gameplayed']}"
];

// Image settings
$width = 620;
$height = 260;
$lineHeight = 32;
$fontSize = 12; // In points
$fontPath = DIR . "/verdana.ttf"; // Path to Verdana font

// Fallback to built-in font if Verdana is not found
$useTTF = file_exists($fontPath);

// Colors (RGB)
$backgroundColor = [24, 24, 24];
$white = [220, 220, 220];
$orange = [255, 140, 0];
$lightBlue = [72, 142, 209];

// Create the image
$image = imagecreatetruecolor($width, $height);
$bg = imagecolorallocate($image, ...$backgroundColor);
imagefilledrectangle($image, 0, 0, $width, $height, $bg);

// Allocate text colors
$whiteColor = imagecolorallocate($image, ...$white);
$orangeColor = imagecolorallocate($image, ...$orange);
$blueColor = imagecolorallocate($image, ...$lightBlue);

// Start drawing
$y = 20;
foreach ($lines as $line) {
    if (str_contains($line, 'confirmed the match!')) {
        drawText($image, $line, 30, $y, $whiteColor, $fontSize, $fontPath, $useTTF);
    } elseif (str_contains($line, 'Mission') || str_contains($line, 'Game')) {
        [$label, $value] = explode(' - ', $line, 2);
        drawText($image, "$label:", 30, $y, $orangeColor, $fontSize, $fontPath, $useTTF);
        drawText($image, $value, 250, $y, $blueColor, $fontSize, $fontPath, $useTTF);
    } elseif (substr_count($line, ' - ') === 2) {
        [$name, $kills, $comment] = explode(' - ', $line, 3);
        drawText($image, "$name:", 30, $y, $orangeColor, $fontSize, $fontPath, $useTTF);
        drawText($image, "$kills Kills - $comment", 200, $y, $blueColor, $fontSize, $fontPath, $useTTF);
    } else {
        drawText($image, $line, 30, $y, $whiteColor, $fontSize, $fontPath, $useTTF);
    }
    $y += $lineHeight;
}

// Save the image
$outputPath = DIR . "/match_report_output.png";
imagepng($image, $outputPath);
imagedestroy($image);

echo "✅ Image saved at: match_report_output.png";

// Helper function for text rendering
function drawText($image, $text, $x, $y, $color, $fontSize, $fontPath, $useTTF) {
    if ($useTTF) {
        imagettftext($image, $fontSize, 0, $x, $y, $color, $fontPath, $text);
    } else {
        imagestring($image, 4, $x, $y - 14, $text, $color);
    }
}
?>