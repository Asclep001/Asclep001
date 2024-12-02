<?php

require('./includes/dbconn.php');

if (isset($_POST["create-stdinfo"])) {

  $studID = $_POST['stud-id'];
  $studFname = $_POST['stud-fname'];
  $studMdname = $_POST['stud-mdname'];
  $studLname = $_POST['stud-lname'];
  $studEmail = $_POST['stud-email'];
  $studGender = $_POST['stud-gender'];
  $studAddress = $_POST['stud-address'];
  $studCourseName = $_POST['stud-course'];
  $studContacts = $_POST['stud-contact'];

  if (empty($_FILES['studimage']['name'])) {
    $studLocation = "";
  } else {
    $studImage = pathinfo($_FILES['studimage']['name']);

    // Check if the file has a valid extension
    if (isset($studImage['extension']) && ($studImage['extension'] == 'jpg' || $studImage['extension'] == 'png')) {
      $newStudImage = $studImage['filename'] . "." . $studImage['extension'];
      $uploadStudPath = './studimg/' . $newStudImage;

      // Move the uploaded file
      if (move_uploaded_file($_FILES['studimage']['tmp_name'], $uploadStudPath)) {
        $studLocation = $uploadStudPath; // Set location to the path of the uploaded file
      } else {
        echo '<script>alert("Failed to move uploaded file. Please try again.")</script>';
        echo '<script>window.location.href = "user/adminpage.php"</script>';
      }
    } else {
      $studLocation = ""; // Invalid file type
      echo '<script>alert("Invalid file type. Please upload a JPG or PNG image.")</script>';
      echo '<script>window.location.href = "user/adminpage.php"</script>';
    }
  }


  // Check if the student already exists (check ID only)
  $checkStudentQuery = "SELECT * FROM stdinfo WHERE BINARY studID = '$studID'";
  $checkStudentResult = mysqli_query($conn, $checkStudentQuery);

  if (mysqli_num_rows($checkStudentResult) > 0) {
    echo '<script>
      alert("Student ID ' . $studID . ' is already taken. Please use a different ID.");
      window.history.back();
    </script>';
    exit();
  } else {
    // Fetch the course ID based on the course name
    $checkCourseQuery = "SELECT id FROM courses WHERE course_name = '$studCourseName'";
    $checkCourseResult = mysqli_query($conn, $checkCourseQuery);

    if (mysqli_num_rows($checkCourseResult) > 0) {
      $courseRow = mysqli_fetch_assoc($checkCourseResult);
      $studCourseId = $courseRow['id'];

      // Start transaction to ensure both operations succeed or fail together
      mysqli_begin_transaction($conn);

      try {
        // Add current timestamp for studcreated_at
        $currentTimestamp = date('Y-m-d H:i:s');
        $queryCreate = "INSERT INTO stdinfo (studImg, studID, studFname, studMdname, studLname, studEmail, studGender, studAddress, course_id, course_name, studContact, studcreated_at) VALUES ('$studLocation', '$studID', '$studFname', '$studMdname', '$studLname', '$studEmail', '$studGender', '$studAddress', '$studCourseId', '$studCourseName', '$studContacts', '$currentTimestamp')";
        $sqlCreate = mysqli_query($conn, $queryCreate);

        // ... existing code ...
        // ... existing code ...
        $queryUser = "INSERT INTO users (username, password, roles, usercreated_at) VALUES ('$studID', '" . md5($studID) . "', 'student', '$currentTimestamp')"; // Convert only the password to MD5
        // ... existing code ...
        $sqlUser = mysqli_query($conn, $queryUser);

        if ($sqlCreate && $sqlUser) {
          mysqli_commit($conn);
          echo '<script>alert("Successfully created!")</script>';
          echo '<script>window.location.href = "user/adminpage.php"</script>';
        } else {
          throw new Exception("Failed to create student or user record");
        }
      } catch (Exception $e) {
        mysqli_rollback($conn);
        echo '<script>alert("Failed to create student. Please try again.")</script>';
        echo '<script>window.location.href = "user/adminpage.php"</script>';
      }
    } else {
      echo '<script>alert("Course does not exist.")</script>';
      echo '<script>window.location.href = "user/adminpage.php"</script>';
    }
  }
} else {
  echo '<script>window.location.href = "user/adminpage.php"</script>';
}
