<?php
require('../includes/dbconn.php');

if (isset($_POST['keywords'])) {
    $keywords = mysqli_real_escape_string($conn, $_POST['keywords']);
    $query = "SELECT bookTitle, bookAuthor FROM tbbook WHERE 
              bookTitle LIKE '%$keywords%' OR 
              bookAuthor LIKE '%$keywords%' OR 
              bookIsbn LIKE '%$keywords%' OR 
              bookCategory LIKE '%$keywords%' OR 
              bookPublisher LIKE '%$keywords%' OR
              bookIndexes LIKE '%$keywords%'
              LIMIT 5";
    $result = mysqli_query($conn, $query);

    $suggestions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $suggestions[] = [
            'title' => $row['bookTitle'],
            'author' => $row['bookAuthor']
        ];
    }

    echo json_encode($suggestions);
}
?>
