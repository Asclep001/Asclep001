<?php
require('includes/dbconn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseName = mysqli_real_escape_string($conn, $_POST['course_name'] ?? '');
    $courseCode = mysqli_real_escape_string($conn, $_POST['course_code'] ?? '');
    
    // Generate default values if empty
    if (empty($courseName)) {
        $courseName = 'Untitled Course ' . time();
    }
    if (empty($courseCode)) {
        $courseCode = 'CODE_' . time();
    }
    
    // Insert new course
    $query = "INSERT INTO courses (course_name, course_code) VALUES ('$courseName', '$courseCode')";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>
            alert('Course added successfully!');
            window.location.href = 'user/adminpage.php';
        </script>";
    } else {
        echo "<script>
            alert('Error adding course: " . mysqli_error($conn) . "');
            window.location.href = 'user/adminpage.php';
        </script>";
    }
} else {
    header('Location: user/adminpage.php');
}
?>