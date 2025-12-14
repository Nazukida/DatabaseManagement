<?php
require_once 'db.php';

echo "<h1>Rider Status Check</h1>";
$result = $conn->query("SELECT * FROM riders");
if ($result) {
    echo "<table border='1'><tr><th>RiderID</th><th>Username</th><th>CurrentStatus</th></tr>";
    while($row = $result->fetch_assoc()) {
        $username = isset($row['Username']) ? $row['Username'] : 'N/A';
        echo "<tr><td>{$row['RiderID']}</td><td>{$username}</td><td>{$row['CurrentStatus']}</td></tr>";
    }
    echo "</table>";
}
?>