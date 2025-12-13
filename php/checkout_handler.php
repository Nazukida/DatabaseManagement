<?php
require_once 'database.php';
require_once 'functions.php';

if (!isUserLoggedIn()) {
    header("Location: login.php");
    exit();
}

$userId = getCurrentUserId();

// 获取用户的pending订单
$sql = "SELECT OrderID FROM `order` WHERE UserID = ? AND OrderStatus = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while($order = $result->fetch_assoc()) {
    // 更新订单状态为confirmed
    $updateSql = "UPDATE `order` SET OrderStatus = 'confirmed' WHERE OrderID = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("i", $order['OrderID']);
    $updateStmt->execute();
    
    // 创建支付记录
    $orderSql = "SELECT TotalAmount FROM `order` WHERE OrderID = ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bind_param("i", $order['OrderID']);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    $orderData = $orderResult->fetch_assoc();
    
    $paymentTime = date('Y-m-d H:i:s');
    $transactionId = 'TRX' . time() . $order['OrderID'];
    
    $paymentSql = "INSERT INTO payment (Amount, PaymentStatus, PaymentTime, TransationID, OrderID) 
                   VALUES (?, 'completed', ?, ?, ?)";
    $paymentStmt = $conn->prepare($paymentSql);
    $paymentStmt->bind_param("dssi", $orderData['TotalAmount'], $paymentTime, $transactionId, $order['OrderID']);
    $paymentStmt->execute();
}

header("Location: customer_orders.php");
exit();
?>