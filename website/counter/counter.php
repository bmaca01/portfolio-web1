<?php
// Simple file-based visitor counter for Web 1.0 site
// No sessions, no complex logic - just increment and display

// Store counter file outside web directory to survive deployments
$counterFile = '/var/lib/web1-site1-counter/counter.txt';
$digitsToShow = 6;
$startCount = 0;

// Initialize counter file if it doesn't exist
if (!file_exists($counterFile)) {
    file_put_contents($counterFile, $startCount, LOCK_EX);
}

// Read current count
$count = (int)file_get_contents($counterFile);

// Increment
$count++;

// Write back with file locking to prevent race conditions
file_put_contents($counterFile, $count, LOCK_EX);

// Format with leading zeros
$paddedCount = str_pad($count, $digitsToShow, '0', STR_PAD_LEFT);
$digits = str_split($paddedCount);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
            background: transparent;
        }
        img {
            border: 0;
            vertical-align: bottom;
            display: inline-block;
        }
    </style>
</head>
<body>
    <?php foreach ($digits as $digit): ?>
        <img src="../images/counter/<?php echo $digit; ?>.gif" alt="<?php echo $digit; ?>" width="15" height="20">
    <?php endforeach; ?>
</body>
</html>
