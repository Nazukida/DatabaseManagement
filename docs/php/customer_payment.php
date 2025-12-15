<?php
require_once 'database.php';
require_once 'functions.php';

if (!isUserLoggedIn()) {
    header("Location: login.php");
    exit();
}

$userId = getCurrentUserId();
$orderId = $_GET['order_id'] ?? 0;

// Verify order belongs to user and is pending
$sql = "SELECT * FROM `order` WHERE OrderID = ? AND UserID = ? AND OrderStatus = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo "<script>alert('Invalid order or order already processed.'); window.location.href='customer_cart.php';</script>";
    exit();
}

// Handle Payment Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['payment_method'])) {
    $paymentMethod = $_POST['payment_method'];
    $amount = $order['TotalAmount'];
    $paymentTime = date("Y-m-d H:i:s");
    $transactionId = uniqid("TRX-"); // Simulate a transaction ID
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. Insert Payment Record
        $paySql = "INSERT INTO payment (Amount, PaymentStatus, PaymentTime, TransationID, OrderID) VALUES (?, 'Completed', ?, ?, ?)";
        $payStmt = $conn->prepare($paySql);
        $payStmt->bind_param("dssi", $amount, $paymentTime, $transactionId, $orderId);
        $payStmt->execute();
        
        // 2. Update Order Status
        $updateSql = "UPDATE `order` SET OrderStatus = 'confirmed', PaymentMethod = ? WHERE OrderID = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $paymentMethod, $orderId);
        $updateStmt->execute();
        
        $conn->commit();
        echo "<script>alert('Payment successful! Order confirmed.'); window.location.href='customer_orders.php';</script>";
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Payment failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - YouShi LinLi</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="body container">
    <div class="unified-top-bar">
        <div class="top-bar-content">
            <span class="brand-name">YouShi LinLi</span>
            <div class="top-nav-links">
                <a href="../index.html">Home</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page active">
            <div class="page-header">
                <a href="customer_cart.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Cart</a>
                <h3>Payment</h3>
            </div>
            
            <?php if (isset($error)): ?>
                <div style="color:red; padding:10px; text-align:center;"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="payment-content" style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <div class="payment-amount" style="text-align: center; margin-bottom: 30px;">
                    <p style="color: #666;">Total to Pay</p>
                    <h1 id="pay-amount-display" style="font-size: 2.5em; color: #333;">¥<?php echo formatPrice($order['TotalAmount']); ?></h1>
                </div>
                
                <form method="POST">
                    <div class="payment-methods" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                        <h4 style="margin-top: 0; margin-bottom: 15px;">Select Payment Method</h4>
                        
                        <label class="method-item" style="display: flex; align-items: center; padding: 15px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 10px; cursor: pointer;">
                            <i class="fab fa-alipay" style="color: #1678ff; font-size: 24px; margin-right: 15px; width: 30px; text-align: center;"></i>
                            <span style="flex: 1;">Alipay</span>
                            <input type="radio" name="payment_method" value="Alipay" checked>
                        </label>
                        
                        <label class="method-item" style="display: flex; align-items: center; padding: 15px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 10px; cursor: pointer;">
                            <i class="fab fa-weixin" style="color: #07c160; font-size: 24px; margin-right: 15px; width: 30px; text-align: center;"></i>
                            <span style="flex: 1;">WeChat Pay</span>
                            <input type="radio" name="payment_method" value="WeChat">
                        </label>
                        
                        <label class="method-item" style="display: flex; align-items: center; padding: 15px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 10px; cursor: pointer;">
                            <i class="fas fa-credit-card" style="color: #ff9900; font-size: 24px; margin-right: 15px; width: 30px; text-align: center;"></i>
                            <span style="flex: 1;">Credit Card</span>
                            <input type="radio" name="payment_method" value="Card">
                        </label>
                    </div>
                    
                    <button type="submit" class="btn-pay-now" style="width: 100%; padding: 15px; background: #ff4d00; color: white; border: none; border-radius: 8px; font-size: 1.1em; font-weight: bold; margin-top: 20px; cursor: pointer;">Pay Now</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
