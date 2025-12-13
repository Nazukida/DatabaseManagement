<?php
// php/rider_api.php
include 'db.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$action = $_GET['action'] ?? '';
$riderId = isset($_REQUEST['rider_id']) ? intval($_REQUEST['rider_id']) : 0;

if ($riderId <= 0 && $action !== 'get_available_orders') {
    // For demo purposes, if no rider ID is provided, we might default to a test ID or fail.
    // Let's fail to encourage providing an ID.
    // echo json_encode(['success' => false, 'message' => 'Rider ID required']);
    // exit;
}

switch ($action) {
    case 'get_status':
        // Get rider status
        $stmt = $conn->prepare("SELECT CurrentStatus FROM riders WHERE RiderID = ?");
        $stmt->bind_param("i", $riderId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $status = $row['CurrentStatus'];
        } else {
            $status = 'Offline'; // Default if rider not found
        }
        
        // Get active orders count (Delivering or Pending assigned to rider)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM `order` WHERE RiderID = ? AND OrderStatus IN ('Pending', 'Delivering')");
        $stmt->bind_param("i", $riderId);
        $stmt->execute();
        $active = $stmt->get_result()->fetch_assoc()['count'];
        
        // Get completed orders count and earnings
        $stmt = $conn->prepare("SELECT COUNT(*) as count, SUM(DeliveryFee) as earnings FROM `order` WHERE RiderID = ? AND OrderStatus = 'Completed'");
        $stmt->bind_param("i", $riderId);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'status' => $status,
            'active_count' => $active,
            'completed_count' => $stats['count'],
            'total_earnings' => $stats['earnings'] ? number_format($stats['earnings'], 2, '.', '') : '0.00'
        ]);
        break;

    case 'toggle_status':
        $status = $_POST['status'] ?? 'Offline'; // 'Online' or 'Offline'
        $stmt = $conn->prepare("UPDATE riders SET CurrentStatus = ? WHERE RiderID = ?");
        $stmt->bind_param("si", $status, $riderId);
        $success = $stmt->execute();
        echo json_encode(['success' => $success]);
        break;

    case 'get_available_orders':
        // Orders with no rider and status Pending
        // We assume 'Pending' means ready for assignment.
        // Also check if rider is online? The frontend handles that check usually.
        
        $sql = "SELECT o.OrderID, o.TotalAmount, o.DeliveryFee, r.RestaurantName, r.DeliveryArea as PickupAddress 
                FROM `order` o
                LEFT JOIN restaurants r ON o.RestaurantID = r.RestaurantID
                WHERE (o.RiderID IS NULL OR o.RiderID = 0) 
                AND o.OrderStatus = 'Pending'
                ORDER BY o.OrderTime DESC
                LIMIT 20";
        
        $result = $conn->query($sql);
        $orders = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        echo json_encode(['success' => true, 'orders' => $orders]);
        break;

    case 'accept_order':
        $orderId = $_POST['order_id'];
        
        // Check active orders limit (Max 5)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM `order` WHERE RiderID = ? AND OrderStatus IN ('Pending', 'Delivering')");
        $stmt->bind_param("i", $riderId);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        
        if ($count >= 5) {
            echo json_encode(['success' => false, 'message' => 'You have reached the maximum limit of 5 active orders.']);
            exit;
        }
        
        // Assign order
        // We change status to 'Delivering' immediately or keep 'Pending' but assigned?
        // Let's set to 'Delivering' to indicate it's being handled.
        $stmt = $conn->prepare("UPDATE `order` SET RiderID = ?, OrderStatus = 'Delivering' WHERE OrderID = ? AND (RiderID IS NULL OR RiderID = 0)");
        $stmt->bind_param("ii", $riderId, $orderId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Order is no longer available.']);
        }
        break;

    case 'get_active_orders':
        $stmt = $conn->prepare("SELECT o.OrderID, o.TotalAmount, o.OrderStatus, r.RestaurantName, r.DeliveryArea as PickupAddress, da.FullAddress as DeliveryAddress, u.Username as CustomerName, u.PhoneNumber as CustomerPhone
                                FROM `order` o
                                LEFT JOIN restaurants r ON o.RestaurantID = r.RestaurantID
                                LEFT JOIN delivery_addresses da ON o.AddressID = da.AddressID
                                LEFT JOIN users u ON o.UserID = u.UserID
                                WHERE o.RiderID = ? AND o.OrderStatus IN ('Pending', 'Delivering')");
        $stmt->bind_param("i", $riderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        echo json_encode(['success' => true, 'orders' => $orders]);
        break;

    case 'complete_order':
        $orderId = $_POST['order_id'];
        $stmt = $conn->prepare("UPDATE `order` SET OrderStatus = 'Completed' WHERE OrderID = ? AND RiderID = ?");
        $stmt->bind_param("ii", $orderId, $riderId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to complete order.']);
        }
        break;
        
    case 'get_history':
        $stmt = $conn->prepare("SELECT o.OrderID, o.TotalAmount, o.DeliveryFee, o.OrderTime, r.RestaurantName 
                                FROM `order` o
                                LEFT JOIN restaurants r ON o.RestaurantID = r.RestaurantID
                                WHERE o.RiderID = ? AND o.OrderStatus = 'Completed'
                                ORDER BY o.OrderTime DESC LIMIT 50");
        $stmt->bind_param("i", $riderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        echo json_encode(['success' => true, 'orders' => $orders]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>