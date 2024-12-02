<?php
require('includes/dbconn.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $categoryName = mysqli_real_escape_string($conn, $_POST['category_name']);
    
    // Check if category already exists
    $checkQuery = "SELECT category_name FROM book_categories WHERE category_name = '$categoryName'";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (mysqli_num_rows($checkResult) > 0) {
        echo "<script>
            alert('Category already exists!');
            window.location.href = 'user/adminpage.php';
        </script>";
    } else {
        // Insert new category
        $query = "INSERT INTO book_categories (category_name) VALUES ('$categoryName')";
        
        if (mysqli_query($conn, $query)) {
            echo "<script>
                alert('Category added successfully!');
                window.location.href = 'user/adminpage.php';
            </script>";
        } else {
            echo "<script>
                alert('Error adding category: " . mysqli_error($conn) . "');
                window.location.href = 'user/adminpage.php';
            </script>";
        }
    }
}
?> 