<?php
require_once 'database.php';
require_once 'functions.php';

// 检查登录
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
    <title>Customer Home - YouShi LinLi</title>
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
            <div class="search-bar">
                <input type="text" class="search-input" placeholder="Search restaurants, food...">
            </div>
            <div class="restaurant-list" id="restaurant-list-container">
                <?php
                // 查询所有餐厅
                $sql = "SELECT * FROM restaurants WHERE TRIM(BusinessStatus) = 'Open'";
                $result = $conn->query($sql);
                
                if ($result === false) {
                    echo "<p style='color:red; text-align:center;'>Error fetching restaurants: " . safeOutput($conn->error) . "</p>";
                } elseif ($result->num_rows > 0) {
                    // Debug info (can be removed later)
                    // echo "<p style='text-align:center; color:#ccc; font-size:10px;'>Found " . $result->num_rows . " open restaurants</p>";
                    
                    while($restaurant = $result->fetch_assoc()) {
                        $restName = safeOutput($restaurant['RestaurantName']);
                        $description = safeOutput($restaurant['Description'] ?? '');
                        $rating = formatPrice($restaurant['AverageRating'] ?? 0);
                        $deliveryFee = formatPrice($restaurant['DeliveryFee'] ?? 0);
                        $minOrder = formatPrice($restaurant['MinimumOrderAmount'] ?? 0);
                        $status = safeOutput($restaurant['BusinessStatus']);
                        $restId = $restaurant['RestaurantID'];
                        
                        echo <<<HTML
                        <div class="restaurant-card" onclick="window.location.href='customer_menu.php?id={$restId}&user_id={$userId}'">
                            <h3>{$restName}</h3>
                            <div class="restaurant-meta">
                                <span><i class="fas fa-star"></i> {$rating} | {$description}</span>
                                <span>Delivery: ¥{$deliveryFee}</span>
                            </div>
                            <div class="restaurant-tags">
                                <span class="tag-discount">{$status}</span>
                                <span class="tag-discount">Min Order: ¥{$minOrder}</span>
                            </div>
                        </div>
                        HTML;
                    }
                } else {
                    echo "<p style='text-align:center; padding:20px; color:#666;'>No restaurants available</p>";
                }
                ?>
            </div>
        </div>
    </div>

    <div class="common-tab-bar container">
        <a href="customer_home.php?user_id=<?php echo $userId; ?>" class="tab-item active">
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
</body>
</html>