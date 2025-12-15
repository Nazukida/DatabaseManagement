<?php
require_once 'db.php';
$result = $conn->query("SELECT AddressID FROM delivery_addresses WHERE AddressID = 1");
if ($result && $result->num_rows > 0) {
    echo "Address 1 exists.";
} else {
    echo "Address 1 MISSING. Inserting...";
    $conn->query("INSERT INTO delivery_addresses (AddressID, UserID, FullAddress, ContactName, ContactPhone) VALUES (1, 24, 'Test Address', 'Test User', '1234567890')");
    if ($conn->error) echo "Error: " . $conn->error;
    else echo "Inserted Address 1.";
}
?>