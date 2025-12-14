<?php
require_once 'db.php';

$riderId = 100;

echo "<h1>Debug Rider $riderId</h1>";

// 1. Check if rider exists
$stmt = $conn->prepare("SELECT * FROM riders WHERE RiderID = ?");
$stmt->bind_param("i", $riderId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "<h2>Rider Found</h2>";
    echo "ID: " . $row['RiderID'] . "<br>";
    echo "Username: " . ($row['Username'] ?? 'N/A') . "<br>";
    echo "Status: " . $row['CurrentStatus'] . "<br>";
} else {
    echo "<h2>Rider NOT Found</h2>";
    echo "Rider ID $riderId does not exist in the `riders` table.<br>";
    
    // Optional: Create it for testing if missing
    echo "<h3>Attempting to create Rider $riderId for testing...</h3>";
    // Note: We need to know the schema of riders table to insert correctly.
    // Let's check schema first.
    $desc = $conn->query("DESCRIBE riders");
    echo "Table Schema: <br>";
    while($d = $desc->fetch_assoc()) {
        echo $d['Field'] . " - " . $d['Type'] . "<br>";
    }
}

// 2. Check available orders visibility for this rider
echo "<h2>Available Orders Check</h2>";
$sql = "SELECT COUNT(*) as count FROM `order` WHERE (RiderID IS NULL OR RiderID = 0) AND OrderStatus = 'confirmed'";
$count = $conn->query($sql)->fetch_assoc()['count'];
echo "Total Available Orders (RiderID=0/NULL, Status=confirmed): $count<br>";

?>