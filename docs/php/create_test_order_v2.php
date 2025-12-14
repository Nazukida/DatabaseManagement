<?php
require_once 'db.php';

$userId = 24;
$restaurantId = 1;
$addressId = 1;
$totalAmount = 50.00;
$deliveryFee = 5.00;
$status = 'confirmed';
$orderTime = date('Y-m-d H:i:s');

// Get max OrderID
$maxSql = "SELECT MAX(OrderID) as max_id FROM `order`";
$maxResult = $conn->query($maxSql);
$maxId = 0;
if ($maxResult && $row = $maxResult->fetch_assoc()) {
    $maxId = $row['max_id'];
}
$newOrderId = $maxId + 1;
// Force random to be sure
$newOrderId = rand(60000, 90000);

echo "Attempting to create order $newOrderId for user $userId...<br>";

$sql = "INSERT INTO `order` (OrderID, UserID, RestaurantID, AddressID, TotalAmount, DeliveryFee, OrderStatus, OrderTime) 
        VALUES ($newOrderId, $userId, $restaurantId, $addressId, $totalAmount, $deliveryFee, '$status', '$orderTime')";

if ($conn->query($sql)) {
    echo "Success! Created test order for User $userId. Order ID: " . $newOrderId;
} else {
    echo "Error creating order: " . $conn->error;
}
?>