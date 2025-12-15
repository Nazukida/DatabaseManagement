<?php
// php/get_admin_avatar.php
require_once 'db.php';

$adminId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($adminId <= 0) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$stmt = $conn->prepare("SELECT profile_picture FROM admin WHERE AdminID = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($imageData);
    $stmt->fetch();

    if ($imageData) {
        // Simple MIME type detection
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);
        
        header("Content-Type: " . $mimeType);
        echo $imageData;
    } else {
        // Return a default placeholder or 404
        header("HTTP/1.0 404 Not Found");
    }
} else {
    header("HTTP/1.0 404 Not Found");
}

$stmt->close();
$conn->close();
?>
