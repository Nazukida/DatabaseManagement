<?php include('config.php'); ?>
<?php include('functions.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Menu Management - YouShi LinLi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="page-header">
            <a href="index.php"><i class="fas fa-arrow-left"></i></a>
            <span>Menu Management</span>
        </div>
        <div id="menu-list">
            <?php 
            $menu = getMenu();
            foreach ($menu as $item) {
                echo "<div class='order-card'>
                        <div class='menu-item'>
                            <span>" . $item['name'] . "</span>
                            <span>¥" . $item['price'] . "</span>
                        </div>
                        <button onclick='deleteItem(" . $item['id'] . ")'>Delete</button>
                    </div>";
            }
            ?>
        </div>
    </div>

    <script>
        function deleteItem(itemId) {
            if (confirm('Are you sure you want to delete this item?')) {
                window.location.href = 'delete_menu_item.php?id=' + itemId;
            }
        }
    </script>
</body>
</html>
