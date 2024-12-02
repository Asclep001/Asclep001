<?php

    $host = 'localhost';
    $user = 'root';
    $password = '';
    $db = 'library_db';

    $conn = mysqli_connect($host, $user, $password, $db);

    if (mysqli_connect_error()) {
        die("Database connection failed: " . mysqli_connect_error());
    }
?>