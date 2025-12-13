<?php
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
            echo "<script>alert('Registration Successful! Your User ID is " . $new_id . ". Please Login.'); location.href='../login.html';</script>";
        } else {
            echo "<script>alert('Error: " . $conn->error . "'); history.back();</script>";
        }

    } elseif ($role == 'rider') {
        $new_id = getNextId($conn, 'riders', 'RiderID');
        $stmt = $conn->prepare("INSERT INTO riders (RiderID, Name, PhoneNumber, IDNumber, PasswordHash) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $new_id, $_POST['rider_name'], $_POST['phone_rider'], $_POST['id_number'], $pwd_hash);

        if ($stmt->execute()) {
            echo "<script>alert('Registration Successful! IMPORTANT: Your Rider ID is " . $new_id . ". You need this ID to login.'); location.href='../login.html';</script>";
        } else {
            echo "<script>alert('Error: " . $conn->error . "'); history.back();</script>";
        }

    } elseif ($role == 'merchant') {
        $new_id = getNextId($conn, 'restaurants', 'RestaurantID');
        $stmt = $conn->prepare("INSERT INTO restaurants (RestaurantID, RestaurantName, Description, DeliveryArea, PasswordHash) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $new_id, $_POST['restaurant_name'], $_POST['description'], $_POST['delivery_area'], $pwd_hash);

        if ($stmt->execute()) {
            echo "<script>alert('Registration Successful! IMPORTANT: Your Restaurant ID is " . $new_id . ". You need this ID to login.'); location.href='../login.html';</script>";
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