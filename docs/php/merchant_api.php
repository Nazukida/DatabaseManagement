<?php
// php/merchant_api.php
require_once 'db.php';

// Configuration
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-cache, no-store, must-revalidate");
ini_set('display_errors', 0); // Disable HTML error output
error_reporting(E_ALL);

// Logging Helper
function logDebug($message) {
    $logFile = 'debug_merchant.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Response Helper
function sendJson($success, $data = [], $message = '') {
    $response = ['success' => $success];
    if (!empty($data)) $response = array_merge($response, $data);
    if (!empty($message)) $response['message'] = $message;
    echo json_encode($response);
    exit;
}

// Main Logic
try {
    $action = $_GET['action'] ?? '';
    $restaurantId = isset($_REQUEST['restaurant_id']) ? intval($_REQUEST['restaurant_id']) : 0;

    logDebug("Request: Action=$action, RID=$restaurantId, Method=" . $_SERVER['REQUEST_METHOD']);

    if ($restaurantId <= 0 && $action !== 'login') {
        sendJson(false, [], 'Invalid Restaurant ID');
    }

    switch ($action) {
        case 'get_dashboard_stats':
            $stmt = $conn->prepare("SELECT RestaurantName, BusinessStatus FROM restaurants WHERE RestaurantID = ?");
            $stmt->bind_param("i", $restaurantId);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM `order` WHERE RestaurantID = ? AND OrderStatus = 'Pending'");
            $stmt->bind_param("i", $restaurantId);
            $stmt->execute();
            $pending = $stmt->get_result()->fetch_assoc()['count'];

            sendJson(true, [
                'restaurant_name' => $res['RestaurantName'],
                'status' => $res['BusinessStatus'],
                'pending_orders' => $pending
            ]);
            break;

        case 'get_profile':
            $stmt = $conn->prepare("SELECT RestaurantName, ContactPhone, Address, Description, DeliveryFee, MinimumOrderAmount, BusinessStatus FROM restaurants WHERE RestaurantID = ?");
            $stmt->bind_param("i", $restaurantId);
            $stmt->execute();
            $profile = $stmt->get_result()->fetch_assoc();
            sendJson(true, ['profile' => $profile]);
            break;

        case 'update_profile':
            $name = $_POST['name'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $address = $_POST['address'] ?? '';
            $desc = $_POST['description'] ?? '';
            $fee = floatval($_POST['delivery_fee'] ?? 0);
            $min = floatval($_POST['min_order'] ?? 0);
            $status = $_POST['status'] ?? 'Open';

            $stmt = $conn->prepare("UPDATE restaurants SET RestaurantName=?, ContactPhone=?, Address=?, Description=?, DeliveryFee=?, MinimumOrderAmount=?, BusinessStatus=? WHERE RestaurantID=?");
            $stmt->bind_param("ssssddsi", $name, $phone, $address, $desc, $fee, $min, $status, $restaurantId);
            
            if ($stmt->execute()) {
                sendJson(true, [], 'Profile updated');
            } else {
                throw new Exception($stmt->error);
            }
            break;

        case 'get_menu':
            // Filter out 'Deleted' items
            $stmt = $conn->prepare("SELECT * FROM menu_items WHERE RestaurantID = ? AND StockStatus != 'Deleted' ORDER BY MenuItemID DESC");
            $stmt->bind_param("i", $restaurantId);
            $stmt->execute();
            $result = $stmt->get_result();
            $menu = [];
            while ($row = $result->fetch_assoc()) {
                $menu[] = $row;
            }
            sendJson(true, ['menu' => $menu]);
            break;

        case 'add_menu_item':
            $name = $_POST['name'] ?? '';
            $price = floatval($_POST['price'] ?? 0);
            $desc = $_POST['description'] ?? '';
            
            if (empty($name)) sendJson(false, [], 'Item name is required');

            // Manual ID generation because AUTO_INCREMENT is missing in DB
            $idRes = $conn->query("SELECT MAX(MenuItemID) as max_id FROM menu_items");
            $row = $idRes->fetch_assoc();
            $newId = ($row['max_id'] ?? 0) + 1;

            $stmt = $conn->prepare("INSERT INTO menu_items (MenuItemID, ItemName, Price, ItemDescription, RestaurantID, StockStatus) VALUES (?, ?, ?, ?, ?, 'In Stock')");
            $stmt->bind_param("isdsi", $newId, $name, $price, $desc, $restaurantId);
            
            if ($stmt->execute()) {
                sendJson(true, [], 'Item added');
            } else {
                throw new Exception($stmt->error);
            }
            break;

        case 'update_menu_item':
            $itemId = intval($_POST['item_id'] ?? 0);
            $name = $_POST['name'] ?? '';
            $price = floatval($_POST['price'] ?? 0);
            $desc = $_POST['description'] ?? '';

            if ($itemId <= 0) sendJson(false, [], 'Invalid Item ID');
            
            $stmt = $conn->prepare("UPDATE menu_items SET ItemName=?, Price=?, ItemDescription=? WHERE MenuItemID=? AND RestaurantID=?");
            $stmt->bind_param("sdsii", $name, $price, $desc, $itemId, $restaurantId);
            
            if ($stmt->execute()) {
                sendJson(true, [], 'Item updated');
            } else {
                throw new Exception($stmt->error);
            }
            break;
        case 'delete_menu_item':
            $itemId = intval($_POST['item_id'] ?? 0);
            
            if ($itemId <= 0) sendJson(false, [], 'Invalid Item ID');

            // Soft delete: Mark as 'Deleted' instead of removing row to preserve order history
            $stmt = $conn->prepare("UPDATE menu_items SET StockStatus = 'Deleted' WHERE MenuItemID=? AND RestaurantID=?");
            $stmt->bind_param("ii", $itemId, $restaurantId);
            
            if ($stmt->execute()) {
                sendJson(true, [], 'Item deleted');
            } else {
                throw new Exception($stmt->error);
            }
            break;
            break;

        case 'get_orders':
            $stmt = $conn->prepare("SELECT * FROM `order` WHERE RestaurantID = ? ORDER BY OrderTime DESC");
            $stmt->bind_param("i", $restaurantId);
            $stmt->execute();
            $result = $stmt->get_result();
            $orders = [];
            
            while ($row = $result->fetch_assoc()) {
                $oid = $row['OrderID'];
                // Get items for this order
                $itemStmt = $conn->prepare("SELECT oi.Quantity, m.ItemName 
                                            FROM order_items oi 
                                            JOIN menu_items m ON oi.MenuItemID = m.MenuItemID 
                                            WHERE oi.OrderID = ?");
                $itemStmt->bind_param("i", $oid);
                $itemStmt->execute();
                $itemsRes = $itemStmt->get_result();
                $items = [];
                while ($item = $itemsRes->fetch_assoc()) {
                    $items[] = $item;
                }
                $row['items'] = $items;
                $orders[] = $row;
            }
            sendJson(true, ['orders' => $orders]);
            break;

        case 'update_order_status':
            $orderId = intval($_POST['order_id'] ?? 0);
            $newStatus = $_POST['status'] ?? '';
            
            $stmt = $conn->prepare("UPDATE `order` SET OrderStatus = ? WHERE OrderID = ? AND RestaurantID = ?");
            $stmt->bind_param("sii", $newStatus, $orderId, $restaurantId);
            
            if ($stmt->execute()) {
                sendJson(true, [], 'Order status updated');
            } else {
                throw new Exception($stmt->error);
            }
            break;

        case 'get_sales':
            // Total stats
            $stmt = $conn->prepare("SELECT COUNT(*) as total_orders, SUM(TotalAmount) as total_revenue 
                                    FROM `order` 
                                    WHERE RestaurantID = ? AND OrderStatus = 'Completed'");
            $stmt->bind_param("i", $restaurantId);
            $stmt->execute();
            $stats = $stmt->get_result()->fetch_assoc();
            
            // Item sales
            $itemStmt = $conn->prepare("
                SELECT m.ItemName, SUM(oi.Quantity) as total_sold, SUM(oi.Quantity * oi.UnitPrice) as item_revenue
                FROM order_items oi
                JOIN `order` o ON oi.OrderID = o.OrderID
                JOIN menu_items m ON oi.MenuItemID = m.MenuItemID
                WHERE o.RestaurantID = ? AND o.OrderStatus = 'Completed'
                GROUP BY m.MenuItemID
                ORDER BY total_sold DESC
            ");
            $itemStmt->bind_param("i", $restaurantId);
            $itemStmt->execute();
            $itemResult = $itemStmt->get_result();
            $itemSales = [];
            while ($row = $itemResult->fetch_assoc()) {
                $itemSales[] = $row;
            }

            sendJson(true, [
                'total_orders' => $stats['total_orders'] ?? 0,
                'total_revenue' => $stats['total_revenue'] ? number_format($stats['total_revenue'], 2, '.', '') : '0.00',
                'item_sales' => $itemSales
            ]);
            break;

        default:
            sendJson(false, [], 'Invalid action: ' . $action);
    }

} catch (Exception $e) {
    logDebug("Exception: " . $e->getMessage());
    sendJson(false, [], 'Server Error: ' . $e->getMessage());
}
?>