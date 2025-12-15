<?php
// 获取所有订单
function getOrders() {
    global $conn;
    $sql = "SELECT * FROM `order`";  // 假设订单存储在 'order' 表中
    $result = $conn->query($sql);
    $orders = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // 获取订单项
            $orderId = $row['id'];
            $orderItems = getOrderItems($orderId);
            $row['items'] = $orderItems;
            $orders[] = $row;
        }
    }
    return $orders;
}

// 获取订单项（从 `order_items` 表获取数据）
function getOrderItems($orderId) {
    global $conn;
    $sql = "SELECT * FROM order_items WHERE order_id = $orderId";  // 获取某个订单的所有菜品项
    $result = $conn->query($sql);
    $items = [];
    while($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}

// 获取所有菜单项（`menu_items` 表）
function getMenu() {
    global $conn;
    $sql = "SELECT * FROM menu_items";  // 获取菜单项
    $result = $conn->query($sql);
    $menu = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $menu[] = $row;
        }
    }
    return $menu;
}

// 获取销售数据（从 `order` 表获取数据）
function getSalesData() {
    global $conn;
    $sql = "SELECT SUM(total_amount) AS total_revenue, COUNT(*) AS total_orders FROM `order`";  // 计算订单总收入与总订单数
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}

// 新增菜单项（`menu_items` 表）
function addMenuItem($name, $description, $price) {
    global $conn;
    $sql = "INSERT INTO menu_items (name, description, price) VALUES ('$name', '$description', $price)";
    return $conn->query($sql);
}

// 删除菜单项（`menu_items` 表）
function deleteMenuItem($itemId) {
    global $conn;
    $sql = "DELETE FROM menu_items WHERE id = $itemId";
    return $conn->query($sql);
}
?>
