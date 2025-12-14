<?php
require_once __DIR__ . '/db.php';

echo "<h1>Debug User 24</h1>";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM `users` WHERE UserID = 24";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<table border='1'>";
    foreach ($row as $key => $value) {
        echo "<tr><th>$key</th><td>$value</td></tr>";
    }
    echo "</table>";
} else {
    echo "No user found with ID 24";
}
?>