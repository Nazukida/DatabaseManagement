<?php
// php/test_create_order_v3.php
require_once 'db.php';

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

echo "<h1>Order Creation Test (FK Constraint Check)</h1>";

try {
    $conn->begin_transaction();

    // 1. Get a valid UserID (assuming 49983 exists from dump)
    $userId = 49983; 
    // 2. Get a valid RestaurantID (need to query one)
    $res = $conn->query("SELECT RestaurantID FROM restaurants LIMIT 1");
    if (!$res || $res->num_rows == 0) throw new Exception("No restaurants found");
    $restaurantId = $res->fetch_assoc()['RestaurantID'];

    // 3. Generate OrderID
    $res = $conn->query("SELECT MAX(OrderID) as max_id FROM `order`");
    $newOrderId = ($res->fetch_assoc()['max_id'] ?? 0) + 1;

    echo "Attempting to create Order #$newOrderId for User $userId, Restaurant $restaurantId...<br>";

    // 4. Insert with RiderID = NULL
    $stmt = $conn->prepare("INSERT INTO `order` (OrderID, UserID, RestaurantID, AddressID, TotalAmount, DeliveryFee, OrderStatus, OrderTime, PaymentMethod, RiderID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $addressId = 1; // Assuming 1 exists, or we might need to fetch one
    // Check address
    $checkAddr = $conn->query("SELECT AddressID FROM delivery_addresses LIMIT 1");
    if ($checkAddr && $row = $checkAddr->fetch_assoc()) {
        $addressId = $row['AddressID'];
    } else {
        // Create dummy address if needed, but let's hope 1 exists or we use existing
        echo "Warning: No address found, using ID 1 which might fail.<br>";
    }

    $totalAmount = 50.00;
    $deliveryFee = 5.00;
    $status = 'confirmed';
    $orderTime = date('Y-m-d H:i:s');
    $paymentMethod = 'Test';
    $riderId = null; // THE KEY FIX

    $stmt->bind_param("iiiiddsssi", $newOrderId, $userId, $restaurantId, $addressId, $totalAmount, $deliveryFee, $status, $orderTime, $paymentMethod, $riderId);
    
    $stmt->execute();
    echo "<strong style='color:green'>SUCCESS: Order created with RiderID = NULL.</strong><br>";
    
    $conn->commit();
    
    echo "<h3>Verification:</h3>";
    $check = $conn->query("SELECT * FROM `order` WHERE OrderID = $newOrderId");
    $row = $check->fetch_assoc();
    echo "Order ID: " . $row['OrderID'] . "<br>";
    echo "Rider ID: " . var_export($row['RiderID'], true) . " (Should be NULL)<br>";
    echo "Status: " . $row['OrderStatus'] . "<br>";

} catch (Exception $e) {
    $conn->rollback();
    echo "<strong style='color:red'>FAILED: " . $e->getMessage() . "</strong><br>";
    echo "Possible Cause: Foreign Key Constraint Violation. Check if UserID, RestaurantID, or AddressID exist.<br>";
}
?>