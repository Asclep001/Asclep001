<?php
if (isset($_POST['search-stud'])) {
    $searchTerm = mysqli_real_escape_string($conn, $_POST['search-stud']);
    echo "Search Term: " . htmlspecialchars($searchTerm);

    // Query to search for students by ID, first name, middle name, or last name
    $searchQuery = "SELECT * FROM stdinfo WHERE studID LIKE '%$searchTerm%' 
                    OR studFname LIKE '%$searchTerm%' 
                    OR studMdname LIKE '%$searchTerm%' 
                    OR studLname LIKE '%$searchTerm%'";
    
    $searchResult = mysqli_query($conn, $searchQuery);
    
    if (!$searchResult) {
        echo "Query Error: " . mysqli_error($conn);
    } else {
        if (mysqli_num_rows($searchResult) > 0) {
            echo "<table class='student-info management'>";
            echo "<tr><th>STUDENT ID</th><th>NAME</th><th>EMAIL</th><th>CONTACT</th><th>AGE</th></tr>";
            
            while ($student = mysqli_fetch_assoc($searchResult)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($student['studID']) . "</td>";
                echo "<td>" . htmlspecialchars($student['studFname'] . " " . $student['studMdname'] . " " . $student['studLname']) . "</td>";
                echo "<td>" . htmlspecialchars($student['studEmail']) . "</td>";
                echo "<td>" . htmlspecialchars($student['studContact']) . "</td>";
                echo "<td>" . htmlspecialchars($student['studAge']) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No students found matching your search.</p>";
        }
    }
}
?>