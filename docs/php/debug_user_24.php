<?php
require_once __DIR__ . '/db.php';

echo "<h1>Debug Last 5 Orders (All Users)</h1>";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "DESCRIBE `order`";
$result = $conn->query($sql);

echo "<h2>Order Table Schema</h2>";
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach($row as $cell) {
            echo "<td>$cell</td>";
        }
        echo "</tr>";
    }
}
echo "</table>";
?>