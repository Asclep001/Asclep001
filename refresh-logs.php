<?php
require('./includes/dbconn.php');

// Use prepared statements for better security
$query = "SELECT * FROM facilitystudlogs ORDER BY timelogs DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Error handling for the query
if (!$result) {
    echo "Error fetching logs: " . mysqli_error($conn);
    exit;
}

echo '<link rel="stylesheet" href="./css/loghistory.css">';
while ($row = mysqli_fetch_array($result)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['studID']) . "</td>";
    echo "<td>" . htmlspecialchars($row['studFname'] . " " . $row['studMdname'] . " " . $row['studLname']) . "</td>";
    echo "<td>" . htmlspecialchars($row['studPurpose']) . "</td>";
    echo "<td>" . htmlspecialchars($row['timelogs']) . "</td>";
    echo "</tr>";
}
mysqli_stmt_close($stmt); // Close the statement
?> 