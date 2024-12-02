<?php 
session_start();

function pathto($destination){
    echo "<script>
            localStorage.removeItem('activeSection');
            window.location.href = '$destination.php';
          </script>";
}

$_SESSION['status'] = 'invalid';
unset($_SESSION['username']);
pathto('online-login');

?>;