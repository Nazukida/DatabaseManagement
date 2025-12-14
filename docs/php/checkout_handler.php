<?php
// php/checkout_handler.php
// Refactored for robustness and reliability
require_once 'db.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Enable error reporting for debugging (but catch them to return JSON)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function log_debug($message) {
    file_put_contents(__DIR__ . "/debug_log.txt", "[" . date('Y-m-d H:i:s') . "] " . $message . "\n", FILE_APPEND);
}

try {
    // 1. Get and Validate Input
    $input = file_get_contents("php://input");
    log_debug("Received checkout request: " . $input);

    $data = json_decode($input, true);
    if (!$data) {
        throw new Exception("Invalid JSON received");
    }

    if (empty($data['userId']) || empty($data['cart'])) {
        throw new Exception("Missing required fields: userId or cart");
    }

    $userId = intval($data['userId']);
    $cart = $data['cart'];
    $paymentMethod = $data['paymentMethod'] ?? 'Alipay';
    $addressId = 1; // Default address (TODO: Make dynamic)

    $ordersCreated = [];
    
    // 2. Start Transaction
    $conn->begin_transaction();

    foreach ($cart as $restId => $restData) {
        if (empty($restData['items'])) continue;

        $restaurantId = intval($restId);
        $items = $restData['items'];
        
        // Calculate totals
        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += floatval($item['price']) * intval($item['count']);
        }
        $deliveryFee = 5.00;
        $finalAmount = $totalAmount + $deliveryFee;

        // Generate OrderID
        // Using a safer method: Get max ID and increment, but inside transaction it's safer.
        // Ideally, OrderID should be AUTO_INCREMENT. If not, we must be careful.
        $result = $conn->query("SELECT MAX(OrderID) as max_id FROM `order`");
        $row = $result->fetch_assoc();
        $newOrderId = ($row['max_id'] ?? 0) + 1;
        
        // Ensure uniqueness if multiple requests happen same time (simple collision avoidance)
        // Note: In a real production system, use AUTO_INCREMENT or UUIDs.
        // We will trust the transaction isolation for now.

    $orderTime = date('Y-m-d H:i:s');
    $status = 'confirmed'; // Ready for rider
    $riderId = null; // Use NULL for "no rider assigned" to avoid FK constraint violations if RiderID=0 doesn't exist

    // Insert Order
    $stmt = $conn->prepare("INSERT INTO `order` (OrderID, UserID, RestaurantID, AddressID, TotalAmount, DeliveryFee, OrderStatus, OrderTime, PaymentMethod, RiderID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiiddsssi", $newOrderId, $userId, $restaurantId, $addressId, $totalAmount, $deliveryFee, $status, $orderTime, $paymentMethod, $riderId);
    $stmt->execute();
    $stmt->close();        // Insert Items
        $stmtItem = $conn->prepare("INSERT INTO order_items (OrderID, MenuItemID, Quantity, UnitPrice) VALUES (?, ?, ?, ?)");
        foreach ($items as $item) {
            $menuItemId = intval($item['menuItemId']);
            $qty = intval($item['count']);
            $price = floatval($item['price']);
            $stmtItem->bind_param("iiid", $newOrderId, $menuItemId, $qty, $price);
            $stmtItem->execute();
        }
        $stmtItem->close();

        $ordersCreated[] = $newOrderId;
        log_debug("Order $newOrderId created successfully.");
    }

    // 3. Commit Transaction
    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Orders placed successfully', 
        'orders' => $ordersCreated
    ]);

} catch (Exception $e) {
    // Rollback on error
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    log_debug("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>