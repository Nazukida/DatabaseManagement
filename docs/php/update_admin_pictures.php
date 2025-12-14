<?php
// php/update_admin_pictures.php
require_once 'db.php';

// Set directory for pictures
$pictureDir = __DIR__ . '/../picture/';

// Get all image files
$images = glob($pictureDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);

if (empty($images)) {
    die("No images found in " . $pictureDir);
}

echo "Found " . count($images) . " images.<br>";

// Get all admins
$sql = "SELECT AdminID FROM admin";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $imageCount = count($images);
    $i = 0;

    while($row = $result->fetch_assoc()) {
        $adminId = $row['AdminID'];
        
        // Select an image (cycle through if not enough images)
        $imagePath = $images[$i % $imageCount];
        $i++;

        // Read image content
        $imageData = file_get_contents($imagePath);
        
        // Prepare SQL to update profile_picture
        // Use prepared statement for BLOB
        $stmt = $conn->prepare("UPDATE admin SET profile_picture = ? WHERE AdminID = ?");
        $null = NULL;
        $stmt->bind_param("bi", $null, $adminId);
        $stmt->send_long_data(0, $imageData);
        
        if ($stmt->execute()) {
            echo "Updated AdminID: $adminId with image: " . basename($imagePath) . "<br>";
        } else {
            echo "Failed to update AdminID: $adminId. Error: " . $stmt->error . "<br>";
        }
        $stmt->close();
    }
    echo "All admins updated.";
} else {
    echo "No admins found.";
}

$conn->close();
?>
