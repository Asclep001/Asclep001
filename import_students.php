<?php
require('./includes/dbconn.php');

if (isset($_POST['import_students'])) {
    $file = $_FILES['import-stud']['tmp_name'];

    if (($handle = fopen($file, 'r')) !== FALSE) {
        // Skip the header row
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== FALSE) {
            $studID = $data[0];
            $course_name = $data[1];
            $studFname = $data[2];
            $studMdname = $data[3];
            $studLname = $data[4];
            $studGender = $data[5];
            $studAddress = $data[6];
            $studEmail = $data[7];
            $studContact = $data[8];
            $studCreatedAt = date('Y-m-d H:i:s');
            $userCreatedAt = date('Y-m-d H:i:s');

            // Insert into the database
            $query = "INSERT INTO stdinfo (studID, course_name, studFname, studMdname, studLname, studGender, studAddress, studEmail, studContact, studcreated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssssssss", $studID, $course_name, $studFname, $studMdname, $studLname, $studGender, $studAddress, $studEmail, $studContact, $studCreatedAt);
            
            try {
                $stmt->execute();

                // Insert into courses table if the course does not already exist
                $courseQuery = "INSERT INTO courses (course_name) VALUES (?) ON DUPLICATE KEY UPDATE course_name = course_name";
                $courseStmt = $conn->prepare($courseQuery);
                $courseStmt->bind_param("s", $course_name);
                $courseStmt->execute();

                // Insert into users table
                $userQuery = "INSERT INTO users (username, password, roles, usercreated_at) VALUES (?, ?, 'student', ?)";
                $userStmt = $conn->prepare($userQuery);
                $userStmt->bind_param("sss", $studID, $studID, $userCreatedAt);
                $userStmt->execute();

            } catch (mysqli_sql_exception $e) {
                // If a duplicate entry error occurs, update the existing record
                if ($e->getCode() == 1062) { // Duplicate entry error code
                    $updateQuery = "UPDATE stdinfo SET 
                                    course_name = ?, 
                                    studFname = ?, 
                                    studMdname = ?, 
                                    studLname = ?, 
                                    studGender = ?, 
                                    studAddress = ?, 
                                    studEmail = ?, 
                                    studContact = ?, 
                                    studcreated_at = ? 
                                    WHERE studID = ?";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param("ssssssssss", $course_name, $studFname, $studMdname, $studLname, $studGender, $studAddress, $studEmail, $studContact, $studCreatedAt, $studID);
                    $updateStmt->execute();

                    // Update the users table as well
                    $updateUserQuery = "UPDATE users SET password = ?, usercreated_at = ? WHERE username = ?";
                    $updateUserStmt = $conn->prepare($updateUserQuery);
                    $updateUserStmt->bind_param("sss", $studID, $userCreatedAt, $studID);
                    $updateUserStmt->execute();
                } else {
                    throw $e; // Rethrow the exception if it's not a duplicate entry error
                }
            }
        }
        fclose($handle);
        echo "<script>alert('Students imported successfully!');</script>";
        echo "<script>window.location.href = './user/adminpage.php';</script>";
    } else {
        echo "<script>alert('Error opening the file.');</script>";
    }
}
?> 