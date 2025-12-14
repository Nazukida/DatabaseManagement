<?php
$file = 'debug_log.txt';
$current = file_get_contents($file);
$current .= "Test log entry at " . date('Y-m-d H:i:s') . "\n";
if (file_put_contents($file, $current)) {
    echo "Log write successful. Check monitor.";
} else {
    echo "Log write FAILED. Check permissions.";
}
?>