<?php
// php/rider_api.php
// Refactored for robustness
require_once 'db.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Enable error reporting but catch exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$action = $_GET['action'] ?? '';
$riderId = isset($_REQUEST['rider_id']) ? intval($_REQUEST['rider_id']) : 0;

try {
    switch ($action) {
        case 'get_status':
            // Get rider status
            $stmt = $conn->prepare("SELECT CurrentStatus FROM riders WHERE RiderID = ?");
            $stmt->bind_param("i", $riderId);
            $stmt->execute();
            $result = $stmt->get_result();
            $status = 'Offline';
            if ($row = $result->fetch_assoc()) {
                $status = $row['CurrentStatus'];
            }
            
            // Get active orders count
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM `order` WHERE RiderID = ? AND OrderStatus IN ('Pending', 'Delivering')");
            $stmt->bind_param("i", $riderId);
            $stmt->execute();
            $active = $stmt->get_result()->fetch_assoc()['count'];
            
            // Get completed stats
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
            $status = $_POST['status'] ?? 'Offline';
            $stmt = $conn->prepare("UPDATE riders SET CurrentStatus = ? WHERE RiderID = ?");
            $stmt->bind_param("si", $status, $riderId);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'get_available_orders':
            // Fetch orders waiting for a rider
            $sql = "SELECT o.OrderID, o.TotalAmount, o.DeliveryFee, r.RestaurantName, r.DeliveryArea as PickupAddress 
                    FROM `order` o
                    LEFT JOIN restaurants r ON o.RestaurantID = r.RestaurantID
                    WHERE (o.RiderID IS NULL OR o.RiderID = 0) 
                    AND o.OrderStatus = 'confirmed'
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
            $orderId = intval($_POST['order_id']);
            
            // Check active limit
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM `order` WHERE RiderID = ? AND OrderStatus IN ('Pending', 'Delivering')");
            $stmt->bind_param("i", $riderId);
            $stmt->execute();
            $count = $stmt->get_result()->fetch_assoc()['count'];
            
            if ($count >= 5) {
                throw new Exception('You have reached the maximum limit of 5 active orders.');
            }
            
            // Atomic update to assign order
            $stmt = $conn->prepare("UPDATE `order` SET RiderID = ?, OrderStatus = 'Delivering' WHERE OrderID = ? AND (RiderID IS NULL OR RiderID = 0)");
            $stmt->bind_param("ii", $riderId, $orderId);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Order is no longer available.');
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
            $orderId = intval($_POST['order_id']);
            $stmt = $conn->prepare("UPDATE `order` SET OrderStatus = 'Completed' WHERE OrderID = ? AND RiderID = ?");
            $stmt->bind_param("ii", $orderId, $riderId);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to complete order.');
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
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>