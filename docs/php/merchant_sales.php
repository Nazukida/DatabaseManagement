<?php include('config.php'); ?>
<?php include('functions.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Data - YouShi LinLi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="page-header">
            <a href="index.php"><i class="fas fa-arrow-left"></i></a>
            <span>Sales Data</span>
        </div>
        <div id="sales-data">
            <?php
            $sales = getSalesData();
            echo "<div>Total Orders: " . $sales['total_orders'] . "</div>";
            echo "<div>Total Revenue: ¥" . $sales['total_revenue'] . "</div>";
            ?>
        </div>
    </div>
</body>
</html>
