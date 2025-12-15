<?php
// php/setup_security_users.php
// This script creates database users with specific privileges for different roles.
// Run this script once to set up the security layer.

$servername = "localhost";
$username = "root"; // Must use root to create users
$password = "";
$dbname = "dbms";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully as root.\n";

// Define users and their passwords
// In a real production environment, use strong, unique passwords and store them securely (e.g., environment variables)
$users = [
    'app_public' => 'PublicPass123!',
    'app_customer' => 'CustomerPass123!',
    'app_merchant' => 'MerchantPass123!',
    'app_rider' => 'RiderPass123!',
    'app_admin' => 'AdminPass123!'
];

// Helper function to execute query and log result
function executeQuery($conn, $sql, $description) {
    if ($conn->query($sql) === TRUE) {
        echo "[SUCCESS] $description\n";
    } else {
        echo "[ERROR] $description: " . $conn->error . "\n";
    }
}

// 1. Create Users
foreach ($users as $user => $pass) {
    // Drop user if exists to ensure clean slate
    // executeQuery($conn, "DROP USER IF EXISTS '$user'@'localhost'", "Dropped user $user");
    
    // Create user
    executeQuery($conn, "CREATE USER IF NOT EXISTS '$user'@'localhost' IDENTIFIED BY '$pass'", "Created user $user");
    executeQuery($conn, "ALTER USER '$user'@'localhost' IDENTIFIED BY '$pass'", "Updated password for $user");
}

echo "\n--- Granting Privileges ---\n";

// 2. Grant Privileges

// --- app_public (Login, Register, View Home) ---
// Can read basic info to validate login
executeQuery($conn, "GRANT SELECT ON $dbname.users TO 'app_public'@'localhost'", "Public: Read users");
executeQuery($conn, "GRANT SELECT ON $dbname.riders TO 'app_public'@'localhost'", "Public: Read riders");
executeQuery($conn, "GRANT SELECT ON $dbname.restaurants TO 'app_public'@'localhost'", "Public: Read restaurants");
executeQuery($conn, "GRANT SELECT ON $dbname.admin TO 'app_public'@'localhost'", "Public: Read admin");
// Can register new accounts
executeQuery($conn, "GRANT INSERT ON $dbname.users TO 'app_public'@'localhost'", "Public: Register users");
executeQuery($conn, "GRANT INSERT ON $dbname.riders TO 'app_public'@'localhost'", "Public: Register riders");
executeQuery($conn, "GRANT INSERT ON $dbname.restaurants TO 'app_public'@'localhost'", "Public: Register restaurants");
// Can view menu (publicly available)
executeQuery($conn, "GRANT SELECT ON $dbname.menu_items TO 'app_public'@'localhost'", "Public: Read menu");
// Can view categories (for menu filtering)
executeQuery($conn, "GRANT SELECT ON $dbname.category TO 'app_public'@'localhost'", "Public: Read categories");
// Can view reviews (for restaurant ratings)
executeQuery($conn, "GRANT SELECT ON $dbname.review TO 'app_public'@'localhost'", "Public: Read reviews");


// --- app_customer (Shopping, Ordering, Profile) ---
// Inherits public read access (conceptually, but we grant explicitly)
executeQuery($conn, "GRANT SELECT ON $dbname.restaurants TO 'app_customer'@'localhost'", "Customer: Read restaurants");
executeQuery($conn, "GRANT SELECT ON $dbname.menu_items TO 'app_customer'@'localhost'", "Customer: Read menu");
executeQuery($conn, "GRANT SELECT ON $dbname.category TO 'app_customer'@'localhost'", "Customer: Read categories");
executeQuery($conn, "GRANT SELECT ON $dbname.review TO 'app_customer'@'localhost'", "Customer: Read reviews");
// Manage own orders
executeQuery($conn, "GRANT SELECT, INSERT ON $dbname.order TO 'app_customer'@'localhost'", "Customer: Create and View orders");
executeQuery($conn, "GRANT SELECT, INSERT ON $dbname.order_items TO 'app_customer'@'localhost'", "Customer: Create and View order items");
// Payment (if used)
executeQuery($conn, "GRANT SELECT, INSERT ON $dbname.payment TO 'app_customer'@'localhost'", "Customer: Make payments");
// Manage own profile
executeQuery($conn, "GRANT SELECT, UPDATE ON $dbname.users TO 'app_customer'@'localhost'", "Customer: Manage profile");
// Read addresses (if table exists)
executeQuery($conn, "GRANT SELECT, INSERT, UPDATE ON $dbname.delivery_addresses TO 'app_customer'@'localhost'", "Customer: Manage addresses");


// --- app_merchant (Menu Management, Order Processing) ---
// Manage own restaurant info
executeQuery($conn, "GRANT SELECT, UPDATE ON $dbname.restaurants TO 'app_merchant'@'localhost'", "Merchant: Manage restaurant info");
// Manage menu
executeQuery($conn, "GRANT SELECT, INSERT, UPDATE, DELETE ON $dbname.menu_items TO 'app_merchant'@'localhost'", "Merchant: Manage menu");
executeQuery($conn, "GRANT SELECT ON $dbname.category TO 'app_merchant'@'localhost'", "Merchant: Read categories");
executeQuery($conn, "GRANT SELECT ON $dbname.review TO 'app_merchant'@'localhost'", "Merchant: Read reviews");
// Process orders (Read and Update status)
executeQuery($conn, "GRANT SELECT, UPDATE ON $dbname.order TO 'app_merchant'@'localhost'", "Merchant: Process orders");
executeQuery($conn, "GRANT SELECT ON $dbname.order_items TO 'app_merchant'@'localhost'", "Merchant: Read order items");


// --- app_rider (Delivery) ---
// Read available orders
executeQuery($conn, "GRANT SELECT, UPDATE ON $dbname.order TO 'app_rider'@'localhost'", "Rider: Manage orders (Accept/Complete)");
// Read restaurant info (pickup)
executeQuery($conn, "GRANT SELECT ON $dbname.restaurants TO 'app_rider'@'localhost'", "Rider: Read pickup info");
// Read user info (delivery contact - limited ideally, but SELECT needed)
executeQuery($conn, "GRANT SELECT ON $dbname.users TO 'app_rider'@'localhost'", "Rider: Read customer info");
// Read delivery address info
executeQuery($conn, "GRANT SELECT ON $dbname.delivery_addresses TO 'app_rider'@'localhost'", "Rider: Read delivery address");
// Manage own status
executeQuery($conn, "GRANT SELECT, UPDATE ON $dbname.riders TO 'app_rider'@'localhost'", "Rider: Update status");


// --- app_admin (Full Management) ---
// Full CRUD on all tables
executeQuery($conn, "GRANT SELECT, INSERT, UPDATE, DELETE ON $dbname.* TO 'app_admin'@'localhost'", "Admin: Full CRUD access");
// Note: We do NOT grant DROP, ALTER, etc. to prevent structural damage via web interface.


// 3. Flush Privileges
executeQuery($conn, "FLUSH PRIVILEGES", "Flushed privileges");

$conn->close();

echo "\nSecurity setup completed. Users created and privileges granted.\n";
?>
