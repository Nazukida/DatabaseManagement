<?php include('config.php'); ?>
<?php include('functions.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Management - YouShi LinLi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="page-header">
            <a href="index.php"><i class="fas fa-arrow-left"></i></a>
            <span>Order Management</span>
        </div>
        <div id="order-list">
            <?php 
            $orders = getOrders();
            foreach ($orders as $order) {
                $itemsDesc = implode(', ', array_map(function($item) {
                    return $item['ItemName'] . ' x' . $item['Quantity'];
                }, $order['items']));

                echo "<div class='order-card'>
                        <div class='order-header'>
                            <span>Order #" . $order['id'] . "</span>
                            <span class='order-status'>" . $order['status'] . "</span>
                        </div>
                        <div class='order-items'>$itemsDesc</div>
                        <div class='order-actions'>
                            <button class='btn-action'>Accept</button>
                            <button class='btn-action'>Mark as Ready</button>
                        </div>
                    </div>";
            }
            ?>
        </div>
    </div>
</body>
</html>
