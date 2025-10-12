<?php
// Simple file-based visitor counter for Web 1.0 site
// Tracks unique visitors by IP address (24-hour window)

// Configuration
$counterFile = '/var/lib/web1-site1-counter/counter.txt';
$ipDir = '/var/lib/web1-site1-counter/ips/';
$digitsToShow = 6;
$startCount = 0;
$uniqueWindow = 86400; // 24 hours in seconds

// Initialize counter file if it doesn't exist
if (!file_exists($counterFile)) {
    file_put_contents($counterFile, $startCount, LOCK_EX);
}

// Get visitor's IP address
$visitorIP = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Create safe filename from IP (replace dots with underscores)
$ipFileName = $ipDir . str_replace('.', '_', $visitorIP) . '.txt';

// Clean up old IP files (older than 24 hours)
if (is_dir($ipDir)) {
    $now = time();
    foreach (glob($ipDir . '*.txt') as $file) {
        if ($now - filemtime($file) > $uniqueWindow) {
            @unlink($file);
        }
    }
}

// Check if this IP has visited recently (within last 24 hours)
if (!file_exists($ipFileName) || (time() - filemtime($ipFileName) > $uniqueWindow)) {
    // New visitor or returning after 24h - increment counter
    $count = (int)file_get_contents($counterFile);
    $count++;
    file_put_contents($counterFile, $count, LOCK_EX);
    file_put_contents($ipFileName, date('Y-m-d H:i:s'), LOCK_EX);
} else {
    // Existing visitor within 24 hours - just read current count
    $count = (int)file_get_contents($counterFile);
}

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
