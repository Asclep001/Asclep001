<?php

    require('../includes/dbconn.php');
    

    $queryBookinfo = "SELECT * FROM tbbook  ORDER BY bookdewey ASC";
    $sqlBookinfo = mysqli_query($conn, $queryBookinfo);

?>