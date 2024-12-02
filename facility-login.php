<?php

include('./includes/dbconn.php');

session_start();

if (isset($_POST['purpose-submit'])) {

    $studId = $_POST['student-id'];
    $studPurpose = $_POST['stud-purpose'];

    $stmt = $conn->prepare("SELECT * FROM stdinfo WHERE studID = ?");
    $stmt->bind_param("s", $studId);
    $stmt->execute();
    $sqlCheck = $stmt->get_result();

    if ($sqlCheck->num_rows > 0) {
        $rowsqlCheck = $sqlCheck->fetch_assoc();
        $_SESSION['studID'] = $rowsqlCheck['studID'];

        $studFname = $rowsqlCheck['studFname'];
        $studMdname = $rowsqlCheck['studMdname'];
        $studLname = $rowsqlCheck['studLname'];
        $studGender = $rowsqlCheck['studGender'];
        $studAddress = $rowsqlCheck['studAddress'];
        $studEmail = $rowsqlCheck['studEmail'];
        $studCourse = $rowsqlCheck['course_id'];
        $studCourseName = $rowsqlCheck['course_name'];
        $insertQuery = "INSERT INTO facilitystudlogs (studID, studPurpose, studFname, studMdname, studLname, studGender, studAddress, studEmail, course_id, course_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($insertQuery);
        $stmtInsert->bind_param("ssssssssss", $studId, $studPurpose, $studFname, $studMdname, $studLname, $studGender, $studAddress, $studEmail, $studCourse, $studCourseName);
        $stmtInsert->execute();

        $_SESSION['success'] = true;

        header("Location: facility-login.php");
        exit(); 

    } else {
        echo "<script>alert('ID CANNOT BE FOUND!')</script>";
    }
}


if (isset($_SESSION['success'])) {
    echo "<script>alert('Submission successful!');</script>";
    unset($_SESSION['success']); 
}

// Fetching the ENUM values for the dropdown
$query = "SHOW COLUMNS FROM facilitystudlogs LIKE 'studPurpose'";
$result = mysqli_query($conn, $query);

if ($result) {
    $row = mysqli_fetch_array($result);
    $enumList = $row['Type']; // Fetch the ENUM values
    // Remove "enum('')" and split into an array
    $enumList = str_replace("enum('", "", $enumList);
    $enumList = str_replace("')", "", $enumList);
    $enumValues = explode("','", $enumList);
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PURPOSE IN LIBRARY</title>

    <link rel="stylesheet" href="/icon-font-style/all.min.css">
    <link rel="stylesheet" href="/icon-font-style/boxicons.min.css">
    <link rel="stylesheet" href="/icon-font-style/fontawesome.min.css">
    <link rel="stylesheet" href="css/facility-login.css">


</head>

<body>
    <section class="main-content">
        <div class="logs-container">
            <form action="facility-login.php" method="POST">
                <input type="text" name="student-id" placeholder="Enter Your ID" id="student-id" required autofocus>

                <select name="stud-purpose" required>
                    <option value="" disabled selected>Select Your Purpose</option>
                    <?php
                    // Loop through ENUM values and populate the dropdown
                    if (!empty($enumValues)) {
                        foreach ($enumValues as $value) {
                            echo "<option value='$value'>$value</option>";
                        }
                    } else {
                        echo "<option value='' disabled>No purposes available</option>";
                    }
                    ?>
                </select>
                <input type="submit" name="purpose-submit" value="SUBMIT" id="stud-btn">
            </form>
        </div>
    </section>
</body>
</html>