<?php

    require('../includes/dbconn.php');
    

    $queryStudinfo = "SELECT * FROM stdinfo";
    $sqlStudinfo = mysqli_query($conn, $queryStudinfo);

?>