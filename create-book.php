<?php 

  require('./includes/dbconn.php');


  $bookLocation = ""; 

  if (isset ($_POST["create-bookinfo"])) {

    $bookDewey = $_POST['book-dewey']; 
    $bookIsbn = $_POST['bookIsbn']; 
    $bookTitle = $_POST['book-title']; 
    $bookAuthor = $_POST['book-author']; 
    $bookPublisher = $_POST['book-publisher']; 
    $bookPages = $_POST['book-pages']; 
    $bookCategory = $_POST['book-category'];
    $bookQuantity = $_POST['book-quantity'];
    $bookTotalQuantity = $bookQuantity;
    $bookDescription = $_POST['book-desc'];

    if (empty($_FILES['book-img']['name'])) {
        $bookLocation = ""; 
    } else {
        $bookImage = pathinfo($_FILES['book-img']['name']);
        
        // Check if the file has a valid extension
        if (isset($bookImage['extension']) && ($bookImage['extension'] == 'jpg' || $bookImage['extension'] == 'png')) {
            $newBookImage = $bookImage['filename'] . "." . $bookImage['extension'];
            $uploadBookPath = './bookImg/' . $newBookImage;
          
        
            // Move the uploaded file
            if (move_uploaded_file($_FILES['book-img']['tmp_name'], $uploadBookPath)) {
                $bookLocation = $uploadBookPath; // Set location to the path of the uploaded file
            } else {
                echo '<script>alert("Failed to move uploaded file. Please try again.")</script>';
                echo '<script>window.location.href = "user/adminpage.php"</script>';
             }
        } else {
            $bookLocation = ""; // Invalid file type
            echo '<script>alert("Invalid file type. Please upload a JPG or PNG image.")</script>';
            echo '<script>window.location.href = "user/adminpage.php"</script>';
        }
    }

    // Get the selected genres
    $bookGenres = isset($_POST['book-genre']) ? $_POST['book-genre'] : [];

    // Convert the array of genres to a string (e.g., comma-separated)
    $bookGenreString = implode(',', $bookGenres);

    $checkQuery = "SELECT * FROM tbbook WHERE bookdewey = '$bookDewey' OR bookIsbn = '$bookIsbn'";
    $checkResult = mysqli_query($conn, $checkQuery);

    // Check if the book already exists
    if (mysqli_num_rows($checkResult) > 0) {
        echo '<script>alert("Book already exists. Please check the input data.")</script>';
        echo '<script>window.location.href = "user/adminpage.php"</script>';
    } else {

        // Ensure $location is a string before using it in the query
        $queryCreate = "INSERT INTO tbbook (book_img, bookdewey, bookIsbn, bookTitle, bookAuthor, bookPublisher, bookQuantity, booktotalQuantity, bookPages, bookCategory, bookGenre, bookDescription, bookcreated_at) VALUES ('$bookLocation' ,'$bookDewey', '$bookIsbn', '$bookTitle', '$bookAuthor',
         '$bookPublisher', '$bookQuantity', '$bookTotalQuantity', '$bookPages', '$bookCategory', '$bookGenreString', '$bookDescription', NOW())"; 
        $sqlCreate = mysqli_query($conn, $queryCreate); 

        if ($sqlCreate) {
            echo '<script>alert("Successfully created!")</script>'; 
            echo '<script>window.location.href = "user/adminpage.php"</script>';
        } else {
            echo '<script>alert("Failed to create book. Please check the input data.")</script>';
            echo '<script>window.location.href = "user/adminpage.php"</script>';
        }
    }
  
  } else {
    echo '<script>window.location.href = "user/adminpage.php"</script>';
  } 
?> 
