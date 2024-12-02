<?php
require('../includes/dbconn.php'); // Ensure you have the database connection

if (isset($_GET['id'])) {
    $bookId = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM tbbook WHERE bookdewey = ?");
    $stmt->bind_param("s", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
        echo json_encode($book); // Return book details as JSON
    } else {
        echo json_encode(['error' => 'Book not found']);
    }
} else {
    echo json_encode(['error' => 'No book ID provided']);
}
?>
