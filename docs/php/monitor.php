<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Monitor</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f0f0f0; }
        .container { display: flex; gap: 20px; }
        .box { flex: 1; background: white; padding: 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); height: 80vh; overflow: auto; }
        h2 { margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
        .refresh-timer { position: fixed; top: 10px; right: 10px; background: #333; color: white; padding: 5px 10px; border-radius: 3px; }
    </style>
    <script>
        setInterval(() => {
            location.reload();
        }, 5000);
    </script>
</head>
<body>
    <div class="refresh-timer">Auto-refresh: 5s</div>
    <h1>System Monitor</h1>
    
    <div class="container">
        <div class="box">
            <h2>Debug Log (php/debug_log.txt)</h2>
            <pre><?php
                $logFile = __DIR__ . '/debug_log.txt';
                if (file_exists($logFile)) {
                    echo htmlspecialchars(file_get_contents($logFile));
                } else {
                    echo "Log file not found yet.";
                }
            ?></pre>
        </div>
        
        <div class="box">
            <h2>Latest Orders (DB)</h2>
            <?php
            require_once 'db.php';
            
            if ($conn->connect_error) {
                echo "DB Connection Failed: " . $conn->connect_error;
            } else {
                $sql = "SELECT * FROM `order` ORDER BY OrderID DESC LIMIT 10";
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    echo "<table border='1' style='width:100%; border-collapse:collapse;'>";
                    echo "<tr><th>ID</th><th>User</th><th>Status</th><th>Rider</th><th>Time</th></tr>";
                    while($row = $result->fetch_assoc()) {
                        $bg = ($row['OrderStatus'] == 'confirmed') ? '#e6fffa' : 'white';
                        echo "<tr style='background:$bg'>";
                        echo "<td>" . $row['OrderID'] . "</td>";
                        echo "<td>" . $row['UserID'] . "</td>";
                        echo "<td>" . $row['OrderStatus'] . "</td>";
                        echo "<td>" . ($row['RiderID'] ?? 'NULL') . "</td>";
                        echo "<td>" . $row['OrderTime'] . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "No orders found.";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>