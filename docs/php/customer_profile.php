<?php
require_once 'database.php';
require_once 'functions.php';

if (!isUserLoggedIn()) {
    header("Location: login.php");
    exit();
}

$userId = getCurrentUserId();
$user = getCurrentUser($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - YouShi LinLi</title>
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
            <div class="profile-header" style="text-align:center; padding:30px 0; background:white; margin-bottom:20px;">
                <div class="avatar" style="width:80px; height:80px; background:#eee; border-radius:50%; margin:0 auto 15px; display:flex; align-items:center; justify-content:center; font-size:30px; color:#999;">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-name" style="font-size:20px; font-weight:bold; margin-bottom:5px;">
                    <?php echo safeOutput($user['Username']); ?>
                </div>
                <div class="user-phone" style="color:#666;">
                    <?php echo safeOutput($user['PhoneNumber'] ?? 'No phone number'); ?>
                </div>
                <div class="user-email" style="color:#666; font-size:0.9em;">
                    <?php echo safeOutput($user['Email'] ?? ''); ?>
                </div>
            </div>
            
            <div class="profile-menu" style="background:white; border-radius:8px; overflow:hidden;">
                <div class="menu-item" style="padding:15px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; cursor:pointer;">
                    <span><i class="fas fa-wallet" style="width:25px; color:#ff4d00;"></i> My Wallet</span>
                    <i class="fas fa-chevron-right" style="color:#ccc;"></i>
                </div>
                <a href="customer_orders.php?user_id=<?php echo $userId; ?>" class="menu-item" style="padding:15px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; cursor:pointer; text-decoration:none; color:inherit;">
                    <span><i class="fas fa-receipt" style="width:25px; color:#2196F3;"></i> Order History</span>
                    <i class="fas fa-chevron-right" style="color:#ccc;"></i>
                </a>
                <div class="menu-item" style="padding:15px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; cursor:pointer;">
                    <span><i class="fas fa-map-marker-alt" style="width:25px; color:#4CAF50;"></i> Delivery Addresses</span>
                    <i class="fas fa-chevron-right" style="color:#ccc;"></i>
                </div>
                <div class="menu-item" style="padding:15px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; cursor:pointer;">
                    <span><i class="fas fa-cog" style="width:25px; color:#607D8B;"></i> Settings</span>
                    <i class="fas fa-chevron-right" style="color:#ccc;"></i>
                </div>
            </div>
            
            <div style="margin-top:20px; text-align:center;">
                <a href="logout.php" style="color:#ff4444; text-decoration:none; font-weight:bold;">Sign Out</a>
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
        <a href="customer_profile.php?user_id=<?php echo $userId; ?>" class="tab-item active">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </div>
</body>
</html>
