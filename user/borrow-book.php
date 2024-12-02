<?php
require('../includes/dbconn.php');

if (isset($_POST['borrow-book-btn'])) {
    $studentId = $_POST['student-id'];
    $bookIds = isset($_POST['borrow-book-info']) && is_array($_POST['borrow-book-info']) ? $_POST['borrow-book-info'] : [];
    $borrowDate = $_POST['borrow-date'];
    $returnDate = $_POST['return-date'];

    // Debugging: Check input values
    if (empty($studentId) || empty($bookIds) || empty($borrowDate) || empty($returnDate)) {
        echo '<script>alert("Please fill in all fields.");</script>';
        echo '<script>window.location.href = "adminpage.php";</script>';
        exit();
    }

    // Check if the student already has 3 borrow records
    $countQuery = "SELECT COUNT(*) FROM borrow_records WHERE student_id = ?";
    $countStmt = mysqli_prepare($conn, $countQuery);
    mysqli_stmt_bind_param($countStmt, "s", $studentId);
    mysqli_stmt_execute($countStmt);
    mysqli_stmt_bind_result($countStmt, $borrowCount);
    mysqli_stmt_fetch($countStmt);
    mysqli_stmt_close($countStmt);

    if ($borrowCount >= 3) {
        echo '<script>alert("A student can only borrow up to 3 books at a time.");</script>';
        echo '<script>window.location.href = "adminpage.php";</script>';
        exit();
    }

    // Prepare the insert query
    $query = "INSERT INTO borrow_records (student_id, book_id, borrow_date, expectedreturn_date) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);

    // Check if the statement was prepared successfully
    if ($stmt === false) {
        echo '<script>alert("Failed to prepare statement: ' . mysqli_error($conn) . '");</script>';
        exit();
    }

    $booksBorrowed = false; // Flag to track if any books were borrowed

    // Loop through each checked book ID and execute the insert
    foreach ($bookIds as $bookId) {
        // Check if the book is available
        $availabilityQuery = "SELECT bookQuantity FROM tbbook WHERE bookdewey = ?";
        $availabilityStmt = mysqli_prepare($conn, $availabilityQuery);
        mysqli_stmt_bind_param($availabilityStmt, "s", $bookId);
        mysqli_stmt_execute($availabilityStmt);
        mysqli_stmt_bind_result($availabilityStmt, $bookQuantity);
        mysqli_stmt_fetch($availabilityStmt);
        mysqli_stmt_close($availabilityStmt);

        if ($bookQuantity < 1) {
            // Fetch the book title
            $titleQuery = "SELECT bookTitle FROM tbbook WHERE bookdewey = ?";
            $titleStmt = mysqli_prepare($conn, $titleQuery);
            mysqli_stmt_bind_param($titleStmt, "s", $bookId);
            mysqli_stmt_execute($titleStmt);
            mysqli_stmt_bind_result($titleStmt, $bookTitle);
            mysqli_stmt_fetch($titleStmt);
            mysqli_stmt_close($titleStmt);

            echo '<script>alert("Book titled ' . htmlspecialchars($bookTitle) . ' is out of stock.");</script>';
            // Prevent further processing if any book is out of stock
            echo '<script>window.location.href = "adminpage.php";</script>';
            exit(); // Exit to prevent further execution
        } else {
            mysqli_stmt_bind_param($stmt, "ssss", $studentId, $bookId, $borrowDate, $returnDate);
            if (!mysqli_stmt_execute($stmt)) {
                echo '<script>alert("Error borrowing book: ' . mysqli_stmt_error($stmt) . '");</script>';
            } else {
                $booksBorrowed = true; // Set flag to true if a book is borrowed
                // Update the book availability and decrease the quantity
                $updateQuery = "UPDATE tbbook SET bookQuantity = bookQuantity - 1 WHERE bookdewey = ?";
                $updateStmt = mysqli_prepare($conn, $updateQuery);
                mysqli_stmt_bind_param($updateStmt, "s", $bookId);
                mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);
            }
        }
    }

    mysqli_stmt_close($stmt);

    // Only show success message if at least one book was borrowed
    if ($booksBorrowed) {
        echo '<script>alert("Books borrowed successfully!");</script>';
        echo '<script>window.location.href = "adminpage.php";</script>';
    }
}
