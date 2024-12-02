<?php
require('./includes/dbconn.php');
session_start();

// Assuming the username is stored in the session
$username = $_SESSION['username'];

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form inputs with checks
    $adminFName = isset($_POST['adminFname']) ? mysqli_real_escape_string($conn, $_POST['adminFname']) : '';
    $adminMName = isset($_POST['adminMname']) ? mysqli_real_escape_string($conn, $_POST['adminMname']) : '';
    $adminLName = isset($_POST['adminLname']) ? mysqli_real_escape_string($conn, $_POST['adminLname']) : '';
    $adminContact = isset($_POST['adminContact']) ? mysqli_real_escape_string($conn, $_POST['adminContact']) : '';
    $adminEmail = isset($_POST['adminEmail']) ? mysqli_real_escape_string($conn, $_POST['adminEmail']) : '';
    
    // Prepare the update SQL statement
    $updateQuery = "
        UPDATE tbadmininfo 
        SET adminFname = ?, adminMname = ?, adminLname = ?, adminContact = ?, adminEmail = ?
        WHERE username = ?";
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ssssss", $adminFName, $adminMName, $adminLName, $adminContact, $adminEmail, $username);
    
    if ($stmt->execute()) {
        echo "<script>alert('Admin profile updated successfully.'); setTimeout(function() { window.location.href = './user/adminpage.php'; }, 1000);</script>";
        exit();
    } else {
        echo "Error updating profile: " . htmlspecialchars($stmt->error);
        error_log("SQL Error: " . $stmt->error);
    }

    // Close the statement
    $stmt->close();
}

// Prepare the SQL statement to fetch admin info
$query = "
    SELECT 
        tbadmininfo.adminFname, 
        tbadmininfo.adminMname, 
        tbadmininfo.adminLname, 
        tbadmininfo.adminContact, 
        tbadmininfo.adminEmail 
    FROM 
        tbadmininfo 
    WHERE 
        tbadmininfo.username = ?";
    
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $adminInfo = $result->fetch_assoc();
    // Access the admin information
    $adminFName = htmlspecialchars($adminInfo['adminFname']);
    $adminMName = htmlspecialchars($adminInfo['adminMname']);
    $adminLName = htmlspecialchars($adminInfo['adminLname']);
    $adminContact = htmlspecialchars($adminInfo['adminContact']);
    $adminEmail = htmlspecialchars($adminInfo['adminEmail']);
} else {
    echo "No admin information found.";
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>