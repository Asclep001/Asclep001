<?php
   require('./includes/dbconn.php');

   if (isset($_POST['update-book'])) {
       $bookID = mysqli_real_escape_string($conn, $_POST['bookID']);
       $bookDewey = mysqli_real_escape_string($conn, $_POST['bookDewey']);
       $bookIsbn = mysqli_real_escape_string($conn, $_POST['bookIsbn']);
       $bookTitle = mysqli_real_escape_string($conn, $_POST['bookTitle']);
       $bookAuthor = mysqli_real_escape_string($conn, $_POST['bookAuthor']);
       $bookPublisher = mysqli_real_escape_string($conn, $_POST['bookPublisher']);
       $bookTotalQuantity = mysqli_real_escape_string($conn, $_POST['bookQuantity']);
       $bookPages = mysqli_real_escape_string($conn, $_POST['bookPages']);
       $bookCategory = isset($_POST['bookCategory']) ? mysqli_real_escape_string($conn, $_POST['bookCategory']) : '';
       $bookGenre = isset($_POST['bookGenre']) ? mysqli_real_escape_string($conn, $_POST['bookGenre']) : '';
       $bookDescription = mysqli_real_escape_string($conn, $_POST['bookDescription']);
       $bookIndex = mysqli_real_escape_string($conn, $_POST['bookIndex']);

       // Handle image upload
       if (isset($_FILES['bookImg']) && $_FILES['bookImg']['error'] == 0) {
           $allowed = ['jpg', 'jpeg', 'png', 'gif'];
           $filename = $_FILES['bookImg']['name'];
           $fileTmp = $_FILES['bookImg']['tmp_name'];
           $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

           if (in_array($fileExt, $allowed)) {
               $newFilename = uniqid('', true) . "." . $fileExt;
               $uploadPath = './bookImg/' . $newFilename;

               if (move_uploaded_file($fileTmp, $uploadPath)) {
                   $bookImg = $uploadPath;
               } else {
                   echo '<script>alert("Failed to upload image.");</script>';
                   echo '<script>window.location.href = "./user/adminpage.php";</script>';
                   exit();
               }
           } else {
               echo '<script>alert("Invalid image type. Only JPG, JPEG, PNG, and GIF are allowed.");</script>';
               echo '<script>window.location.href = "./user/adminpage.php";</script>';
               exit();
           }
       } else {
           // If no new image is uploaded, retain the existing image
           $queryExisting = "SELECT book_img FROM tbbook WHERE bookdewey = '$bookDewey'";
           $resultExisting = mysqli_query($conn, $queryExisting);
           if ($resultExisting && mysqli_num_rows($resultExisting) > 0) {
               $row = mysqli_fetch_assoc($resultExisting);
               $bookImg = $row['book_img'];
           } else {
               $bookImg = '';
           }
       }

       // Update the book information in the database
       $updateQuery = "UPDATE tbbook SET 
           bookdewey = '$bookDewey',
           bookIsbn = '$bookIsbn',
           bookTitle = '$bookTitle',
           bookAuthor = '$bookAuthor',
           bookPublisher = '$bookPublisher',
           booktotalQuantity = '$bookTotalQuantity',
           bookPages = '$bookPages',
           bookCategory = '$bookCategory',
           bookGenre = '$bookGenre',
           bookDescription = '$bookDescription',
           book_img = '$bookImg',
           bookUpdated_at = NOW(),
           bookIndex = '$bookIndex'
           WHERE bookdewey = '$bookID'";

       if (mysqli_query($conn, $updateQuery)) {
           echo '<script>alert("Book information updated successfully.");</script>';
           echo '<script>window.location.href = "./user/adminpage.php";</script>';
       } else {
           echo '<script>alert("Failed to update book information: ' . mysqli_error($conn) . '");</script>';
           echo '<script>window.location.href = "./user/adminpage.php";</script>';
       }
   } else {
       echo '<script>window.location.href = "./user/adminpage.php";</script>';
   }
   ?>