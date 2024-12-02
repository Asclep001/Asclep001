<?php

session_start();

function pathto($destination){
    echo "<script>window.location.href = '$destination.php'</script>";
}

if ($_SESSION['status'] ==  'invalid' || empty($_SESSION['status'])) {
    $_SESSION['status'] = 'invalid';
    pathto('online-login');
    exit();
}

// Check user role and redirect to appropriate page
if ($_SESSION['status'] == 'valid') {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    
    switch ($_SESSION['roles']) {
        case 'admin':
            if ($current_page != 'adminpage') {
                pathto('user/adminpage');
            }
            break;
            
        case 'student':
            if ($current_page != 'studentpage') {
                pathto('user/studentpage');
            }
            break;
            
        default:
            $_SESSION['status'] = 'invalid';
            pathto('online-login');
            break;
    }
}
?>