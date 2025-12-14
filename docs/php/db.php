<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$dbname = "dbms";

// Default to public user
$username = "app_public";
$password = "PublicPass123!";

if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'customer':
            $username = "app_customer";
            $password = "CustomerPass123!";
            break;
        case 'merchant':
            $username = "app_merchant";
            $password = "MerchantPass123!";
            break;
        case 'rider':
            $username = "app_rider";
            $password = "RiderPass123!";
            break;
        case 'admin':
            $username = "app_admin";
            $password = "AdminPass123!";
            break;
    }
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure UTF-8 encoding for all database interactions
$conn->set_charset("utf8mb4");
?>