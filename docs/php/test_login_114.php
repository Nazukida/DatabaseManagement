<?php
// php/test_login_114.php
require_once 'db.php';

$riderId = 114;
// The hash for the default password
$passwordHash = 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3';

$sql = "SELECT * FROM riders WHERE RiderID = '$riderId' AND PasswordHash = '$passwordHash'";
$result = mysqli_query($conn, $sql);

if ($result) {
    $user = mysqli_fetch_assoc($result);
    if ($user) {
        echo "Found User: " . $user['Name'] . " (ID: " . $user['RiderID'] . ")";
    } else {
        echo "User 114 not found with default password.";
    }
} else {
    echo "Query failed: " . mysqli_error($conn);
}

echo "\n\n--- Check for ID 3 ---\n";
$sql3 = "SELECT * FROM riders WHERE RiderID = '3'";
$result3 = mysqli_query($conn, $sql3);
$user3 = mysqli_fetch_assoc($result3);
echo "User 3 is: " . $user3['Name'];
?>