<?php

require('./includes/dbconn.php');

session_start();

function pathto($destination)
{
    echo "<script>window.location.href = 'user/$destination.php'</script>";
}

// Check if the session status is set before using it
if (!isset($_SESSION['status']) || $_SESSION['status'] == 'invalid' || empty($_SESSION['status'])) {
    $_SESSION['status'] = 'invalid';
}

if ($_SESSION['status'] == 'valid') {
    // Check the user's type and redirect accordingly
    switch ($_SESSION['roles']) {
        case 'admin':
            pathto('adminpage');
            break;
        case 'student':
            pathto('searchpage');
            break;
        default:
            echo "<script>alert('Invalid user type');</script>";
            pathto('online-login');
            break;
    }
}

if (isset($_POST['loginbutton'])) {
    $username = trim($_POST['login-username']);
    $password = trim($_POST['login-password']);

    if (empty($username) || empty($password)) {
        echo "<script>alert('PLEASE FILL UP THE FORM!')</script>";
    } else {

        $stmt = $conn->prepare("SELECT * FROM users WHERE BINARY username = ? AND BINARY password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $rowsqlValidate = $result->fetch_assoc();
            $_SESSION['status'] = 'valid';
            $_SESSION['username'] = $rowsqlValidate['username'];
            $_SESSION['roles'] = $rowsqlValidate['roles'];

            // Handle Remember Me functionality
            if (isset($_POST['remember'])) {
                setcookie('remember_username', $username, time() + (86400 * 30), "/"); // 30 days
                setcookie('remember_password', $password, time() + (86400 * 30), "/"); // 30 days
            } else {
                // Remove cookies if Remember Me is unchecked
                setcookie('remember_username', '', time() - 3600, "/");
                setcookie('remember_password', '', time() - 3600, "/");
            }

            // Redirect based on role
            switch ($_SESSION['roles']) {
                case 'admin':
                    pathto('adminpage');
                    break;
                case 'student':
                    pathto('searchpage');
                    break;
                default:
                    echo "<script>alert('Invalid user type');</script>";
                    pathto('online-login');
                    break;
            }
        } else {
            $_SESSION['status'] = 'invalid';
            echo "<script>alert('INCORRECT USERNAME OR PASSWORD')</script>";
        }
    }
}

$remembered_username = isset($_COOKIE['remember_username']) ? $_COOKIE['remember_username'] : '';
$remembered_password = isset($_COOKIE['remember_password']) ? $_COOKIE['remember_password'] : '';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>

    <!-- External CSS for login form styling -->
    <link rel="stylesheet" href="css/online-login.css">

    <!-- Font Awesome icons for the user, lock, and eye icons -->
    <link rel="stylesheet" href="icon-font-style/all.min.css">
    <link rel="stylesheet" href="icon-font-style/fontawesome.min.css">
</head>

<body>

    <header>

        <nav class="navbar">
            <div class="logo" style="font-size: 24px; font-weight: bold;">LIBRARY MANAGEMENT SYSTEM</div>
            <form action="user/searchpage.php" method="POST">
                <button type="submit" class="login-button">Return to Search</button>
            </form>
        </nav>
    </header>

    <div class="login-container">

        <h2>Login Form</h2>
        <form action="online-login.php" method="POST">

            <!-- Username input field -->
            <div class="input-group">
                <input type="text" name="login-username" placeholder="Enter Your ID" value="<?php echo htmlspecialchars($remembered_username); ?>">
                <span class="icon"><i class="fa-solid fa-user"></i></span>
            </div>

            <!-- Password input field with toggle visibility icon -->
            <div class="input-group">
                <input type="password" id="password" name="login-password" placeholder="Enter Your Password" value="<?php echo htmlspecialchars($remembered_password); ?>">
                <span class="icon icon-lock"><i class="fa fa-lock"></i></span>
                <span class="icon toggle-password" id="togglePassword" onclick="togglePassword()">
                    <i id="eyeIcon" class="fa fa-eye"></i>
                </span>
            </div>

            <!-- Remember Me checkbox -->
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember" <?php echo (isset($_COOKIE['remember_username']) && isset($_COOKIE['remember_password'])) ? 'checked' : ''; ?>>
                <label for="remember">Remember Me</label>
            </div>

            <!-- Submit button -->
            <button type="submit" name="loginbutton" class="login-button">
                <i class="fas fa-sign-in-alt"></i> SIGN IN
            </button>
        </form>
    </div>

    <script src="js/eyefeatures.js"></script>

</body>

</html>