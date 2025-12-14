<?php
require_once 'db.php';
$sql = "DESCRIBE `order_items`";
$result = $conn->query($sql);
echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr><td>".$row['Field']."</td><td>".$row['Type']."</td></tr>";
}
echo "</table>";
?>