<?php
// php/register.php

// 1. 强制清除可能存在的登录状态
// 注册必须使用 'app_public' 身份（在 db.php 中默认），因为只有它有 INSERT 权限。
// 如果用户之前登录了其他角色（如 rider），session 中会有 role，导致 db.php 使用 app_rider 连接，
// 而 app_rider 没有 INSERT 权限，从而导致 "Command denied" 错误。
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['role'])) {
    unset($_SESSION['role']);
}

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $pwd = $_POST['password'];
    $confirm_pwd = $_POST['confirm_password'];

    if ($pwd !== $confirm_pwd) {
        echo "<script>alert('Passwords do not match'); history.back();</script>";
        exit;
    }

    $pwd_hash = hash('sha256', $pwd);
    $stmt = null;
    $new_id = null;
    
    $debug_start = microtime(true);

    // Helper function to get next ID (Since AUTO_INCREMENT is missing)
    function getNextId($conn, $table, $idField) {
        $result = $conn->query("SELECT MAX($idField) as max_id FROM $table");
        $row = $result->fetch_assoc();
        return ($row['max_id'] !== null) ? $row['max_id'] + 1 : 1;
    }

    if ($role == 'customer') {
        $new_id = getNextId($conn, 'users', 'UserID');
        $stmt = $conn->prepare("INSERT INTO users (UserID, Username, Email, PhoneNumber, PasswordHash, RegistrationDate) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issss", $new_id, $_POST['username'], $_POST['email'], $_POST['phone_user'], $pwd_hash);

        if ($stmt->execute()) {
            $duration = number_format(microtime(true) - $debug_start, 4);
            echo "<script>alert('Registration Successful! Your User ID is " . $new_id . ". Please Login.\\nQuery Time: " . $duration . " s'); location.href='../login.html';</script>";
        } else {
            echo "<script>alert('Error: " . $conn->error . "'); history.back();</script>";
        }

    } elseif ($role == 'rider') {
        $new_id = getNextId($conn, 'riders', 'RiderID');
        $stmt = $conn->prepare("INSERT INTO riders (RiderID, Name, PhoneNumber, IDNumber, PasswordHash) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $new_id, $_POST['rider_name'], $_POST['phone_rider'], $_POST['id_number'], $pwd_hash);

        if ($stmt->execute()) {
            $duration = number_format(microtime(true) - $debug_start, 4);
            echo "<script>alert('Registration Successful! IMPORTANT: Your Rider ID is " . $new_id . ". You need this ID to login.\\nQuery Time: " . $duration . " s'); location.href='../login.html';</script>";
        } else {
            echo "<script>alert('Error: " . $conn->error . "'); history.back();</script>";
        }

    } elseif ($role == 'merchant') {
        $new_id = getNextId($conn, 'restaurants', 'RestaurantID');
        $stmt = $conn->prepare("INSERT INTO restaurants (RestaurantID, RestaurantName, Description, DeliveryArea, PasswordHash) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $new_id, $_POST['restaurant_name'], $_POST['description'], $_POST['delivery_area'], $pwd_hash);
        if ($stmt->execute()) {
            $duration = number_format(microtime(true) - $debug_start, 4);
            echo "<script>alert('Registration Successful! IMPORTANT: Your Restaurant ID is " . $new_id . ". You need this ID to login.\\nQuery Time: " . $duration . " s'); location.href='../login.html';</script>";
        } else {
            echo "<script>alert('Error: " . $conn->error . "'); history.back();</script>";
        }
    }

    if ($stmt) {
        $stmt->close();
    }
}

$conn->close();
?>