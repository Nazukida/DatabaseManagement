<?php
require_once 'db.php';

echo "<h1>Order Table Schema</h1>";
$result = $conn->query("DESCRIBE `order`");

if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach($row as $key => $val) {
            echo "<td>" . ($val === null ? 'NULL' : $val) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

echo "<h1>Recent Orders (Last 5)</h1>";
$result = $conn->query("SELECT OrderID, OrderStatus, RiderID FROM `order` ORDER BY OrderTime DESC LIMIT 5");
if ($result) {
    echo "<table border='1'><tr><th>OrderID</th><th>Status</th><th>RiderID</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['OrderID']}</td><td>{$row['OrderStatus']}</td><td>" . var_export($row['RiderID'], true) . "</td></tr>";
    }
    echo "</table>";
}
?>