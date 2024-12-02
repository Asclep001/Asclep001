<?php
require('./includes/dbconn.php');

if (isset($_POST['return-book'])) {
    $studentId = $_POST['student_id'];
    $bookId = $_POST['book_id'];

    // Fetch the return date and due date from the borrow_records table
    $returnDateQuery = "SELECT expectedreturn_date FROM borrow_records WHERE student_id = ? AND book_id = ?";
    $returnDateStmt = mysqli_prepare($conn, $returnDateQuery);
    
    if ($returnDateStmt === false) {
        echo '<script>alert("Failed to prepare statement: ' . mysqli_error($conn) . '");</script>';
        exit();
    }

    mysqli_stmt_bind_param($returnDateStmt, "ss", $studentId, $bookId);
    
    mysqli_stmt_execute($returnDateStmt);
    
    // Bind the correct number of result variables
    mysqli_stmt_bind_result($returnDateStmt, $returnDate);
    
    if (!mysqli_stmt_fetch($returnDateStmt)) { // Check if fetch was successful
        echo '<script>alert("No records found for this student and book.");</script>';
        exit();
    }
    mysqli_stmt_close($returnDateStmt);

    // Check if the book is overdue
    $currentDate = date('Y-m-d');
    $penalty = 0;
    $penaltyRate = 5; // Define a penalty rate per day
    $status = 'good'; // Default status

    // Ensure due date is valid before calculating overdue days
    if ($returnDate && strtotime($currentDate) > strtotime($returnDate)) {
        $overdueDays = (strtotime($currentDate) - strtotime($returnDate)) / (60 * 60 * 24);
        $penalty = (float)$overdueDays * $penaltyRate; // Ensure penalty is a float
        $status = 'penalty'; // Update status to penalty if overdue
    }

    // Prepare the delete query to remove the record from borrow_records
    $deleteQuery = "DELETE FROM borrow_records WHERE student_id = ? AND book_id = ?";
    $stmt = mysqli_prepare($conn, $deleteQuery);
    
    if ($stmt === false) {
        echo '<script>alert("Failed to prepare statement: ' . mysqli_error($conn) . '");</script>';
        exit();
    }

    mysqli_stmt_bind_param($stmt, "ss", $studentId, $bookId);
    
    // Execute the delete query
    if (mysqli_stmt_execute($stmt)) {
        // Update the book availability in tbbook
        $updateQuery = "UPDATE tbbook SET  bookQuantity = bookQuantity + 1 WHERE bookdewey = ?";
        $updateStmt = mysqli_prepare($conn, $updateQuery);
        
        if ($updateStmt === false) {
            echo '<script>alert("Failed to prepare update statement: ' . mysqli_error($conn) . '");</script>';
            exit();
        }

        mysqli_stmt_bind_param($updateStmt, "s", $bookId);
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);

        // Insert into return_record table
        $insertReturnQuery = "INSERT INTO return_record (student_id, book_id, return_date, expectedreturn_date, penalty, status) VALUES (?, ?, ?, ?, ?, ?)";
        $insertReturnStmt = mysqli_prepare($conn, $insertReturnQuery);
        
        if ($insertReturnStmt === false) {
            echo '<script>alert("Failed to prepare insert statement: ' . mysqli_error($conn) . '");</script>';
            exit();
        }

        mysqli_stmt_bind_param($insertReturnStmt, "ssssis", $studentId, $bookId, $currentDate, $dueDate, $penalty, $status);
        
        mysqli_stmt_execute($insertReturnStmt);
        mysqli_stmt_close($insertReturnStmt);

        echo '<script>alert("Book returned successfully! Penalty: ₱' . number_format($penalty, 2) . '");</script>'; // Format penalty for display
    } else {
        echo '<script>alert("Error returning book: ' . mysqli_stmt_error($stmt) . '");</script>';
    }

    mysqli_stmt_close($stmt);
    // Close the database connection
    mysqli_close($conn); // Ensure the connection is closed
    echo '<script>window.location.href = "user/adminpage.php";</script>';
} else {
    echo '<script>alert("Invalid request.");</script>';
    echo '<script>window.location.href = "user/adminpage.php";</script>';
}
?>