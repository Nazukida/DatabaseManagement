<?php
require_once 'database.php';
require_once 'functions.php';

if (!isUserLoggedIn()) {
    header("Location: login.php");
    exit();
}

$userId = getCurrentUserId();
$restaurantId = $_GET['id'] ?? 0;

// 获取餐厅信息
$sql = "SELECT * FROM restaurants WHERE RestaurantID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $restaurantId);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();

if (!$restaurant) {
    header("Location: customer_home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Menu - YouShi LinLi</title>
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
            <div class="page-header">
                <a href="customer_home.php?user_id=<?php echo $userId; ?>" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
                <h3 id="menu-restaurant-title"><?php echo safeOutput($restaurant['RestaurantName']); ?></h3>
            </div>
            
            <div class="menu-list" id="menu-list-container">
                <?php
                // 查询菜单项
                $sql = "SELECT * FROM menu_items WHERE RestaurantID = ? AND StockStatus = 'In Stock'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $restaurantId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    while($item = $result->fetch_assoc()) {
                        $itemName = safeOutput($item['ItemName']);
                        $description = safeOutput($item['ItemDescription'] ?? '');
                        $price = formatPrice($item['Price']);
                        $itemId = $item['MenuItemID'];
                        
                        echo <<<HTML
                        <div class="menu-item-card" style="display:flex; justify-content:space-between; align-items:center; padding:15px; border-bottom:1px solid #eee; background:white; margin-bottom:10px; border-radius:8px;">
                            <div class="item-info" style="flex:1;">
                                <h4 style="margin:0 0 5px 0;">{$itemName}</h4>
                                <p style="margin:0 0 5px 0; color:#666; font-size:0.9em;">{$description}</p>
                                <span class="price" style="color:#ff4d00; font-weight:bold;">¥{$price}</span>
                            </div>
                            <button class="btn-add" onclick="addToCart({$itemId}, {$restaurantId})" style="background:#ff4d00; color:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; font-size:18px;">+</button>
                        </div>
                        HTML;
                    }
                } else {
                    echo "<p style='text-align:center; padding:20px; color:#666;'>No menu items available</p>";
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
        <a href="customer_cart.php?user_id=<?php echo $userId; ?>" class="tab-item">
            <i class="fas fa-shopping-cart"></i>
            <span>Cart</span>
        </a>
        <a href="customer_profile.php?user_id=<?php echo $userId; ?>" class="tab-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </div>

    <script>
        function addToCart(menuItemId, restaurantId) {
            const formData = new FormData();
            formData.append('action', 'add_to_cart');
            formData.append('menu_item_id', menuItemId);
            formData.append('restaurant_id', restaurantId);

            fetch('cart_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Item added to cart!\nQuery Time: ' + data.query_time + ' s');
                } else {
                    alert(data.message || 'Failed to add item');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Item added to cart!');
            });
        }
    </script>
</body>
</html>