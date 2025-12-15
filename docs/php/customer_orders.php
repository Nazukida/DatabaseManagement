<?php
require_once 'database.php';
require_once 'functions.php';

if (!isUserLoggedIn()) {
    header("Location: login.php");
    exit();
}

$userId = getCurrentUserId();

// 处理订单状态更新
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $orderId = $_POST['order_id'] ?? 0;
    
    if ($action == 'cancel_order' && $orderId) {
        // 验证订单属于当前用户
        $checkSql = "SELECT OrderID FROM `order` WHERE OrderID = ? AND UserID = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $orderId, $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            // 取消订单（仅当状态为pending或confirmed时可取消）
            $updateSql = "UPDATE `order` SET OrderStatus = 'cancelled' WHERE OrderID = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("i", $orderId);
            $updateStmt->execute();
        }
        header("Location: customer_orders.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - YouShi LinLi</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .order-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-preparing { background: #d4edda; color: #155724; }
        .status-on-the-way { background: #cce5ff; color: #004085; }
        .status-delivered { background: #d1e7dd; color: #0f5132; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .order-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body class="body container">
    <div class="unified-top-bar">
        <div class="top-bar-content">
            <span class="brand-name">YouShi LinLi</span>
            <div class="top-nav-links">
                <a href="../index.html">Home</a>
                <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page active">
            <div class="page-header" style="padding: 20px 0;">
                <h2>My Orders</h2>
            </div>
            
            <div class="order-list">
                <?php
                $sql = "
                SELECT 
                    o.OrderID,
                    o.OrderTime,
                    o.OrderStatus,
                    o.TotalAmount,
                    r.RestaurantName
                FROM `order` o
                JOIN restaurants r ON o.RestaurantID = r.RestaurantID
                WHERE o.UserID = ?
                ORDER BY o.OrderTime DESC
                ";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    while($order = $result->fetch_assoc()) {
                        $orderId = $order['OrderID'];
                        $restName = safeOutput($order['RestaurantName']);
                        $time = date('Y-m-d H:i', strtotime($order['OrderTime']));
                        $status = safeOutput($order['OrderStatus']);
                        $total = formatPrice($order['TotalAmount']);
                        
                        $statusClass = 'status-' . strtolower(str_replace(' ', '-', $status));
                        
                        echo <<<HTML
                        <div class="order-card">
                            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                                <h4 style="margin:0;">{$restName}</h4>
                                <span class="order-status {$statusClass}">{$status}</span>
                            </div>
                            <div style="color:#666; font-size:0.9em; margin-bottom:10px;">
                                <div>Order #{$orderId}</div>
                                <div>{$time}</div>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; padding-top:10px;">
                                <span style="font-weight:bold;">¥{$total}</span>
                                <div>
                        HTML;
                        
                        if ($status == 'pending' || $status == 'confirmed') {
                            echo <<<HTML
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="cancel_order">
                                        <input type="hidden" name="order_id" value="{$orderId}">
                                        <button type="submit" onclick="return confirm('Cancel this order?')" style="background:#ff4444; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">Cancel</button>
                                    </form>
                            HTML;
                        }
                        
                        echo <<<HTML
                                </div>
                            </div>
                        </div>
                        HTML;
                    }
                } else {
                    echo "<p style='text-align:center; padding:20px; color:#666;'>No orders found.</p>";
                }
                ?>
            </div>
        </div>
    </div>

    <div class="common-tab-bar container">
        <a href="customer_home.php?user_id=<?php echo $userId; ?>" class="tab-item">
            <i class="fas fa-utensils"></i>
            <span>Home</span>
        </a>
        <a href="customer_orders.php?user_id=<?php echo $userId; ?>" class="tab-item active">
            <i class="fas fa-receipt"></i>
            <span>Orders</span>
        </a>
        <a href="customer_cart.php?user_id=<?php echo $userId; ?>" class="tab-item">
            <i class="fas fa-shopping-cart"></i>
            <span>Cart</span>
        </a>
        <a href="customer_profile.php?user_id=<?php echo $userId; ?>" class="tab-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </div>
</body>
</html>