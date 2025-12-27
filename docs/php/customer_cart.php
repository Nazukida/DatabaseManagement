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
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <div class="checkout-header" style="padding: 20px 0;">
                <h2>Your Cart</h2>
            </div>
            <div class="cart-list" id="cart-list-container">
                <?php
                // Query user's pending orders
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
                
                if ($result && $result->num_rows > 0) {
                    while($order = $result->fetch_assoc()) {
                        $orderId = $order['OrderID'];
                        $restaurantName = safeOutput($order['RestaurantName']);
                        $orderTotal = formatPrice($order['TotalAmount']);
                        
                        // Query order item details
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
                        <div class="cart-restaurant-group" style="background:white; padding:15px; border-radius:8px; margin-bottom:20px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
                            <h4 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;"><i class="fas fa-store"></i> {$restaurantName}</h4>
                            <div class="cart-items">
                        HTML;
                        
                        while($item = $itemResult->fetch_assoc()) {
                            $itemName = safeOutput($item['ItemName']);
                            $quantity = $item['Quantity'];
                            $subtotal = formatPrice($item['subtotal']);
                            
                            echo <<<HTML
                                <div class="cart-item" style="display:flex; justify-content:space-between; margin-bottom:10px;">
                                    <span>{$itemName} x {$quantity}</span>
                                    <span>¥{$subtotal}</span>
                                </div>
                            HTML;
                        }
                        
                        echo <<<HTML
                            </div>
                            <div class="cart-footer" style="border-top:1px solid #eee; padding-top:10px; margin-top:10px; display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-weight:bold;">Total: ¥{$orderTotal}</span>
                                <div style="display:flex; gap:10px;">
                                    <button onclick="removeOrder({$orderId})" style="padding:8px 15px; border-radius:4px; border:none; background:#ff4444; color:white; cursor:pointer;">Remove</button>
                                    <a href="customer_payment.php?order_id={$orderId}" class="btn-primary" style="padding:8px 15px; border-radius:4px; border:none; background:#ff4d00; color:white; cursor:pointer; text-decoration:none;">Checkout</a>
                                </div>
                            </div>
                        </div>
                        HTML;
                    }
                } else {
                    echo "<div style='text-align:center; padding:40px; color:#666;'>
                            <i class='fas fa-shopping-cart' style='font-size:40px; margin-bottom:10px;'></i>
                            <p>Your cart is empty.</p>
                            <a href='customer_home.php' style='color:#ff4d00;'>Go Shopping</a>
                          </div>";
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
        <a href="customer_orders.php?user_id=<?php echo $userId; ?>" class="tab-item">
            <i class="fas fa-receipt"></i>
            <span>Orders</span>
        </a>
        <a href="customer_cart.php?user_id=<?php echo $userId; ?>" class="tab-item active">
            <i class="fas fa-shopping-cart"></i>
            <span>Cart</span>
        </a>
        <a href="customer_profile.php?user_id=<?php echo $userId; ?>" class="tab-item">
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
                        alert('Order removed successfully!\nQuery Time: ' + data.query_time + ' s');
                        location.reload();
                    } else {
                        alert(data.message || 'Error removing order');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing order');
                });
            }
        }
    </script>
</body>
</html>