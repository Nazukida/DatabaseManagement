<?php
require_once 'database.php';
require_once 'functions.php';

if (!isUserLoggedIn()) {
    header("Location: login.php");
    exit();
}

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
            <div class="page-header">
                <a href="customer_home.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
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
                        <div class="menu-item-card">
                            <div class="item-info">
                                <h4>{$itemName}</h4>
                                <p>{$description}</p>
                                <span class="price">¥{$price}</span>
                            </div>
                            <button class="btn-add" onclick="addToCart({$itemId}, '{$itemName}', {$item['Price']})">+</button>
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
        <a href="customer_home.php" class="tab-item">
            <i class="fas fa-utensils"></i>
            <span>Home</span>
        </a>
        <a href="customer_orders.php" class="tab-item">
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
        let currentRestaurantId = <?php echo $restaurantId; ?>;
        
        function addToCart(menuItemId, name, price) {
            // 发送AJAX请求添加到购物车
            const params = new URLSearchParams();
            params.append('action', 'add_to_cart');
            params.append('menu_item_id', menuItemId);
            params.append('restaurant_id', currentRestaurantId);
            
            fetch('cart_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Added ${name} to cart!`);
                } else {
                    alert(data.message || 'Error adding to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding to cart');
            });
        }
    </script>
</body>
</html>