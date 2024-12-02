<?php
require('./includes/dbconn.php');
if (isset($_POST['import_books'])) {
    $file = $_FILES['import-book']['tmp_name'];

    if (($handle = fopen($file, 'r')) !== FALSE) {
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== FALSE) {
            // Debugging: Log the data being read
            error_log(print_r($data, true)); // Log the data for debugging

            // Check if the expected number of columns is present
            if (count($data) < 9) {
                continue; // Skip this row if it doesn't have enough columns
            }

            $bookID = $data[0];
            $bookTitle = $data[1];
            $bookAuthor = $data[2];
            $bookPublisher = $data[3];
            $bookISBN = $data[4];
            $bookQuantity = $data[5];
            $bookPages = $data[6];
            $bookCategory = $data[7];
            $bookDescription = $data[8];
            $bookTotalQuantity = $data[5];
            $bookIndexes = $data[9];
            $bookGenre = $data[10];
            $bookCreatedAt = date('Y-m-d H:i:s'); // Get the current timestamp

            // Check if the category already exists
            $checkCategoryQuery = "SELECT * FROM book_categories WHERE category_name = ?";
            $checkCategoryStmt = $conn->prepare($checkCategoryQuery);
            $checkCategoryStmt->bind_param("s", $bookCategory);
            $checkCategoryStmt->execute();
            $result = $checkCategoryStmt->get_result();

            // Insert the new category into the book_categories table if it doesn't exist
            if ($result->num_rows === 0) {
                $categoryQuery = "INSERT INTO book_categories (category_name) VALUES (?)";
                $categoryStmt = $conn->prepare($categoryQuery);
                $categoryStmt->bind_param("s", $bookCategory);
                if (!$categoryStmt->execute()) {
                    error_log("Category insertion failed: " . $categoryStmt->error); // Log error
                }
            }

            // Insert into the database
            $query = "INSERT INTO tbbook (bookdewey, bookIsbn, bookTitle, bookAuthor, bookPublisher, bookQuantity, bookTotalQuantity, bookPages, bookIndexes, bookCategory, bookGenre, bookDescription, bookcreated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE 
                      bookTitle = VALUES(bookTitle), 
                      bookAuthor = VALUES(bookAuthor), 
                      bookPublisher = VALUES(bookPublisher), 
                      bookIsbn = VALUES(bookIsbn), 
                      bookQuantity = VALUES(bookQuantity), 
                      bookTotalQuantity = VALUES(bookTotalQuantity), 
                      bookPages = VALUES(bookPages), 
                      bookIndexes = VALUES(bookIndexes), 
                      bookCategory = VALUES(bookCategory), 
                      bookGenre = VALUES(bookGenre), 
                      bookDescription = VALUES(bookDescription),
                      bookcreated_at = VALUES(bookcreated_at)";

            // Debugging: Check if the insert query was successful
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssssssssss", $bookID, $bookISBN, $bookTitle, $bookAuthor, $bookPublisher, $bookQuantity, $bookTotalQuantity, $bookPages, $bookIndexes, $bookCategory, $bookGenre, $bookDescription, $bookCreatedAt);
            if (!$stmt->execute()) {
                error_log("Insert failed: " . $stmt->error); // Log error
            }
        }
        fclose($handle);
        echo "<script>alert('Books imported successfully!');</script>";
        echo "<script>window.location.href = './user/adminpage.php';</script>";
    } else {
        echo "<script>alert('Error opening the file.');</script>";
    }
}
?>