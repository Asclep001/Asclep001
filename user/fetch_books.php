<?php
require('../includes/dbconn.php');

if (isset($_POST['searchTerm'])) {
    $searchTerm = $_POST['searchTerm'];
    $query = "SELECT bookdewey, bookTitle, bookIsbn, bookCategory, bookGenre, bookQuantity 
              FROM tbbook 
              WHERE bookdewey LIKE '%$searchTerm%' 
              OR bookTitle LIKE '%$searchTerm%' 
              OR bookIsbn LIKE '%$searchTerm%' 
              OR bookCategory LIKE '%$searchTerm%'";
    $result = mysqli_query($conn, $query);

    $books = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $books[] = $row;
    }

    echo json_encode($books);
}
?> 