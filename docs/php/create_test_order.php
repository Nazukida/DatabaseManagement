<?php
require_once 'db.php';

// Insert a test order for User 24
$userId = 24;
$restaurantId = 1; // Assuming restaurant 1 exists
$addressId = 1; // Assuming address 1 exists
$totalAmount = 50.00;
$deliveryFee = 5.00;
$status = 'confirmed'; // Ready for rider
$orderTime = date('Y-m-d H:i:s');

// Check if restaurant exists
$checkRest = $conn->query("SELECT RestaurantID FROM restaurants LIMIT 1");
if ($checkRest->num_rows > 0) {
    $restaurantId = $checkRest->fetch_assoc()['RestaurantID'];
}

// Check if address exists (or insert one)
// For simplicity, we might skip address FK if it's nullable, but let's check schema.
// Usually AddressID is required.
// Let's just try to insert.

// Get max OrderID
$maxSql = "SELECT MAX(OrderID) as max_id FROM `order`";
$maxResult = $conn->query($maxSql);
$maxId = 0;
if ($maxResult && $row = $maxResult->fetch_assoc()) {
    $maxId = $row['max_id'];
}
$newOrderId = $maxId + 1;
// Force a random ID to avoid collision if max calculation is wrong
$newOrderId = rand(20000, 50000);
echo "Max ID: $maxId, New ID: $newOrderId<br>";

try {
    $sql = "INSERT INTO `order` (OrderID, UserID, RestaurantID, AddressID, TotalAmount, DeliveryFee, OrderStatus, OrderTime) 
            VALUES ($newOrderId, $userId, $restaurantId, $addrId, $totalAmount, $deliveryFee, '$status', '$orderTime')";

    if ($conn->query($sql)) {
        echo "Created test order for User $userId. Order ID: " . $newOrderId;
    } else {
        echo "Error creating order: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
?>
?>