<?php
require('../includes/dbconn.php');

if (isset($_POST['studentId'])) {
    $studentId = $_POST['studentId'];
    
    $stmt = $conn->prepare("SELECT studID, studFname, studLname FROM stdinfo WHERE studID = ?");
    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        echo json_encode([
            'exists' => true,
            'name' => $student['studFname'] . ' ' . $student['studLname']
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
}
?>