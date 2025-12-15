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
    <link rel="stylesheet" href="style.css">
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
        
        .order-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-cancel {
            background: #ff4444;
            color: white;
        }
        
        .btn-review {
            background: #4CAF50;
            color: white;
        }
        
        .btn-reorder {
            background: #2196F3;
            color: white;
        }
        
        .order-details {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            display: none;
        }
        
        .order-details.show {
            display: block;
        }
        
        .order-item-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item-row:last-child {
            border-bottom: none;
        }
        
        .filter-section {
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .filter-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        
        .filter-btn {
            padding: 5px 15px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-btn.active {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        
        .search-box {
            flex: 1;
            min-width: 200px;
        }
        
        .search-input {
            width: 100%;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
        }
        
        .stats-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
        }
    </style>
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
            <div class="page-header">
                <h2>My Orders</h2>
            </div>
            
            <div class="filter-section">
                <div class="filter-row">
                    <button onclick="filterOrders('all')" class="filter-btn active">All</button>
                    <button onclick="filterOrders('pending')" class="filter-btn">Pending</button>
                    <button onclick="filterOrders('confirmed')" class="filter-btn">Confirmed</button>
                    <button onclick="filterOrders('preparing')" class="filter-btn">Preparing</button>
                    <button onclick="filterOrders('on-the-way')" class="filter-btn">On the Way</button>
                    <button onclick="filterOrders('delivered')" class="filter-btn">Delivered</button>
                    <button onclick="filterOrders('cancelled')" class="filter-btn">Cancelled</button>
                </div>
                
                <div class="filter-row">
                    <div class="search-box">
                        <input type="text" class="search-input" id="search-input" 
                               placeholder="Search by restaurant name or order ID..." 
                               onkeyup="searchOrders()">
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <select id="sort-select" onchange="sortOrders()" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                            <option value="newest">Sort by: Newest First</option>
                            <option value="oldest">Sort by: Oldest First</option>
                            <option value="price_high">Sort by: Highest Price</option>
                            <option value="price_low">Sort by: Lowest Price</option>
                        </select>
                    </div>
                </div>
                
                <?php
                // 获取用户订单统计
                $statsSql = "
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN OrderStatus = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN OrderStatus = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN OrderStatus = 'preparing' THEN 1 ELSE 0 END) as preparing,
                    SUM(CASE WHEN OrderStatus = 'on the way' THEN 1 ELSE 0 END) as on_the_way,
                    SUM(CASE WHEN OrderStatus = 'delivered' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN OrderStatus = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(TotalAmount) as total_spent
                FROM `order`
                WHERE UserID = ?
                ";
                
                $statsStmt = $conn->prepare($statsSql);
                $statsStmt->bind_param("i", $userId);
                $statsStmt->execute();
                $statsResult = $statsStmt->get_result();
                $stats = $statsResult->fetch_assoc();
                ?>
                
                <div class="stats-box">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">¥<?php echo formatPrice($stats['total_spent'] ?? 0); ?></div>
                        <div class="stat-label">Total Spent</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $stats['pending'] ?? 0; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $stats['delivered'] ?? 0; ?></div>
                        <div class="stat-label">Delivered</div>
                    </div>
                </div>
            </div>
            
            <div class="order-empty" id="order-empty-state" style="display:none;">
                <i class="fas fa-box-open"></i>
                <p>No Orders Yet</p>
                <p class="order-empty-tip">Go ahead and order some delicious food～</p>
                <a href="customer_home.php" style="display:inline-block; margin-top:20px; padding:10px 20px; background:#4CAF50; color:white; text-decoration:none; border-radius:5px;">
                    <i class="fas fa-utensils"></i> Browse Restaurants
                </a>
            </div>
            
            <div id="order-history-container">
                <?php
                // 查询用户的所有订单
                $sql = "
                SELECT 
                    o.OrderID,
                    r.RestaurantName,
                    r.RestaurantID,
                    o.OrderTime,
                    o.TotalAmount,
                    o.OrderStatus,
                    o.ExpectedDeliveryTime,
                    o.PaymentMethod
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
                        $restaurantName = safeOutput($order['RestaurantName']);
                        $restaurantId = $order['RestaurantID'];
                        $orderTime = date('Y-m-d H:i', strtotime($order['OrderTime']));
                        $totalAmount = formatPrice($order['TotalAmount']);
                        $orderStatus = safeOutput($order['OrderStatus']);
                        $expectedTime = $order['ExpectedDeliveryTime'] ? date('Y-m-d H:i', strtotime($order['ExpectedDeliveryTime'])) : '--';
                        $paymentMethod = safeOutput($order['PaymentMethod'] ?? 'Not specified');
                        
                        // 获取订单项数量
                        $itemCountSql = "SELECT COUNT(*) as item_count FROM order_items WHERE OrderID = ?";
                        $itemCountStmt = $conn->prepare($itemCountSql);
                        $itemCountStmt->bind_param("i", $orderId);
                        $itemCountStmt->execute();
                        $itemCountResult = $itemCountStmt->get_result();
                        $itemCountRow = $itemCountResult->fetch_assoc();
                        $itemCount = $itemCountRow['item_count'];
                        
                        // 状态标签类
                        $statusClass = "status-" . strtolower(str_replace(' ', '-', $orderStatus));
                        
                        echo <<<HTML
                        <div class="order-card" data-status="{$orderStatus}" data-restaurant="{$restaurantName}" 
                             data-amount="{$order['TotalAmount']}" data-time="{$order['OrderTime']}"
                             style="background:white; padding:15px; border-radius:10px; margin-bottom:15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <div>
                                    <h3 style="margin: 0; font-size: 16px;">{$restaurantName}</h3>
                                    <div style="color:#666; font-size:12px; margin-top:2px;">
                                        Order #{$orderId}
                                    </div>
                                </div>
                                <span class="order-status {$statusClass}">{$orderStatus}</span>
                            </div>
                            
                            <div style="color:#666; font-size:13px; margin-bottom:8px;">
                                <i class="far fa-calendar"></i> {$orderTime} 
                                <span style="margin-left: 10px;"><i class="far fa-clock"></i> Expected: {$expectedTime}</span>
                            </div>
                            
                            <div style="color:#666; font-size:13px; margin-bottom:8px;">
                                <i class="fas fa-box"></i> {$itemCount} items
                                <span style="margin-left: 10px;"><i class="fas fa-credit-card"></i> {$paymentMethod}</span>
                            </div>
                            
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px; padding-top:10px; border-top:1px solid #eee;">
                                <span style="font-weight:bold; font-size:16px;">¥{$totalAmount}</span>
                                
                                <div style="display: flex; gap: 10px;">
                                    <button onclick="toggleOrderDetails({$orderId})" 
                                            style="background:#f0f0f0; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">
                                        <i class="fas fa-info-circle"></i> Details
                                    </button>
                        HTML;
                        
                        // 根据订单状态显示不同的操作按钮
                        if ($orderStatus == 'pending' || $orderStatus == 'confirmed') {
                            echo <<<HTML
                                    <form method="POST" style="display:inline;" 
                                          onsubmit="return confirm('Are you sure you want to cancel this order?')">
                                        <input type="hidden" name="action" value="cancel_order">
                                        <input type="hidden" name="order_id" value="{$orderId}">
                                        <button type="submit" class="action-btn btn-cancel">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </form>
                            HTML;
                        }
                        
                        if ($orderStatus == 'delivered') {
                            // 检查是否已经评价过
                            $reviewSql = "SELECT ReviewID FROM review WHERE OrderID = ?";
                            $reviewStmt = $conn->prepare($reviewSql);
                            $reviewStmt->bind_param("i", $orderId);
                            $reviewStmt->execute();
                            $reviewResult = $reviewStmt->get_result();
                            
                            if ($reviewResult->num_rows == 0) {
                                echo <<<HTML
                                        <button onclick="submitReview({$orderId})" class="action-btn btn-review">
                                            <i class="fas fa-star"></i> Review
                                        </button>
                                HTML;
                            }
                            
                            echo <<<HTML
                                    <button onclick="reorder({$orderId})" class="action-btn btn-reorder">
                                        <i class="fas fa-redo"></i> Reorder
                                    </button>
                            HTML;
                        }
                        
                        echo <<<HTML
                                </div>
                            </div>
                            
                            <div id="order-details-{$orderId}" class="order-details">
                                <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 14px;">Order Details</h4>
                        HTML;
                        
                        // 查询订单详细商品
                        $itemsSql = "
                        SELECT 
                            mi.ItemName,
                            oi.Quantity,
                            oi.UnitPrice,
                            (oi.Quantity * oi.UnitPrice) as subtotal
                        FROM order_items oi
                        JOIN menu_items mi ON oi.MenuItemID = mi.MenuItemID
                        WHERE oi.OrderID = ?
                        ";
                        
                        $itemsStmt = $conn->prepare($itemsSql);
                        $itemsStmt->bind_param("i", $orderId);
                        $itemsStmt->execute();
                        $itemsResult = $itemsStmt->get_result();
                        
                        $subtotal = 0;
                        while($item = $itemsResult->fetch_assoc()) {
                            $itemName = safeOutput($item['ItemName']);
                            $quantity = $item['Quantity'];
                            $unitPrice = formatPrice($item['UnitPrice']);
                            $itemSubtotal = formatPrice($item['subtotal']);
                            $subtotal += $item['subtotal'];
                            
                            echo <<<HTML
                            <div class="order-item-row">
                                <span>{$itemName} × {$quantity}</span>
                                <span>¥{$itemSubtotal}</span>
                            </div>
                            HTML;
                        }
                        
                        // 计算配送费
                        $deliveryFee = $order['TotalAmount'] - $subtotal;
                        $deliveryFeeFormatted = formatPrice($deliveryFee);
                        
                        echo <<<HTML
                                <div class="order-item-row" style="border-top: 2px solid #eee; margin-top: 5px; padding-top: 5px;">
                                    <span>Subtotal:</span>
                                    <span>¥{$subtotal}</span>
                                </div>
                                <div class="order-item-row">
                                    <span>Delivery Fee:</span>
                                    <span>¥{$deliveryFeeFormatted}</span>
                                </div>
                                <div class="order-item-row" style="font-weight: bold;">
                                    <span>Total:</span>
                                    <span>¥{$totalAmount}</span>
                                </div>
                            </div>
                        </div>
                        HTML;
                    }
                } else {
                    echo <<<HTML
                    <script>
                        document.getElementById('order-empty-state').style.display = 'block';
                    </script>
                    HTML;
                }
                ?>
            </div>
        </div>
    </div>

    <div class="common-tab-bar container">
        <a href="customer_home.php" class="tab-item">
            <i class="fas fa-utensils"></i>
            <span>Home</span>
        </a>
        <a href="customer_orders.php" class="tab-item active">
            <i class="fas fa-receipt"></i>
            <span>Orders</span>
        </a>
        <a href="customer_cart.php" class="tab-item">
            <i class="fas fa-shopping-cart"></i>
            <span>Cart</span>
        </a>
        <a href="customer_profile.php" class="tab-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </div>

    <script>
        // 订单筛选
        function filterOrders(status) {
            const orderCards = document.querySelectorAll('.order-card');
            const filterBtns = document.querySelectorAll('.filter-btn');
            
            // 更新按钮状态
            filterBtns.forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // 筛选订单
            orderCards.forEach(card => {
                if (status === 'all') {
                    card.style.display = 'block';
                } else {
                    const cardStatus = card.getAttribute('data-status').toLowerCase();
                    const statusMap = {
                        'pending': 'pending',
                        'confirmed': 'confirmed',
                        'preparing': 'preparing',
                        'on-the-way': 'on the way',
                        'delivered': 'delivered',
                        'cancelled': 'cancelled'
                    };
                    
                    if (cardStatus === statusMap[status]) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        }
        
        // 搜索订单
        function searchOrders() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const orderCards = document.querySelectorAll('.order-card');
            
            orderCards.forEach(card => {
                const restaurant = card.getAttribute('data-restaurant').toLowerCase();
                const orderId = card.querySelector('div:first-child div:nth-child(2)').textContent.toLowerCase();
                
                if (restaurant.includes(searchTerm) || orderId.includes(searchTerm) || searchTerm === '') {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // 排序订单
        function sortOrders() {
            const sortBy = document.getElementById('sort-select').value;
            const container = document.getElementById('order-history-container');
            const orderCards = Array.from(document.querySelectorAll('.order-card'));
            
            orderCards.sort((a, b) => {
                switch(sortBy) {
                    case 'newest':
                        return new Date(b.getAttribute('data-time')) - new Date(a.getAttribute('data-time'));
                    case 'oldest':
                        return new Date(a.getAttribute('data-time')) - new Date(b.getAttribute('data-time'));
                    case 'price_high':
                        return parseFloat(b.getAttribute('data-amount')) - parseFloat(a.getAttribute('data-amount'));
                    case 'price_low':
                        return parseFloat(a.getAttribute('data-amount')) - parseFloat(b.getAttribute('data-amount'));
                    default:
                        return 0;
                }
            });
            
            // 重新排列DOM
            orderCards.forEach(card => {
                container.appendChild(card);
            });
        }
        
        // 显示/隐藏订单详情
        function toggleOrderDetails(orderId) {
            const details = document.getElementById('order-details-' + orderId);
            details.classList.toggle('show');
        }
        
        // 提交评价
        function submitReview(orderId) {
            const rating = prompt('Please rate your order (1-5 stars):');
            if (rating && rating >= 1 && rating <= 5) {
                const comment = prompt('Leave a comment (optional):');
                
                // 发送AJAX请求提交评价
                const formData = new FormData();
                formData.append('order_id', orderId);
                formData.append('rating', rating);
                formData.append('comment', comment || '');
                
                fetch('review_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Thank you for your review!');
                        location.reload();
                    } else {
                        alert(data.message || 'Error submitting review');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error submitting review');
                });
            }
        }
        
        // 重新下单
        function reorder(orderId) {
            if (confirm('Add all items from this order to cart?')) {
                const formData = new FormData();
                formData.append('order_id', orderId);
                
                fetch('reorder_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Items added to cart!');
                        window.location.href = 'customer_cart.php';
                    } else {
                        alert(data.message || 'Error reordering');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error reordering');
                });
            }
        }
        
        // 初始化筛选按钮
        document.addEventListener('DOMContentLoaded', function() {
            const filterBtns = document.querySelectorAll('.filter-btn');
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>