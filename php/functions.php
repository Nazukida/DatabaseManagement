<?php
// 检查用户是否登录
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 获取当前用户ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// 获取当前用户信息
function getCurrentUser($conn) {
    if (!isUserLoggedIn()) return null;
    
    $userId = getCurrentUserId();
    $sql = "SELECT * FROM users WHERE UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// 安全输出HTML
function safeOutput($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// 格式化价格
function formatPrice($price) {
    return number_format($price, 2);
}
?>