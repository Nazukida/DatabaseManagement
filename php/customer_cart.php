<?php
require_once 'database.php';
require_once 'functions.php';

if (!isUserLoggedIn()) {
    header("Location: login.php");
    exit();
}

$userId = getCurrentUserId();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - YouShi LinLi</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="body container">
    <div class="unified-top-bar">
        <div class="top-bar-content">
            <span class="brand-name">YouShi LinLi</span>
            <div class="top-nav-links">
                <a href="index.html">Home</a>
                <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page active">
            <div class="checkout-header">
                <h2>Confirm Order</h2>
            </div>
            <div class="cart-list" id="cart-list-container">
                <?php
                // 查询用户的pending订单
                $sql = "
                SELECT 
                    o.OrderID,
                    r.RestaurantName,
                    o.TotalAmount,
                    COUNT(oi.MenuItemID) as item_count
                FROM `order` o
                JOIN restaurants r ON o.RestaurantID = r.RestaurantID
                LEFT JOIN order_items oi ON o.OrderID = oi.OrderID
                WHERE o.UserID = ? 
                  AND o.OrderStatus = 'pending'
                GROUP BY o.OrderID
                ";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $totalAmount = 0;
                $hasItems = false;
                
                if ($result && $result->num_rows > 0) {
                    $hasItems = true;
                    
                    while($order = $result->fetch_assoc()) {
                        $orderId = $order['OrderID'];
                        $restaurantName = safeOutput($order['RestaurantName']);
                        $orderTotal = formatPrice($order['TotalAmount']);
                        $itemCount = $order['item_count'];
                        $totalAmount += $order['TotalAmount'];
                        
                        // 查询订单详细商品
                        $itemSql = "
                        SELECT 
                            mi.ItemName,
                            oi.Quantity,
                            oi.UnitPrice,
                            (oi.Quantity * oi.UnitPrice) as subtotal
                        FROM order_items oi
                        JOIN menu_items mi ON oi.MenuItemID = mi.MenuItemID
                        WHERE oi.OrderID = ?
                        ";
                        
                        $itemStmt = $conn->prepare($itemSql);
                        $itemStmt->bind_param("i", $orderId);
                        $itemStmt->execute();
                        $itemResult = $itemStmt->get_result();
                        
                        echo <<<HTML
                        <div class="cart-restaurant-group">
                            <h4><i class="fas fa-store"></i> {$restaurantName}</h4>
                        HTML;
                        
                        while($item = $itemResult->fetch_assoc()) {
                            $itemName = safeOutput($item['ItemName']);
                            $quantity = $item['Quantity'];
                            $subtotal = formatPrice($item['subtotal']);
                            
                            echo <<<HTML
                            <div class="cart-item">
                                <div class="cart-item-name">{$itemName} × {$quantity}</div>
                                <div class="cart-item-price">¥{$subtotal}</div>
                            </div>
                            HTML;
                        }
                        
                        echo <<<HTML
                            <div style="padding: 10px; text-align: right; border-top: 1px solid #eee;">
                                <strong>Order Total: ¥{$orderTotal}</strong>
                                <button onclick="removeOrder({$orderId})" 
                                        style="margin-left: 10px; padding: 5px 10px; background: #ff4444; color: white; 
                                               border: none; border-radius: 3px; cursor: pointer;">
                                    Remove
                                </button>
                            </div>
                        </div>
                        HTML;
                    }
                } else {
                    echo '<div class="text-center" style="padding:20px; color:#999;">Cart is empty</div>';
                }
                ?>
            </div>
            
            <?php if ($hasItems): ?>
            <div class="checkout-footer">
                <div class="total-amount" id="cart-total-amount">
                    Total: ¥<?php echo formatPrice($totalAmount); ?>
                </div>
                <button class="btn-submit-order" id="btn-submit-order" onclick="checkout()">
                    Place Order
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="common-tab-bar container">
        <a href="customer_home.php" class="tab-item">
            <i class="fas fa-utensils"></i>
            <span>Home</span>
        </a>
        <a href="customer_orders.php" class="tab-item">
            <i class="fas fa-receipt"></i>
            <span>Orders</span>
        </a>
        <a href="customer_cart.php" class="tab-item active">
            <i class="fas fa-shopping-cart"></i>
            <span>Cart</span>
        </a>
        <a href="customer_profile.php" class="tab-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </div>

    <script>
        function removeOrder(orderId) {
            if (confirm('Are you sure you want to remove this order?')) {
                const formData = new FormData();
                formData.append('action', 'remove_order');
                formData.append('order_id', orderId);
                
                fetch('cart_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error removing order');
                    }
                });
            }
        }
        
        function checkout() {
            window.location.href = 'customer_payment.php';
        }
    </script>
</body>
</html>