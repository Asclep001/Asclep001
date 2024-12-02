<?php
require('./includes/dbconn.php');
session_start();

if (isset($_POST['update-student'])) {
    // Retrieve and sanitize form inputs
    $studID = $_POST['studID'];
    $studFName = mysqli_real_escape_string($conn, $_POST['studFname']);
    $studMName = mysqli_real_escape_string($conn, $_POST['studMName']);
    $studLName = mysqli_real_escape_string($conn, $_POST['studLName']);
    $studCourse = mysqli_real_escape_string($conn, $_POST['studCourse']);
    $studGender = mysqli_real_escape_string($conn, $_POST['studGender']);
    $studContact = mysqli_real_escape_string($conn, $_POST['studContact']);
    $studEmail = mysqli_real_escape_string($conn, $_POST['studEmail']);
    $studAddress = mysqli_real_escape_string($conn, $_POST['studAddress']);

    // Fetch the course ID based on the course name
    $checkCourseQuery = "SELECT id FROM courses WHERE course_name = '$studCourse'";
    $checkCourseResult = mysqli_query($conn, $checkCourseQuery);

    if (mysqli_num_rows($checkCourseResult) > 0) {
        $courseRow = mysqli_fetch_assoc($checkCourseResult);
        $studCourseId = $courseRow['id'];

        // Handle image upload if a new image is provided
        if (isset($_FILES['studImg']) && $_FILES['studImg']['error'] == UPLOAD_ERR_OK) {
            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            $fileTmpPath = $_FILES['studImg']['tmp_name'];
            $fileName = $_FILES['studImg']['name'];
            $fileSize = $_FILES['studImg']['size'];
            $fileType = $_FILES['studImg']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            // Check if file extension is allowed
            if (in_array($fileExtension, $allowedExtensions)) {
                // Sanitize file name
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

                // Directory in which the uploaded file will be moved
                $uploadFileDir = './studimg/';
                $dest_path = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $studImg = $dest_path;
                } else {
                    echo "<script>alert('There was an error uploading the image.');</script>";
                    echo "<script>window.location.href = './user/adminpage.php';</script>";
                    exit();
                }
            } else {
                echo "<script>alert('Invalid file type. Only JPG, JPEG, and PNG are allowed.');</script>";
                echo "<script>window.location.href = './user/adminpage.php';</script>";
                exit();
            }
        } else {
            // If no new image is uploaded, retain the existing image
            $queryExisting = "SELECT studImg FROM stdinfo WHERE studID = '$studID'";
            $resultExisting = mysqli_query($conn, $queryExisting);
            if ($resultExisting && mysqli_num_rows($resultExisting) > 0) {
                $row = mysqli_fetch_assoc($resultExisting);
                $studImg = $row['studImg'];
            } else {
                $studImg = '';
            }
        }

        // Prepare the update statement
        $updateQuery = "UPDATE stdinfo SET 
            studFname = ?,
            studMdname = ?,
            studLname = ?,
            course_id = ?,
            course_name = ?,
            studGender = ?,
            studContact = ?,
            studEmail = ?,
            studAddress = ?,
            studImg = ?,
            studUpdated_at = NOW()
            WHERE studID = ?";

        if ($stmt = $conn->prepare($updateQuery)) {
            $stmt->bind_param("sssssssssss", $studFName, $studMName, $studLName, $studCourseId, $studCourse, $studGender, $studContact, $studEmail, $studAddress, $studImg, $studID);

            if ($stmt->execute()) {
                echo "<script>alert('Student information updated successfully.');</script>";
            } else {
                echo "<script>alert('Failed to update student information: " . htmlspecialchars($stmt->error) . "');</script>";
            }

            $stmt->close();
        } else {
            echo "<script>alert('Failed to prepare the update statement: " . htmlspecialchars($conn->error) . "');</script>";
        }
    } else {
        echo "<script>alert('Invalid course selected.');</script>";
    }

    // Redirect back to the admin page
    header("Location: ./user/adminpage.php");
    exit();
}
