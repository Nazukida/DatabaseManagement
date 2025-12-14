<?php
require_once 'db.php';

echo "<h1>Debug Rider View (Available Orders)</h1>";

// 1. Check raw orders in DB that SHOULD be visible
$sqlRaw = "SELECT OrderID, OrderStatus, RiderID FROM `order` WHERE OrderStatus = 'confirmed'";
$resultRaw = $conn->query($sqlRaw);
echo "<h2>1. Raw 'confirmed' orders in DB:</h2>";
if ($resultRaw->num_rows > 0) {
    echo "<table border='1'><tr><th>OrderID</th><th>Status</th><th>RiderID</th></tr>";
    while($row = $resultRaw->fetch_assoc()) {
        echo "<tr><td>{$row['OrderID']}</td><td>{$row['OrderStatus']}</td><td>" . var_export($row['RiderID'], true) . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "No 'confirmed' orders found in raw table.<br>";
}

// 2. Run the EXACT query from rider_api.php
$sqlApi = "SELECT o.OrderID, o.TotalAmount, o.DeliveryFee, r.RestaurantName, r.DeliveryArea as PickupAddress 
        FROM `order` o
        LEFT JOIN restaurants r ON o.RestaurantID = r.RestaurantID
        WHERE (o.RiderID IS NULL OR o.RiderID = 0) 
        AND o.OrderStatus = 'confirmed'
        ORDER BY o.OrderTime DESC
        LIMIT 20";

echo "<h2>2. Result from Rider API Query:</h2>";
$resultApi = $conn->query($sqlApi);
if ($resultApi && $resultApi->num_rows > 0) {
    echo "<table border='1'><tr><th>OrderID</th><th>Restaurant</th><th>Amount</th></tr>";
    while($row = $resultApi->fetch_assoc()) {
        echo "<tr><td>{$row['OrderID']}</td><td>{$row['RestaurantName']}</td><td>{$row['TotalAmount']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "API Query returned NO results.<br>";
    echo "Query was: <pre>$sqlApi</pre>";
    echo "Error (if any): " . $conn->error;
}
?>