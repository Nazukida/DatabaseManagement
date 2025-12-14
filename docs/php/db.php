<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dbms";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure UTF-8 encoding for all database interactions
$conn->set_charset("utf8mb4");
?>