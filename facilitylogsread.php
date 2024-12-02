<?php

    require('../includes/dbconn.php');
    
    $queryFacilitystudlogs = "SELECT * FROM facilitystudlogs ORDER BY timelogs DESC"; 
    
    $sqlFacilitystudlogs = mysqli_query($conn, $queryFacilitystudlogs);

?>