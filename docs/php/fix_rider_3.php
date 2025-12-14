<?php
require_once 'db.php';

$riderId = 3;

// Set status to Online
$conn->query("UPDATE riders SET CurrentStatus = 'Online' WHERE RiderID = $riderId");
echo "Rider $riderId status set to Online.<br>";

// Clear active orders (set to Completed) so they can accept new ones
// Or just unassign them? Let's complete them to be nice.
$conn->query("UPDATE `order` SET OrderStatus = 'Completed' WHERE RiderID = $riderId AND OrderStatus IN ('Pending', 'Delivering')");
echo "Cleared active orders for Rider $riderId.<br>";

// Verify
$result = $conn->query("SELECT CurrentStatus FROM riders WHERE RiderID = $riderId");
$row = $result->fetch_assoc();
echo "Current Status: " . $row['CurrentStatus'];
?>