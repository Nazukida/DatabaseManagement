<?php
require_once 'db.php';

echo "<h1>Step-by-Step Diagnosis</h1>";

// 1. Check the most recent order created
echo "<h2>1. Checking Most Recent Order</h2>";
$sql = "SELECT * FROM `order` ORDER BY OrderTime DESC LIMIT 1";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    echo "Latest Order ID: " . $row['OrderID'] . "<br>";
    echo "Status: " . $row['OrderStatus'] . "<br>";
    echo "RiderID: " . var_export($row['RiderID'], true) . "<br>";
    echo "RestaurantID: " . $row['RestaurantID'] . "<br>";
    
    $latestOrderId = $row['OrderID'];
    
    // 2. Check if this order meets Rider API criteria
    echo "<h2>2. Validating against Rider API Criteria</h2>";
    $criteriaMet = true;
    
    // Criterion A: RiderID is NULL or 0
    $riderIdOk = ($row['RiderID'] === null || $row['RiderID'] == 0);
    echo "Criterion A (RiderID is NULL or 0): " . ($riderIdOk ? "PASS" : "FAIL") . "<br>";
    if (!$riderIdOk) $criteriaMet = false;
    
    // Criterion B: Status is 'confirmed'
    $statusOk = ($row['OrderStatus'] == 'confirmed');
    echo "Criterion B (Status is 'confirmed'): " . ($statusOk ? "PASS" : "FAIL") . "<br>";
    if (!$statusOk) $criteriaMet = false;
    
    // Criterion C: Restaurant exists
    echo "Criterion C (Restaurant Exists): ";
    $restSql = "SELECT * FROM restaurants WHERE RestaurantID = " . $row['RestaurantID'];
    $restResult = $conn->query($restSql);
    if ($restResult && $restResult->num_rows > 0) {
        echo "PASS<br>";
    } else {
        echo "FAIL (Restaurant ID " . $row['RestaurantID'] . " not found)<br>";
        // Note: LEFT JOIN in API handles this, but data might be missing
    }

    // 3. Simulate Rider API Query
    echo "<h2>3. Simulating Rider API Query</h2>";
    $apiSql = "SELECT o.OrderID, o.TotalAmount, o.DeliveryFee, r.RestaurantName, r.DeliveryArea as PickupAddress 
            FROM `order` o
            LEFT JOIN restaurants r ON o.RestaurantID = r.RestaurantID
            WHERE (o.RiderID IS NULL OR o.RiderID = 0) 
            AND o.OrderStatus = 'confirmed'
            AND o.OrderID = $latestOrderId";
            
    $apiResult = $conn->query($apiSql);
    if ($apiResult && $apiResult->num_rows > 0) {
        echo "SUCCESS: The API query finds this order.<br>";
        $apiRow = $apiResult->fetch_assoc();
        echo "API Output: " . json_encode($apiRow) . "<br>";
    } else {
        echo "FAILURE: The API query DOES NOT find this order.<br>";
    }

} else {
    echo "No orders found in database.<br>";
}

echo "<h2>4. Full API Response Test</h2>";
// Simulate the actual API call
$_GET['action'] = 'get_available_orders';
// Capture output
ob_start();
include 'rider_api.php';
$output = ob_get_clean();
$json = json_decode($output, true);

if ($json && isset($json['orders'])) {
    echo "API returned " . count($json['orders']) . " orders.<br>";
    $found = false;
    foreach ($json['orders'] as $o) {
        if ($o['OrderID'] == $latestOrderId) {
            $found = true;
            break;
        }
    }
    echo "Latest Order ($latestOrderId) in list: " . ($found ? "YES" : "NO") . "<br>";
} else {
    echo "API returned invalid JSON or error.<br>";
    echo "Raw Output: " . htmlspecialchars($output) . "<br>";
}
?>