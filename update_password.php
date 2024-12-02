<?php
session_start();
require('../includes/dbconn.php');

if (isset($_POST['newPassword']) && isset($_SESSION['username'])) {
    $newPassword = $_POST['newPassword'];
    $username = $_SESSION['username'];

    // Hash the new password
    $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Prepare the update statement
    $updateQuery = "UPDATE users SET password = ? WHERE username = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ss", $hashedNewPassword, $username);

    if ($stmt->execute()) {
        http_response_code(200); // Success
    } else {
        http_response_code(500); // Server error
        error_log("Error updating password: " . $stmt->error); // Log the error
    }

    $stmt->close();
} else {
    http_response_code(400); // Bad request
}
?> 
?>