
<?php
// Fetch borrow records from the database
$queryBorrowRecords = "SELECT br.student_id, b.bookTitle, br.borrow_date, br.return_date 
                                           FROM borrow_records br 
                                           JOIN tbbook b ON br.book_id = b.bookdewey";
$resultBorrowRecords = mysqli_query($conn, $queryBorrowRecords);

// Initialize an array to group records by student_id
$borrowRecords = [];

// Loop through each record and group by student_id
while ($row = mysqli_fetch_assoc($resultBorrowRecords)) {
    $studentId = $row['student_id'];
    if (!isset($borrowRecords[$studentId])) {
        $borrowRecords[$studentId] = [
            'bookTitles' => [],
            'borrow_date' => $row['borrow_date'],
            'return_date' => $row['return_date']
        ];
    }
    $borrowRecords[$studentId]['bookTitles'][] = htmlspecialchars($row['bookTitle']);
}

// Check if there are any records
if (count($borrowRecords) > 0) {
    // Loop through the grouped records and display them in the table
    foreach ($borrowRecords as $studentId => $data) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($studentId) . "</td>";
        echo "<td>" . implode('.<br> <hr>', $data['bookTitles']) . "</td>";
        echo "<td>" . htmlspecialchars($data['borrow_date']) . "</td>";
        echo "<td>" . htmlspecialchars($data['return_date']) . "</td>";
        echo "<td>";
        echo "<div class='borrow-actions'>";
        echo "<form method='post' class='action-buttons'>";
        echo "<button type='submit' name='edit-borrow' class='action-btn edit-btn'>";
        echo "<i class='bx bx-edit-alt'></i> Update";
        echo "</button>";
        echo "<button type='button' name='view-borrow' class='action-btn return-btn' onclick='showReturnModal(\"$studentId\")'>";
        echo "<i class='bx bx-undo'></i> Return";
        echo "</button>";
        echo "<input type='hidden' name='editId' value='" . htmlspecialchars($studentId) . "'>";
        echo "<input type='hidden' name='editUsername' value=''>";
        echo "<input type='hidden' name='editPassword' value=''>";
        echo "</form>";
        echo "</div>";
        echo "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>No borrow records found.</td></tr>";
}
?>