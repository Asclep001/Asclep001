<?php
require('../includes/dbconn.php');
include_once('../redirect.php');

// Add security check here
if ($_SESSION['roles'] !== 'admin') {
    header('Location: ../online-login.php');
    exit();
}

include('../facilitylogsread.php');
include('../studmngmntread.php');
include('../bkmngmntread.php');




$query = "SHOW COLUMNS FROM stdinfo LIKE 'studGender'";
$result = mysqli_query($conn, $query);

if ($result) {
    $row = mysqli_fetch_array($result);
    $enumList = $row['Type'];
    $enumList = str_replace("enum('", "", $enumList);
    $enumList = str_replace("')", "", $enumList);
    $enumValues = explode("','", $enumList);
}

$query1 = "SELECT course_name FROM courses";
$result1 = mysqli_query($conn, $query1);

if ($result1) {
    $enumValues1 = array();

    // Loop through the result set and add course names to the array
    while ($row1 = mysqli_fetch_assoc($result1)) {
        $enumValues1[] = $row1['course_name'];
    }
} else {
    echo "Error retrieving courses: " . mysqli_error($conn);
}

// Fetch student info to display in the table
$sqlStdinfo = mysqli_query($conn, "SELECT * FROM stdinfo");

// Count total number of students
$studentCountQuery = "SELECT COUNT(*) as total FROM stdinfo";
$studentCountResult = mysqli_query($conn, $studentCountQuery);
$studentCount = mysqli_fetch_assoc($studentCountResult)['total'];

$bookCountQuery = "SELECT SUM(booktotalQuantity) as total FROM tbbook"; // Changed to SUM
$bookCountResult = mysqli_query($conn, $bookCountQuery);
$bookCount = mysqli_fetch_assoc($bookCountResult)['total'];

$borrowCountQuery = "SELECT COUNT(*) as total FROM borrow_records";
$borrowCountResult = mysqli_query($conn, $borrowCountQuery);
$borrowCount = mysqli_fetch_assoc($borrowCountResult)['total'];

// Fetch the latest student ID from the database
$queryLatestId = "SELECT studID FROM stdinfo ORDER BY studID DESC LIMIT 1";
$resultLatestId = mysqli_query($conn, $queryLatestId);
$latestIdRow = mysqli_fetch_assoc($resultLatestId);

// Check if a result was returned
if (isset($latestIdRow['studID'])) {
    $latestId = $latestIdRow['studID'];

    // Extract numeric part from the ID
    preg_match('/(\d+)$/', $latestId, $matches);
    $numericPart = isset($matches[1]) ? (int)$matches[1] : 0;

    // Increment the numeric part
    $newNumericPart = $numericPart + 1;

    // Reconstruct the new ID (assuming no prefix, adjust if needed)
    $newId = preg_replace('/\d+$/', $newNumericPart, $latestId);
} else {
    // Default ID if no records exist
    $newId = '1'; // Adjust this if you have a specific format
}

// Fetch the latest book number from the database
$queryLatestBookNumber = "SELECT bookdewey FROM tbbook ORDER BY bookdewey DESC LIMIT 1";
$resultLatestBookNumber = mysqli_query($conn, $queryLatestBookNumber);
$latestBookNumberRow = mysqli_fetch_assoc($resultLatestBookNumber);

// Check if a result was returned
if (isset($latestBookNumberRow['bookdewey'])) {
    $latestBookNumber = $latestBookNumberRow['bookdewey'];

    // Increment the book number
    $newBookNumber = $latestBookNumber + 1;
} else {
    // Default book number if no records exist
    $newBookNumber = 1; // Adjust this if you have a specific format
}


// Add security check here
if ($_SESSION['roles'] !== 'admin') {
    header('Location: ../online-login.php');
    exit();
}

// Get admin information from database based on logged in username
$username = $_SESSION['username']; // Assuming the username is stored in the session
$queryAdminInfo = "
    SELECT  
        tbadmininfo.adminFname, 
        tbadmininfo.adminMname, 
        tbadmininfo.adminLname, 
        tbadmininfo.adminEmail, 
        tbadmininfo.adminContact, 
        tbadmininfo.role,
        users.username,
        users.password 
    FROM 
        tbadmininfo 
    JOIN 
        users ON tbadmininfo.username = users.username 
    WHERE 
        tbadmininfo.username = ?";
$stmt = $conn->prepare($queryAdminInfo);
$stmt->bind_param("s", $username); // Bind the username
$stmt->execute();
$resultAdminInfo = $stmt->get_result();

if ($resultAdminInfo->num_rows > 0) {
    $adminInfo = $resultAdminInfo->fetch_assoc();
    $adminFName = htmlspecialchars($adminInfo['adminFname']);
    $adminMName = htmlspecialchars($adminInfo['adminMname']);
    $adminLName = htmlspecialchars($adminInfo['adminLname']);
    $adminContacts = htmlspecialchars($adminInfo['adminContact']);
    $adminEmail = htmlspecialchars($adminInfo['adminEmail']);
    $username = htmlspecialchars($adminInfo['username']); // Fetch the username
    $password = htmlspecialchars($adminInfo['password']); // Fetch the password
} else {
    // Default values if no admin info is found
    $adminFName = "Unknown";
    $adminMName = "Unknown";
    $adminLName = "Unknown";
    $adminContacts = "Unknown";
    $adminEmail = "Unknown";
    $username = "Unknown"; // Handle case where password is not found
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <!-- ========== CSS ========== -->

    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/bookmanages.css">
    <link rel="stylesheet" href="../css/addstudent.css">
    <link rel="stylesheet" href="../css/addbook.css">
    <link rel="stylesheet" href="../css/borrowmanage.css">
    <link rel="stylesheet" href="../css/loghistory.css">
    <link rel="stylesheet" href="../css/refresh-logs.css">
    <link rel="stylesheet" href="../css/addborrow.css">
    <link rel="stylesheet" href="../css/studmanages.css">
    <link rel="stylesheet" href="../css/adminprofile.css">
    <link rel="stylesheet" href="../css/profilemodal.css">
    <link rel="stylesheet" href="../css/returnhistory.css">
    <!-- ========== Boxicons CSS ========== -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

</head>




<body>



    <nav class="sidebar">

        <header>
            <div class="profile-header nav-link" data-section="profile">
                <div class="image-text">
                    <span class="image">

                        <img src="../studimg/defaultman.png" alt="Admin Image" class="profile-image" width="40px" height="40px" style="margin-right: 5px;">

                    </span>

                    <div class="text header-text">
                        <span class="name" style="font-size: 12px; margin-bottom: 5px;">Welcome, <?php echo htmlspecialchars($adminInfo['adminFname']); ?></span>
                        <span class="profession" style=" color: #707070;"> <?php echo htmlspecialchars($adminInfo['role']); ?> <span style="display: inline-block; width: 10px; height: 10px; background-color: green; border-radius: 50%; margin-left: 5px;"></span>
                            Online</span>
                    </div>

                </div>
            </div>
        </header>

        </div>

        <div class="menu-bar">
            <div class="menu">


                <ul class="menu-links">

                    <li>
                        <span class="text nav-text" id="currentDateTime"> </span>
                    </li>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            function updateDateTime() {
                                const now = new Date();
                                const options = {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric'
                                };
                                const formattedDate = now.toLocaleDateString('en-US', options);
                                const formattedTime = now.toLocaleTimeString('en-US', {
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    second: '2-digit'
                                });
                                document.getElementById('currentDateTime').textContent = `${formattedDate} ${formattedTime}`;
                            }

                            // Update the date and time every second
                            setInterval(updateDateTime, 1000);
                            // Initial call to display the time immediately
                            updateDateTime();
                        });
                    </script>


                    <li class="nav-link" data-section="dashboard">
                        <a href="">
                            <i class='bx bx-bar-chart-alt-2 icon'></i>
                            <span class="text nav-text">Dashboard</span>
                        </a>
                    </li>

                    <li class="nav-link" data-section="log-history">
                        <a href="#">
                            <i class='bx bx-right-arrow-alt icon'></i>
                            <span class="text nav-text">Log History</span>
                        </a>
                    </li>

                    <li class="nav-link" data-section="return">
                        <a href="">
                            <i class='bx bx-user-pin icon'></i>
                            <span class="text nav-text">Student List</span>
                        </a>
                    </li>

                    <li class="nav-link" data-section="book-list">
                        <a href="">
                            <i class='bx bx-list-ul icon'></i>
                            <span class="text nav-text">Book Accession</span>
                        </a>
                    </li>

                    <li class="nav-link" data-section="category">
                        <a href="">
                            <i class='bx bx-category icon'></i>
                            <span class="text nav-text">Borrow Book</span>
                        </a>
                    </li>

                    <li class="nav-link" data-section="student-list">
                        <a href="">
                            <i class='bx bx-arrow-back icon'></i>
                            <span class="text nav-text">Return History</span>
                        </a>
                    </li>


                </ul>
            </div>

            <!-- Bottom sidebar -->
            <div class="bottom-content">
                <li class="">
                    <a href="../logout.php">
                        <i class='bx bx-log-out icon'></i>
                        <span class="text nav-text">Logout</span>
                    </a>
                </li>

            </div>

        </div>
    </nav>

    <section class="main-content">

        <div class="text" id="profile" style="display:none;">

            <h2 class="main-text"> PROFILE </h2>
            <div class="admincontainer">
                <div class="profile-container">
                    <div class="profile-header">
                        <img src="../studimg/defaultman.png" alt="Admin Image" class="profile-image" width="80px" height="80px" style="margin-right: 5px;">

                        <h4>username: <?php echo htmlspecialchars($adminInfo['username']); ?></h4>
                        <h4>password: <?php echo htmlspecialchars($adminInfo['password']); ?></h4>
                    </div>
                    <div class="profile-info">
                        <form action="../save_adminprofile.php" method="POST" class="profile-form">

                            <div class="form-group">
                                <label for="adminFname">First Name:</label>
                                <input type="text" name="adminFname" id="adminFname" value="<?php echo $adminFName; ?>" required disabled>
                            </div>
                            <div class="form-group">
                                <label for="adminMname">Middle Name:</label>
                                <input type="text" name="adminMname" id="adminMname" value="<?php echo $adminMName; ?>" required disabled>
                            </div>
                            <div class="form-group">
                                <label for="adminLname">Last Name:</label>
                                <input type="text" name="adminLname" id="adminLname" value="<?php echo $adminLName; ?>" required disabled>
                            </div>
                            <div class="form-group">
                                <label for="adminContact">Contact Number:</label>
                                <input type="text" name="adminContact" id="adminContact" value="<?php echo $adminContacts; ?>" required disabled>
                            </div>
                            <div class="form-group">
                                <label for="adminEmail">Email:</label>
                                <input type="email" name="adminEmail" id="adminEmail" value="<?php echo $adminEmail; ?>" required disabled>
                            </div>
                            <div class="form-group">
                                <button type="button" id="editButton" class="btn btn-edit">Update Profile</button>
                                <button type="submit" name="confirmButton" class="btn btn-confirm" style="display: none;">Confirm Update</button>
                                <button type="button" id="cancelButton" class="btn btn-cancel" style="display: none;">Cancel</button>
                            </div>
                        </form>
                    </div>

                </div>

                <div class="addstaff">
                    <button class="addstaff-btn">ADD STAFF</button>
                    <table class="staff-table">
                        <thead>
                            <tr>
                                <th>STAFF ID</th>
                                <th>FULLNAME</th>
                                <th>ROLE</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                    </table>

                </div>
            </div>


        </div>

        <!-- DASHBOARD PAGES -->

        <div class="text" id="dashboard">

            <h2 class="main-text">DASHBOARD</h2>

            <div class="boxes">

                <div class="boxx box1">
                    <i class='bx bxs-user-account'></i>
                    <h1 class="texts" style="color: black;">STUDENTS</h1>
                    <span class="number"><?php echo number_format($studentCount); ?></span>
                </div>

                <div class="boxx box2">
                    <i class='bx bxs-book'></i>
                    <h1 class="texts" style="color: black;">NUMBER OF BOOKS</h1>
                    <span class="number"><?php echo number_format($bookCount); ?></span>
                </div>

                <div class="boxx box3">
                    <i class='bx bxs-book-reader'></i>
                    <h1 class="texts" style="color: black;">NUMBER OF UNRETURN BOOKS</h1>
                    <span class="number"><?php echo number_format($borrowCount) ?></span>
                </div>



            </div>

        </div>


        <!-- LOGHISTORY PAGES -->
        <div class="text" id="log-history" style="display:none;">
            <h2 class="main-text"> LOG HISTORY </h2>


            <div class="stud-logs">

                <table class="student-logs">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>FULLNAME</th>
                            <th>PURPOSE</th>
                            <th>DATE</th>
                        </tr>
                    </thead>

                    <tbody>

                    </tbody>



                </table>
            </div>

        </div>

        <!-- STUDENT LIST PAGE -->
        <div class="text" id="return" style="display:none;">
            <h2 class="main-text">STUDENT LIST SECTION</h2>

            <div class="stud-list">
                <form action="../create-student.php" method="POST" enctype="multipart/form-data">

                    <div class="add-stud">
                        <div class="stud-container" id="studentForm" style="display:none;">

                            <div class="stud-card">
                                <div class="left-section">

                                    <div class="image-placeholder">
                                        <input type="file" name="studimage" id="studentimage" style="display: none;" onchange="updateStudentFileName()">
                                        <label for="studentimage" class="custom-file-upload">
                                            <span id="file-name-student">No file chosen</span>
                                            <button type="button" onclick="document.getElementById('studentimage').click();">Choose File</button>
                                        </label>
                                    </div>

                                    <div class="student-label" style="color: black;">STUDENT</div>
                                </div>


                                <div class="right-section">

                                    <div class="form-stud">
                                        <label>STUDENT ID :</label>
                                        <input type="text" name="stud-id" placeholder="Enter Student ID" value="<?php echo $newId; ?>" required>
                                    </div>


                                    <div class="form-stud">
                                        <label>First Name :</label>
                                        <input type="text" name="stud-fname" placeholder="ENTER YOUR FIRSTNAME" required>
                                    </div>

                                    <div class="form-stud">
                                        <label>Middle Name :</label>
                                        <input type="text" name="stud-mdname" placeholder="ENTER YOUR MIDDLENAME" required>
                                    </div>

                                    <div class="form-stud">
                                        <label>Last Name :</label>
                                        <input type="text" name="stud-lname" placeholder="ENTER YOUR LASTNAME" required>
                                    </div>

                                    <div class="form-stud">
                                        <label>Course :</label>
                                        <div class="course-selection">
                                            <select name="stud-course">
                                                <option value="" disabled selected>Select Your Course/Strand</option>
                                                <?php
                                                if (!empty($enumValues1)) {
                                                    foreach ($enumValues1 as $value1) {
                                                        echo "<option value='$value1'>$value1</option>";
                                                    }
                                                } else {
                                                    echo "<option value='' disabled>No courses available</option>";
                                                }
                                                ?>
                                            </select>
                                            <button type="button" class="add-course-btn" onclick="openCourseModal()">
                                                <i class='bx bx-plus'></i> Add Course/Strand
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-stud">
                                        <label>Gender :</label>
                                        <select name="stud-gender" required>
                                            <option value="" disabled selected>Select Your Gender</option>
                                            <?php
                                            if (!empty($enumValues)) {
                                                foreach ($enumValues as $value) {
                                                    echo "<option value='$value'>$value</option>";
                                                }
                                            } else {
                                                echo "<option value='' disabled>No genders available</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>


                                    <div class="form-stud">
                                        <label>Phone Number :</label>
                                        <input type="number" name="stud-contact" placeholder="ENTER YOUR PHONE NUMBER" id="stud-contact" maxlength="11">
                                    </div>

                                    <div class="form-stud">
                                        <label>Email Address :</label>
                                        <input type="email" name="stud-email" placeholder="ENTER YOUR EMAIL" id="stud-email" required>
                                    </div>

                                    <div class="form-stud">
                                        <label>Address :</label>
                                        <textarea name="stud-address" id="stud-address" placeholder="ENTER YOUR ADDRESS" required rows="5" style="resize: none; width: 100%;"></textarea>
                                    </div>
                                </div>

                                <div class="stud-buttons">
                                    <button class="cancel-btn" onclick="toggleStudentForm()">Cancel</button>
                                    <input type="submit" name="create-stdinfo" class="create-btn" value="Create">
                                </div>
                            </div>
                        </div>

                        <button type="button" id="add-stud-info" onclick="toggleStudentForm()" style="cursor:pointer;  margin-bottom: 20px;">
                            <i class='bx bxs-add-to-queue'> Add Student </i>
                        </button>

                        <script>
                            function toggleStudentForm() {
                                var form = document.getElementById('studentForm');
                                if (form.style.display === "none") {
                                    form.style.display = "block"; // Show the form
                                } else {
                                    form.style.display = "none"; // Hide the form
                                }
                            }
                        </script>
                    </div>
                </form>

                <form action="../import_students.php" method="POST" enctype="multipart/form-data">

                    <div class="stud-import">
                        <label for="import-stud" class="custom-file-upload" style="cursor: pointer;">Import Student CSV File</label>
                        <input type="file" name="import-stud" id="import-stud" accept=".csv" style="cursor:pointer; font-size: 15px;" required>

                        <button type="submit" name="import_students" class="import-btn" style="cursor:pointer; background-color: blue; color: white; border-radius: 5px; padding: 10px 15px;">Import Students File</button>
                    </div>

                </form>

                <div class="stud-search-engine">
                    <form action="" method="POST">
                        <label for="search-stud">Search Student:</label>
                        <div class="search-container">
                            <input type="text" name="search-stud" id="search-stud" placeholder="Search Student..." value="<?php echo isset($_POST['search-stud']) ? htmlspecialchars($_POST['search-stud']) : ''; ?>">
                            <button type="submit" name="search-stud-btn" class="search-btn">SEARCH</button>
                        </div>
                    </form>
                    
                </div>

            </div>

            <div class="table">
                <table class="student-info management">
                    <tr>
                        <th>STUDENT DETAILS</th>
                    </tr>

                    <?php
                    // Check if the search form has been submitted
                    if (isset($_POST['search-stud-btn'])) {
                        $searchTerm = mysqli_real_escape_string($conn, $_POST['search-stud']);
                        $searchQuery = "SELECT * FROM stdinfo WHERE studID LIKE '%$searchTerm%' 
                                    OR studFname LIKE '%$searchTerm%' 
                                    OR studMdname LIKE '%$searchTerm%' 
                                    OR studLname LIKE '%$searchTerm%'
                                    OR course_name LIKE '%$searchTerm%'";
                        $sqlStudinfo = mysqli_query($conn, $searchQuery);
                    } else {
                        // Default query to fetch all students
                        $queryStudinfo = "SELECT * FROM stdinfo";
                        $sqlStudinfo = mysqli_query($conn, $queryStudinfo);
                    }

                    // Check if there are results and display them
                    if (mysqli_num_rows($sqlStudinfo) > 0) {
                        while ($result = mysqli_fetch_array($sqlStudinfo)) { ?>
                            <tr>
                                <td>
                                    <div class="student-info" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                                        <div class="studentdetails" style="display: flex; align-items: center; margin-right: 10px;">
                                            <div class="studentimage" style="margin-right: 15px;">
                                                <?php
                                                if (empty($result['studImg'])) {
                                                    $studImage = ($result['studGender'] === 'Male') ? 'studimg/defaultman.png' : 'studimg/defaultwoman.png';
                                                } else {
                                                    $studImage = $result['studImg'];
                                                }
                                                ?>
                                                <img src="../<?php echo htmlspecialchars($studImage); ?>" alt="Student Image" width="100" height="100" style="border-radius: 50%; border: 2px solid #007bff;">
                                            </div>
                                            <div>
                                                <p>STUDENT ID: <strong><span><?php echo $result['studID'] ?></span></strong></p>
                                                <p>NAME: <strong><span><?php echo $result['studFname'] . " " . $result['studMdname'] . " " . $result['studLname'] ?></span></strong></p>
                                                <p>PROGRAM: <strong><span><?php echo $result['course_name'] ?></span></strong></p>
                                                <p>GENDER: <strong><span><?php echo $result['studGender'] ?></span></strong></p>
                                                <p>EMAIL: <strong><span><?php echo $result['studEmail'] ?></span></strong></p>
                                                <p>PHONE NUMBER: <strong><span><?php echo $result['studContact'] ?></span></strong></p>
                                                <p>ADDRESS: <strong><span><?php echo $result['studAddress'] ?></span></strong></p>
                                            </div>
                                        </div>

                                        <form method="post" class="action-zuttons" style="display: flex; justify-content: space-between; margin-top: 10px;">
                                            <input type="hidden" name="view-id" value="<?php echo $result['studID']; ?>">
                                            <input type="hidden" name="view-studname" value="<?php echo $result['studFname'] . " " . $result['studMdname'] . " " . $result['studLname']; ?>">
                                            <input type="hidden" name="view-course" value="<?php echo $result['course_name']; ?>">

                                            <button type="button" name="update-stud" class="action-btn edit-btn" style="background-color: #007bff; color: white; border: none; border-radius: 5px; padding: 10px 15px; cursor: pointer;"
                                                data-studid="<?php echo htmlspecialchars($result['studID']); ?>"
                                                data-studfname="<?php echo htmlspecialchars($result['studFname']); ?>"
                                                data-studmname="<?php echo htmlspecialchars($result['studMdname']); ?>"
                                                data-studlname="<?php echo htmlspecialchars($result['studLname']); ?>"
                                                data-studcourse="<?php echo htmlspecialchars($result['course_name']); ?>"
                                                data-studgender="<?php echo htmlspecialchars($result['studGender']); ?>"
                                                data-studcontact="<?php echo htmlspecialchars($result['studContact']); ?>"
                                                data-studemail="<?php echo htmlspecialchars($result['studEmail']); ?>"
                                                data-studaddress="<?php echo htmlspecialchars($result['studAddress']); ?>"
                                                data-studimg="<?php echo htmlspecialchars($result['studImg']); ?>"
                                                onclick="openEditModal(this)">
                                                <i class='bx bx-edit-alt'></i> Update
                                            </button>

                                            <button type="button" name="view-stud" class="action-btn view-btn" style="background-color: #28a745; color: white; border: none; border-radius: 5px; padding: 10px 15px; cursor: pointer;" onclick="printStudentID('<?php echo htmlspecialchars($result['studID']); ?>')">
                                                <i class='bx bx-show'></i> Print ID
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                    <?php }
                    } else {
                        echo "<tr><td colspan='1'>No students found.</td></tr>";
                    }
                    ?>
                </table>
            </div>

        </div>



        <div id="courseModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeCourseModal()">&times;</span>
                <h2>Add New Course/Strand</h2>

                <form id="addCourseForm" action="../add-course.php" method="POST">
                    <div class="form-group">
                        <label for="courseName">Course/Strand Name:</label>
                        <input type="text" id="courseName" name="course_name">
                    </div>
                    <div class="form-group">
                        <label for="courseCode">Course/Strand Code:</label>
                        <input type="text" id="courseCode" name="course_code">
                    </div>
                    <button type="submit" class="submit-course-btn">Add Course/Strand</button>
                </form>

            </div>
        </div>

        <script>
            function openCourseModal() {
                document.getElementById('courseModal').style.display = 'block';
            }

            function closeCourseModal() {
                document.getElementById('courseModal').style.display = 'none';
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('courseModal');
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            }
        </script>

        <style>
            .course-selection {
                display: flex;
                gap: 10px;
                align-items: center;
            }

            .add-course-btn {
                padding: 8px 12px;
                background-color: #28a745;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .add-course-btn:hover {
                background-color: #218838;
            }

            .modal {
                display: none;
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.4);
            }

            .modal-content {
                background-color: #fefefe;
                margin: 15% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
                max-width: 500px;
                border-radius: 8px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .form-group label {
                display: block;
                margin-bottom: 5px;
            }

            .form-group input {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }

            .submit-course-btn {
                background-color: #007bff;
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                width: 100%;
            }

            .submit-course-btn:hover {
                background-color: #0056b3;
            }
        </style>

        <!-- BOOK INVENTORY PAGE -->
        <div class="text" id="book-list" style="display:none;">
            <h2 class="main-text">BOOK INVENTORY</h2>

            <div class="book-list">
                <form action="../create-book.php" method="post" enctype="multipart/form-data">
                    <div class="add-book">
                        <div class="book-container" id="bookForm" style="display:none;">

                            <div class="book-card">
                                <div class="left-section">

                                    <div class="book-label" style="color: black;">
                                        BOOK
                                        INFORMATION
                                    </div>

                                    <div class="image-placeholder">
                                        <input type="file" name="book-img" id="book-img" style="display: none;" onchange="updateBookFileName()">
                                        <label for="book-img" class="custom-file-upload">
                                            <span id="file-name">No file chosen</span>
                                            <button type="button" onclick="document.getElementById('book-img').click();">Choose File</button>
                                        </label>

                                        <img id="imagePreview" src="" alt="Book Image" style="display:none;">
                                    </div>


                                </div>


                                <div class="right-section">

                                    <div class="form-book">
                                        <label>Book NUMBER:</label>
                                        <input type="number" name="book-dewey" placeholder="Enter Book ID" value="<?php echo $newBookNumber; ?>" required>
                                    </div>

                                    <div class="form-book">
                                        <label>Book ISBN :</label>
                                        <input type="number" name="bookIsbn" placeholder="Enter Book ISBN" maxlength="13">
                                    </div>

                                    <div class="form-book">
                                        <label>Book Title :</label>
                                        <input type="text" name="book-title" placeholder="Enter Book Title" required>
                                    </div>

                                    <div class="form-book">
                                        <label>Book Author :</label>
                                        <input type="text" name="book-author" placeholder="Enter Book Author" required>
                                    </div>

                                    <div class="form-book">
                                        <label>Book Publisher :</label>
                                        <input type="text" name="book-publisher" placeholder="Enter Book Publisher" required>
                                    </div>

                                    <div class="form-book">
                                        <label>Quanity :</label>
                                        <input type="number" name="book-quantity" placeholder="Enter Book Quantity" min="1" max="100" value="1" required>
                                    </div>

                                    <div class="form-book">
                                        <label>Number of Pages :</label>
                                        <input type="number" name="book-pages" placeholder="Enter Number of Pages" required>
                                    </div>

                                    <div class="form-book">
                                        <label>Book Category :</label>
                                        <div class="category-selection">
                                            <select name="book-category" required>
                                                <option value="" disabled selected>Select Book Category</option>
                                                <?php
                                                // Fetch categories from database
                                                $categoryQuery = "SELECT category_name FROM book_categories ORDER BY category_name  ASC";
                                                $categoryResult = mysqli_query($conn, $categoryQuery);

                                                if (mysqli_num_rows($categoryResult) > 0) {
                                                    while ($category = mysqli_fetch_assoc($categoryResult)) {
                                                        echo "<option value='" . htmlspecialchars($category['category_name']) . "'>"
                                                            . htmlspecialchars($category['category_name']) . "</option>";
                                                    }
                                                } else {
                                                    echo "<option value='' disabled>No categories available</option>";
                                                }
                                                ?>
                                            </select>
                                            <button type="button" class="add-category-btn" onclick="openCategoryModal()">
                                                <i class='bx bx-plus'></i> Add Category
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-book">

                                        <label>Book Genre :</label>
                                        <button type="button" class="genre-button" onclick="openGenreModal()">Select Genres</button>

                                        <div id="genreModal" class="modal" style="display:none;">
                                            <div class="modal-content">
                                                <span class="close" onclick="closeGenreModal()">&times;</span>
                                                <h2>Select Book Genres</h2>

                                                <div class="genre-options">
                                                    <div class="genre-column">
                                                        <label class="checkbox-container">
                                                            <input type="checkbox" name="book-genre[]" value="Fiction" id="genre-fiction">
                                                            <span class="custom-checkbox"></span>
                                                            <span class="label-text">Fiction</span>
                                                        </label>
                                                        <label class="checkbox-container">
                                                            <input type="checkbox" name="book-genre[]" value="Adventure" id="genre-adventure">
                                                            <span class="custom-checkbox"></span>
                                                            <span class="label-text">Adventure</span>
                                                        </label>
                                                        <label class="checkbox-container">
                                                            <input type="checkbox" name="book-genre[]" value="Classics" id="genre-classics">
                                                            <span class="custom-checkbox"></span>
                                                            <span class="label-text">Classics</span>
                                                        </label>
                                                        <label class="checkbox-container">
                                                            <input type="checkbox" name="book-genre[]" value="Crime" id="genre-crime">
                                                            <span class="custom-checkbox"></span>
                                                            <span class="label-text">Crime</span>
                                                        </label>
                                                        <label class="checkbox-container">
                                                            <input type="checkbox" name="book-genre[]" value="Drama" id="genre-drama">
                                                            <span class="custom-checkbox"></span>
                                                            <span class="label-text">Drama</span>
                                                        </label>
                                                    </div>
                                                    <div class="genre-column">
                                                        <label class="checkbox-container">
                                                            <input type="checkbox" name="book-genre[]" value="Fantasy" id="genre-fantasy">
                                                            <span class="custom-checkbox"></span>
                                                            <span class="label-text">Fantasy</span>
                                                        </label>
                                                        <label class="checkbox-container">
                                                            <input type="checkbox" name="book-genre[]" value="Historical Fiction" id="genre-historical-fiction">
                                                            <span class="custom-checkbox"></span>
                                                            <span class="label-text">Historical Fiction</span>
                                                        </label>
                                                        <label class="checkbox-container">
                                                            <input type="checkbox" name="book-genre[]" value="Horror" id="genre-horror">
                                                            <span class="custom-checkbox"></span>
                                                            <span class="label-text">Horror</span>
                                                        </label>
                                                        <label class="checkbox-container">
                                                            <input type="checkbox" name="book-genre[]" value="Literary Fiction" id="genre-literary-fiction">
                                                            <span class="custom-checkbox"></span>
                                                            <span class="label-text">Literary Fiction</span>
                                                        </label>
                                                        <label class="checkbox-container">
                                                            <input type="checkbox" name="book-genre[]" value="Mystery" id="genre-mystery">
                                                            <span class="custom-checkbox"></span>
                                                            <span class="label-text">Mystery</span>
                                                        </label>
                                                    </div>
                                                    <div class="genre-column">
                                                        <label class="checkbox-container">
                                                            <input type="checkbox" name="book-genre[]" value="Romance" id="genre-romance">
                                                            <span class="custom-checkbox"></span>
                                                            <span class="label-text">Romance</span>
                                                        </label>
                                                        <label class="checkbox-container">
                                                            <input type="checkbox" name="book-genre[]" value="Science Fiction" id="genre-science-fiction">
                                                            <span class="custom-checkbox"></span>
                                                            <span class="label-text">Science Fiction</span>
                                                        </label>
                                                        <label class="checkbox-container">
                                                            <input type="checkbox" name="book-genre[]" value="Thriller" id="genre-thriller">
                                                            <span class="custom-checkbox"></span>
                                                            <span class="label-text">Thriller</span>
                                                        </label>
                                                        <label class="checkbox-container">
                                                            <input type="checkbox" name="book-genre[]" value="Dystopian" id="genre-dystopian">
                                                            <span class="custom-checkbox"></span>
                                                            <span class="label-text">Dystopian</span>
                                                        </label>
                                                        <label class="checkbox-container">
                                                            <input type="checkbox" name="book-genre[]" value="Magical Realism" id="genre-magical-realism">
                                                            <span class="custom-checkbox"></span>
                                                            <span class="label-text">Magical Realism</span>
                                                        </label>
                                                    </div>
                                                </div>

                                                <button type="button" class="done-button" onclick="closeGenreModal()">Done</button>
                                            </div>
                                        </div>
                                    </div>

                                    <script>
                                        function openGenreModal() {
                                            document.getElementById('genreModal').style.display = 'block';
                                        }

                                        function closeGenreModal() {
                                            document.getElementById('genreModal').style.display = 'none';
                                        }
                                    </script>

                                    <style>
                                        .genre-button {
                                            background-color: blue;
                                            color: white;
                                            border: none;
                                            padding: 10px 15px;
                                            border-radius: 5px;
                                        }

                                        .modal {
                                            display: none;
                                            position: fixed;
                                            z-index: 1;
                                            left: 0;
                                            top: 0;
                                            width: 100%;
                                            height: 100%;
                                            overflow: auto;
                                            background-color: rgba(0, 0, 0, 0.5);
                                            padding-top: 60px;
                                        }

                                        .modal-content {
                                            background-color: #fefefe;
                                            margin: 5% auto;
                                            padding: 10px;
                                            border: 1px solid #888;
                                            width: 50%;
                                            border-radius: 8px;
                                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                                        }

                                        .close {
                                            color: #aaa;
                                            float: right;
                                            font-size: 28px;
                                            font-weight: bold;
                                        }

                                        .close:hover,
                                        .close:focus {
                                            color: black;
                                            text-decoration: none;
                                            cursor: pointer;
                                        }

                                        .done-button {
                                            background-color: #28a745;
                                            color: white;
                                            border: none;
                                            padding: 10px 15px;
                                            border-radius: 5px;
                                            cursor: pointer;
                                            margin-top: 10px;
                                        }

                                        .done-button:hover {
                                            background-color: #218838;
                                        }

                                        h2 {
                                            margin-bottom: 15px;
                                        }

                                        .checkbox-container {
                                            display: flex;
                                            align-items: center;
                                            margin: 5px 0;
                                        }

                                        .custom-checkbox {
                                            width: 20px;
                                            height: 20px;
                                            background-color: #e0e0e0;
                                            border: 2px solid #ccc;
                                            border-radius: 4px;
                                            display: inline-block;
                                            position: relative;
                                            transition: background-color 0.3s, border-color 0.3s;
                                        }

                                        .checkbox-container input[type="checkbox"]:checked+.custom-checkbox {
                                            background-color: #4caf50;
                                            border-color: #4caf50;
                                        }

                                        .custom-checkbox::after {
                                            content: "";
                                            position: absolute;
                                            top: 2px;
                                            left: 7px;
                                            width: 6px;
                                            height: 12px;
                                            border: solid white;
                                            border-width: 0 2px 2px 0;
                                            transform: rotate(45deg);
                                            opacity: 0;
                                            transition: opacity 0.3s;
                                        }

                                        .checkbox-container input[type="checkbox"]:checked+.custom-checkbox::after {
                                            opacity: 1;
                                        }

                                        .label-text {
                                            margin-left: 10px;
                                        }

                                        .genre-options {
                                            display: flex;
                                            flex-wrap: wrap;
                                            justify-content: space-between;
                                        }

                                        .genre-column {
                                            flex: 1 1 30%;
                                            margin: 10px;
                                        }
                                    </style>

                                    <div class="form-book">
                                        <label>Book Description :</label>
                                        <textarea name="book-desc" placeholder="Enter Book Description" rows="5" style="resize: none; width: 90%;"></textarea>
                                    </div>


                                </div>

                                <div class="book-buttons">
                                    <button class="cancel-btn" onclick="toggleBookForm()">Cancel</button>
                                    <input type="submit" name="create-bookinfo" class="create-btn" value="CREATE">
                                    <input type="hidden" name="book-id" value="">
                                </div>
                            </div>
                        </div>

                        <button type="button" id="add-book-info" onclick="toggleBookForm()" style="cursor:pointer; margin-bottom: 20px;">
                            <i class='bx bxs-add-to-queue'> Add Book </i>
                        </button>

                        <script>
                            function toggleBookForm() {
                                var form = document.getElementById('bookForm');
                                if (form.style.display === "none") {
                                    form.style.display = "block"; // Show the form
                                } else {
                                    form.style.display = "none"; // Hide the form
                                }
                            }
                        </script>
                    </div>


                </form>

                <div class="import-book">
                    <form action="../import-books.php" method="POST" enctype="multipart/form-data">
                        <label for="import-book" class="custom-file-upload"> Import Books File </label>
                        <input type="file" name="import-book" id="import-book" style="cursor:pointer; font-size: 15px;" required>
                        <button type="submit" name="import_books" style="cursor:pointer; background-color: blue; color: white; border-radius: 5px; padding: 10px 15px;">Import Book File</button>
                    </form>
                </div>

                <div class="search-books">
                    <form action="" method="POST"> <!-- Added form tag for search functionality -->
                        <label for="search-book"> Search Book :</label>
                        <input type="text" name="search-book" id="search-book" placeholder=" Search Book..." value="<?php echo isset($_POST['search-book']) ? htmlspecialchars($_POST['search-book']) : ''; ?>"> <!-- Added value for preserving search term -->
                        <button type="submit" name="bsearch-book" class="book-btn-search"> SEARCH </button>
                    </form>
                </div>

            </div>

            <table class="book-info management">
                <thead>
                    <th style=" border: 1px solid black;">BOOKS INFORMATION</th>
                </thead>

                <?php
                // Check if the search form has been submitted
                if (isset($_POST['bsearch-book'])) {
                    $searchTerm = mysqli_real_escape_string($conn, $_POST['search-book']);
                    $searchQuery = "SELECT * FROM tbbook WHERE bookTitle LIKE '%$searchTerm%' 
                                OR bookAuthor LIKE '%$searchTerm%' 
                                OR bookCategory LIKE '%$searchTerm%'
                                OR bookdewey LIKE '%$searchTerm%' 
                                ORDER BY bookdewey ASC"; // Added ORDER BY to sort by bookdewey
                    $sqlBookinfo = mysqli_query($conn, $searchQuery);
                } else {
                    // Default query to fetch all books sorted by bookdewey
                    $queryBookinfo = "SELECT * FROM tbbook ORDER BY bookdewey ASC"; // Added ORDER BY to sort by bookdewey
                    $sqlBookinfo = mysqli_query($conn, $queryBookinfo);
                }

                while ($book = mysqli_fetch_array($sqlBookinfo)) { ?>
                    <tbody>
                        <tr>
                            <td class="books">
                                <div class="book-table" style="display: flex; align-items: center; padding: 10px 10px">
                                    <div>
                                        <div class="book-number" style="font-size: 30px;">
                                            <?php echo htmlspecialchars($book['bookdewey']); ?>
                                        </div>
                                        <div class="book-img" style="margin-right: 20px;">
                                            <?php
                                            $bookImage = !empty($book['book_img']) ? $book['book_img'] : '/bookImg/default.jpg';
                                            ?>
                                            <img src="../<?php echo htmlspecialchars($bookImage); ?>" alt="Book Image" width="100" height="100">
                                        </div>
                                        <div style="font-size: 12px; font-weight: bold; font-family: Arial, Helvetica, sans-serif;">
                                            Book Quantity : <strong><?php echo htmlspecialchars($book['bookQuantity']); ?></strong>
                                        </div>
                                    </div>
                                    <div class="book-details" style="font-size: 14px;">
                                        Book Title : <strong><?php echo htmlspecialchars($book['bookTitle']); ?></strong> <br>
                                        Book Category : <strong><?php echo htmlspecialchars($book['bookCategory']); ?></strong><br>
                                        Book Genre : <strong><?php echo htmlspecialchars($book['bookGenre']); ?></strong> <br>
                                        Book Author : <strong><?php echo htmlspecialchars($book['bookAuthor']); ?></strong> <br>
                                        Book Publisher : <strong><?php echo htmlspecialchars($book['bookPublisher']); ?></strong> <br>
                                        Book Pages : <strong><?php echo htmlspecialchars($book['bookPages']); ?></strong> <br>
                                        Book Description : <br> <strong><?php echo htmlspecialchars($book['bookDescription']); ?></strong> <br>
                                        Book Indexes : <strong><?php echo htmlspecialchars($book['bookIndexes']); ?></strong> <br>
                                    </div>

                                    <div class="book-action">
                                        <button type="button" name="edit-book" class="action-btn edit-btn"
                                            data-bookid="<?php echo htmlspecialchars($book['bookdewey']); ?>"
                                            data-bookdewey="<?php echo htmlspecialchars($book['bookdewey']); ?>"
                                            data-bookisbn="<?php echo htmlspecialchars($book['bookIsbn']); ?>"
                                            data-booktitle="<?php echo htmlspecialchars($book['bookTitle']); ?>"
                                            data-bookauthor="<?php echo htmlspecialchars($book['bookAuthor']); ?>"
                                            data-bookpublisher="<?php echo htmlspecialchars($book['bookPublisher']); ?>"
                                            data-bookquantity="<?php echo htmlspecialchars($book['bookQuantity']); ?>"
                                            data-bookpages="<?php echo htmlspecialchars($book['bookPages']); ?>"
                                            data-bookcategory="<?php echo htmlspecialchars($book['bookCategory']); ?>"
                                            data-bookgenre="<?php echo htmlspecialchars($book['bookGenre']); ?>"
                                            data-bookdescription="<?php echo htmlspecialchars($book['bookDescription']); ?>"
                                            data-bookindex="<?php echo htmlspecialchars($book['bookIndexes']); ?>"
                                            data-bookimg="<?php echo htmlspecialchars($book['book_img']); ?>">
                                            <i class='bx bx-edit-alt'></i> Edit
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                <?php } ?>
            </table>
        </div>


        <div id="categoryModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeCategoryModal()">&times;</span>
                <h2>Add New Category</h2>

                <form id="addCategoryForm" action="../add-category.php" method="POST">
                    <div class="form-group">
                        <label for="categoryName">Category Name:</label>
                        <input type="text" id="categoryName" name="category_name" required>
                    </div>
                    <button type="submit" class="submit-category-btn">Add Category</button>
                </form>
            </div>
        </div>

        <style>
            .category-selection {
                display: flex;
                gap: 10px;
                align-items: center;
            }

            .add-category-btn {
                padding: 8px 12px;
                background-color: #28a745;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .add-category-btn:hover {
                background-color: #218838;
            }

            .submit-category-btn {
                background-color: #007bff;
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                width: 100%;
                margin-top: 15px;
            }

            .submit-category-btn:hover {
                background-color: #0056b3;
            }
        </style>

        <script>
            function openCategoryModal() {
                document.getElementById('categoryModal').style.display = 'block';
            }

            function closeCategoryModal() {
                document.getElementById('categoryModal').style.display = 'none';
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                const categoryModal = document.getElementById('categoryModal');
                if (event.target === categoryModal) {
                    categoryModal.style.display = 'none';
                }
            }
        </script>


        <!-- BORROW SECTION -->
        <div class="text" id="category" style="display:none;">
            <h2 class="main-text">BORROW SECTION</h2>

            <div class="borrow-list">
                <form action="borrow-book.php" method="post">
                    <div class="add-borrow">
                        <div class="container" id="borrowForm" style="display:none;">
                            <div class="right-section">
                                <div class="form-borrow">
                                    <label>Student ID:</label>
                                    <div class="student-id-container">
                                        <input type="text" name="student-id" id="student-id" placeholder="ENTER STUDENT ID" required>
                                        <span id="student-id-status" class="status-label"></span>
                                    </div>
                                </div>

                                <style>
                                    .student-id-container {
                                        display: flex;
                                        align-items: center;
                                        gap: 10px;
                                    }

                                    .status-label {
                                        font-size: 14px;
                                        padding: 4px 8px;
                                        border-radius: 4px;
                                    }

                                    .status-label.valid {
                                        color: #28a745;
                                    }

                                    .status-label.invalid {
                                        color: #dc3545;
                                    }
                                </style>

                                <div class="form-borrow">
                                    <label for="borrow-book-info">Enter Book Details:</label>
                                    <input type="text" name="borrow-book-info" id="borrow-book-info">
                                </div>
                                <div class="form-borrow-table">
                                    <table class="borrow-info management">
                                        <thead>
                                            <tr>
                                                <th>BOOK INFORMATION</th>
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Rows will be dynamically added here -->
                                        </tbody>
                                    </table>
                                </div>

                                <div class="form-borrow">
                                    <label>Borrowed Date:</label>
                                    <input type="date" name="borrow-date" class="dates" value="<?php echo date('Y-m-d'); ?>" required readonly>
                                </div>

                                <div class="form-borrow">
                                    <label>Return Date:</label>
                                    <input type="date" name="return-date" class="dates" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" required>
                                </div>
                            </div>

                            <div class="borrow-buttons">
                                <button type="button" class="cancel-btn" onclick="toggleBorrowForm()">Cancel</button>
                                <input type="submit" name="borrow-book-btn" class="create-btn" value="BORROW">
                            </div>
                            <br>
                        </div>
                    </div>

                    <button type="button" id="add-borrow-info" onclick="toggleBorrowForm()" style="cursor:pointer;">
                        <i class='bx bxs-add-to-queue'> Issue Book </i>
                    </button>

                    <script>
                        function toggleBorrowForm() {
                            var form = document.getElementById('borrowForm');
                            form.style.display = (form.style.display === "none") ? "block" : "none"; // Toggle visibility
                        }
                    </script>

                    <div class="search-borrow">
                        <label for="search-borrower">SEARCH ISSUED BOOK :</label>
                        <form action="" method="POST"> <!-- Added form tag for search functionality -->
                            <input type="text" name="search-borrower" placeholder="Search Borrower" value="<?php echo isset($_POST['search-borrower']) ? htmlspecialchars($_POST['search-borrower']) : ''; ?>"> <!-- Preserving search term -->
                            <button type="submit" name="bsearch-borrower">SEARCH</button>
                        </form>
                    </div>


                </form>
            </div>

            <div class="table">
                <table class="borrow-info management">
                    <tr>
                        <th>STUDENT ID</th>
                        <th>BOOK TITLE</th>
                        <th>ISSUED DATE</th>
                        <th>RETURN DATE</th>
                        <th>ACTION</th>
                    </tr>

                    <?php
                    // Fetch borrowed book records with search functionality
                    $borrowedBooksQuery = "SELECT b.student_id, b.book_id, b.borrow_date, b.expectedreturn_date, tb.bookTitle 
                                        FROM borrow_records b 
                                        JOIN tbbook tb ON b.book_id = tb.bookdewey";

                    // Check if the search form has been submitted
                    if (isset($_POST['bsearch-borrower']) && !empty($_POST['search-borrower'])) {
                        $searchTerm = mysqli_real_escape_string($conn, $_POST['search-borrower']);
                        $borrowedBooksQuery .= " WHERE b.student_id LIKE '%$searchTerm%'"; // Adjusted search query to include student ID
                    }

                    $borrowedBooksQuery .= " ORDER BY b.borrow_date DESC"; // Keep the ordering
                    $borrowedBooksResult = mysqli_query($conn, $borrowedBooksQuery);

                    while ($borrowedBook = mysqli_fetch_assoc($borrowedBooksResult)) {
                        echo "<tr>
                                <td>" . htmlspecialchars($borrowedBook['student_id']) . "</td>
                                <td>" . htmlspecialchars($borrowedBook['bookTitle']) . "</td>
                                <td>" . htmlspecialchars($borrowedBook['borrow_date']) . "</td>
                                <td>" . htmlspecialchars($borrowedBook['expectedreturn_date']) . "</td>
                                <td>
                                    <form action='../returnbook.php' method='post'>
                                        <input type='hidden' name='book_id' value='" . htmlspecialchars($borrowedBook['book_id']) . "'>
                                        <input type='hidden' name='student_id' value='" . htmlspecialchars($borrowedBook['student_id']) . "'>
                                        <button type='submit' name='return-book' class='action-btn view-btn'>RETURN</button>
                                    </form>
                                </td>
                              </tr>";
                    }
                    ?>
                </table>
            </div>
        </div>



        <!-- RETURN HISTORY PAGE -->
        <div class="text" id="student-list" style="display:none;">
            <h2 class="main-text">RETURN HISTORY</h2>

            <div class="form-return-table">
                <table class="return-info management">
                    <thead>
                        <tr>
                            <th>STUDENT ID</th>
                            <th>Book Titles and Status</th>
                            <th>Return Date</th>
                            <th>Expected Return Date</th>
                            <!-- <th>Total Penalty</th> -->
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch return records from the database with book title and expected return date
                        $queryReturnRecords = "
                        SELECT b.student_id, tb.bookTitle, b.return_date, b.expectedreturn_date, b.penalty, b.status
                        FROM return_record b 
                        JOIN tbbook tb ON b.book_id = tb.bookdewey
                        ORDER BY b.student_id, b.expectedreturn_date";
                        $resultReturnRecords = mysqli_query($conn, $queryReturnRecords);

                        $currentStudentId = null;
                        $bookDetails = [];
                        $totalPenalty = 0;

                        if (mysqli_num_rows($resultReturnRecords) > 0) {
                            while ($row = mysqli_fetch_assoc($resultReturnRecords)) {
                                if ($currentStudentId !== $row['student_id']) {
                                    // If we have a current student, output the previous student's data
                                    if ($currentStudentId !== null) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($currentStudentId) . "</td>";
                                        echo "<td>" . implode('<br>', $bookDetails) . "</td>";
                                        echo "<td>" . htmlspecialchars($returnDate) . "</td>"; // Use the last known return date
                                        // echo "<td>" . htmlspecialchars($expectedDate) . "</td>"; // Use the last known expected date
                                        echo "<td>₱" . number_format($totalPenalty, 2) . "</td>";
                                        echo "<td>
                                                <form action='view_return.php' method='post'>
                                                    <button type='submit' name='view-return' class='action-btn view-btn'>
                                                        <i class='bx bx-show'></i> VIEW
                                                    </button>
                                                </form>
                                              </td>";
                                        echo "</tr>";
                                    }

                                    // Reset for the new student
                                    $currentStudentId = $row['student_id'];
                                    $bookDetails = [];
                                    $totalPenalty = 0;
                                }

                                // Combine book title with status and penalty indication
                                $status = ($row['penalty'] > 0) ? "Penalty: ₱" . number_format($row['penalty'], 2) : "No Penalty";
                                $bookDetails[] = htmlspecialchars($row['bookTitle']) . " - " . $status;

                                // Store the last known return and expected dates
                                $returnDate = $row['return_date'];
                                $expectedDate = $row['expectedreturn_date'];
                            }

                            // Output the last student's data
                            if ($currentStudentId !== null) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($currentStudentId) . "</td>";
                                echo "<td>" . implode('<br>', $bookDetails) . "</td>";
                                echo "<td>" . htmlspecialchars($returnDate) . "</td>"; // Use the last known return date
                                // echo "<td>" . htmlspecialchars($expectedDate) . "</td>"; // Use the last known expected date
                                echo "<td>₱" . number_format($totalPenalty, 2) . "</td>";
                                echo "<td>
                                        <form action='view_return.php' method='post'>
                                            <button type='submit' name='view-return' class='action-btn view-btn'>
                                                <i class='bx bx-show'></i> VIEW
                                            </button>
                                        </form>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No return records found.</td></tr>"; // Adjusted colspan to match the number of columns
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        </div>
    </section>

    <script src="../js/darkmode.js"></script>
    <script src="../js/contentswitch.js"></script>
    <script src="../js/previewimage.js"></script>
    <script src="../js/uploudImg.js"></script>
    <script src="../js/active.js"></script>
    <script src="../js/logrefresh.js"></script>
    <script src="../js/checkstud.js"></script>
    <script src="../js/save_profile.js"></script>
    <script src="../js/fetchingbook.js"></script>
    <script src="../js/showreturn.js"></script>

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Student Information</h2>
            <form id="editStudentForm" action="../update_studinfo.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="studID" id="editStudID">

                <div class="form-group">
                    <label for="editStudFName">First Name:</label>
                    <input type="text" name="studFname" id="editStudFName">
                </div>

                <div class="form-group">
                    <label for="editStudMName">Middle Name:</label>
                    <input type="text" name="studMName" id="editStudMName">
                </div>

                <div class="form-group">
                    <label for="editStudLName">Last Name:</label>
                    <input type="text" name="studLName" id="editStudLName">
                </div>

                <div class="form-group">
                    <label for="editStudCourse">Course:</label>
                    <select name="studCourse" id="editStudCourse">
                        <option value="" disabled>Select Course/Strand</option>
                        <?php foreach ($enumValues1 as $course) { ?>
                            <option value="<?php echo htmlspecialchars($course); ?>"><?php echo htmlspecialchars($course); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editStudGender">Gender:</label>
                    <select name="studGender" id="editStudGender">
                        <option value="" disabled>Select Gender</option>
                        <?php foreach ($enumValues as $gender) { ?>
                            <option value="<?php echo htmlspecialchars($gender); ?>"><?php echo htmlspecialchars($gender); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editStudContact">Contact Number:</label>
                    <input type="number" name="studContact" id="editStudContact">
                </div>

                <div class="form-group">
                    <label for="editStudEmail">Email Address:</label>
                    <input type="email" name="studEmail" id="editStudEmail">
                </div>

                <div class="form-group">
                    <label for="editStudAddress">Address:</label>
                    <textarea name="studAddress" id="editStudAddress" rows="3"></textarea>
                </div>

                <!-- New Form Group for Student Image -->
                <div class="form-group">
                    <label for="editStudImg">Student Image:</label>
                    <input type="file" name="studImg" id="editStudImg" accept="image/*">
                    <!-- Optional: Display Current Image -->
                    <img id="currentImagePreview" src="" alt="Current Student Image" style="display:none; width: 100px; height: 100px; margin-top: 10px;">
                </div>

                <div class="form-actions">
                    <button type="button" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="update-student">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- The Modal (background) -->
    <style>
        /* Existing styles... */
        .form-group img {
            display: block;
        }
    </style>

    <script>
        // Function to open the edit modal and populate it with student data
        function openEditModal(button) {
            var modal = document.getElementById('editStudentModal');

            // Get data attributes from the button
            var studId = button.getAttribute('data-studid');
            var studFName = button.getAttribute('data-studfname');
            var studMName = button.getAttribute('data-studmname');
            var studLName = button.getAttribute('data-studlname');
            var studCourse = button.getAttribute('data-studcourse');
            var studGender = button.getAttribute('data-studgender');
            var studContact = button.getAttribute('data-studcontact');
            var studEmail = button.getAttribute('data-studemail');
            var studAddress = button.getAttribute('data-studaddress');
            var studImg = button.getAttribute('data-studimg'); // Add this line to get the current image

            // Populate the form fields
            document.getElementById('editStudID').value = studId;
            document.getElementById('editStudFName').value = studFName;
            document.getElementById('editStudMName').value = studMName;
            document.getElementById('editStudLName').value = studLName;
            document.getElementById('editStudCourse').value = studCourse;
            document.getElementById('editStudGender').value = studGender;
            document.getElementById('editStudContact').value = studContact;
            document.getElementById('editStudEmail').value = studEmail;
            document.getElementById('editStudAddress').value = studAddress;

            // Populate and display the current image if it exists
            var imagePreview = document.getElementById('currentImagePreview');
            if (studImg) {
                imagePreview.src = "../" + studImg;
                imagePreview.style.display = 'block';
            } else {
                imagePreview.style.display = 'none';
            }

            // Display the modal
            modal.style.display = 'block';
        }

        // Function to close the edit modal
        function closeEditModal() {
            var modal = document.getElementById('editStudentModal');
            modal.style.display = 'none';
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            var modal = document.getElementById('editStudentModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Attach event listeners to all edit buttons
        document.addEventListener('DOMContentLoaded', function() {
            var editButtons = document.querySelectorAll('button[name="edit-stud"]');
            editButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    openEditModal(this);
                });
            });
        });
    </script>

    <!-- Edit Book Modal -->
    <div id="editBookModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditBookModal()">&times;</span>
            <h2>Edit Book Information</h2>
            <form id="editBookForm" action="../update_bkinfo.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="bookID" id="editBookID">

                <div class="form-group">
                    <label for="editBookDewey">Book Dewey:</label>
                    <input type="number" name="bookDewey" id="editBookDewey">
                </div>

                <div class="form-group">
                    <label for="editBookIsbn">Book ISBN:</label>
                    <input type="number" name="bookIsbn" id="editBookIsbn" maxlength="13">
                </div>

                <div class="form-group">
                    <label for="editBookTitle">Book Title:</label>
                    <input type="text" name="bookTitle" id="editBookTitle">
                </div>

                <div class="form-group">
                    <label for="editBookAuthor">Book Author:</label>
                    <input type="text" name="bookAuthor" id="editBookAuthor">
                </div>

                <div class="form-group">
                    <label for="editBookPublisher">Book Publisher:</label>
                    <input type="text" name="bookPublisher" id="editBookPublisher">
                </div>

                <div class="form-group">
                    <label for="editBookQuantity">Book Quantity:</label>
                    <input type="number" name="bookQuantity" id="editBookQuantity" min="1" max="100">
                </div>

                <div class="form-group">
                    <label for="editBookPages">Number of Pages:</label>
                    <input type="number" name="bookPages" id="editBookPages">
                </div>

                <div class="form-group">
                    <label for="editBookCategory">Book Category:</label>
                    <select name="bookCategory" id="editBookCategory">
                        <option value="" disabled>Select Book Category</option>
                        <?php
                        // Fetch categories from the database
                        $categoryQuery = "SELECT category_name FROM book_categories ORDER BY category_name ASC";
                        $categoryResult = mysqli_query($conn, $categoryQuery);

                        if (mysqli_num_rows($categoryResult) > 0) {
                            while ($category = mysqli_fetch_assoc($categoryResult)) {
                                echo "<option value='" . htmlspecialchars($category['category_name']) . "'>" . htmlspecialchars($category['category_name']) . "</option>";
                            }
                        } else {
                            echo "<option value='' disabled>No categories available</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editBookGenre">Book Genre:</label>
                    <input type="text" name="bookGenre" id="editBookGenre" placeholder="Enter Book Genre" value="<?php echo isset($book['bookGenre']) ? htmlspecialchars($book['bookGenre']) : ''; ?>" required />
                </div>

                <div class="form-group">
                    <label for="editBookDescription">Book Description:</label>
                    <textarea name="bookDescription" id="editBookDescription" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="editBookIndexes">Book Index:</label>
                    <textarea name="bookIndex" id="editBookIndex" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="editBookImage">Book Image:</label>
                    <input type="file" name="bookImg" id="editBookImage" accept="image/*" onchange="previewEditBookImage()">
                    <img id="editImagePreview" src="" alt="Book Image Preview" style="display:none; width: 100px; height: 100px; margin-top: 10px;">
                </div>

                <div class="form-actions">
                    <button type="button" onclick="closeEditBookModal()">Cancel</button>
                    <button type="submit" name="update-book">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        /* Reuse existing modal styles or add specific styles for editBookModal if needed */
        .modal {
            display: none;
            /* Hidden by default */
            position: fixed;
            /* Stay in place */
            z-index: 1001;
            /* Sit on top */
            left: 0;
            top: 0;
            width: 100%;
            /* Full width */
            height: 100%;
            /* Full height */
            overflow: auto;
            /* Enable scroll if needed */
            background-color: rgba(0, 0, 0, 0.5);
            /* Black w/ opacity */
        }

        /* Modal Content */
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            /* 5% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            /* Could be more or less, depending on screen size */
            border-radius: 8px;
            position: relative;
        }

        /* Close Button */
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .form-actions button[type="button"] {
            background-color: #dc3545;
            color: white;
        }

        .form-actions button[type="submit"] {
            background-color: #28a745;
            color: white;
        }

        .form-actions button:hover {
            opacity: 0.9;
        }

        /* Image Preview */
        #editImagePreview {
            display: block;
        }
    </style>

    <script>
        // Function to open the edit book modal and populate it with book data
        function openEditBookModal(button) {
            var modal = document.getElementById('editBookModal');

            // Get data attributes from the button
            var bookId = button.getAttribute('data-bookid');
            var bookDewey = button.getAttribute('data-bookdewey');
            var bookIsbn = button.getAttribute('data-bookisbn');
            var bookTitle = button.getAttribute('data-booktitle');
            var bookAuthor = button.getAttribute('data-bookauthor');
            var bookPublisher = button.getAttribute('data-bookpublisher');
            var bookQuantity = button.getAttribute('data-bookquantity');
            var bookPages = button.getAttribute('data-bookpages');
            var bookCategory = button.getAttribute('data-bookcategory');
            var bookGenre = button.getAttribute('data-bookgenre');
            var bookDescription = button.getAttribute('data-bookdescription');
            var bookImg = button.getAttribute('data-bookimg');
            var bookIndexes = button.getAttribute('data-bookindex'); // Added to get bookIndexes

            // Populate the form fields
            document.getElementById('editBookID').value = bookId;
            document.getElementById('editBookDewey').value = bookDewey;
            document.getElementById('editBookIsbn').value = bookIsbn;
            document.getElementById('editBookTitle').value = bookTitle;
            document.getElementById('editBookAuthor').value = bookAuthor;
            document.getElementById('editBookPublisher').value = bookPublisher;
            document.getElementById('editBookQuantity').value = bookQuantity;
            document.getElementById('editBookPages').value = bookPages;
            document.getElementById('editBookCategory').value = bookCategory; // Ensure this is set correctly
            document.getElementById('editBookGenre').value = bookGenre;
            document.getElementById('editBookDescription').value = bookDescription;
            document.getElementById('editBookIndex').value = bookIndexes; // Populate bookIndexes

            // Set image preview if an image exists
            if (bookImg) {
                var imagePreview = document.getElementById('editImagePreview');
                imagePreview.src = "../" + bookImg;
                imagePreview.style.display = 'block';
            } else {
                document.getElementById('editImagePreview').style.display = 'none';
            }

            // Display the modal
            modal.style.display = 'block';
        }

        // Function to close the edit book modal
        function closeEditBookModal() {
            var modal = document.getElementById('editBookModal');
            modal.style.display = 'none';
        }

        // Function to preview the selected book image
        function previewEditBookImage() {
            var fileInput = document.getElementById('editBookImage');
            var imagePreview = document.getElementById('editImagePreview');

            if (fileInput.files && fileInput.files[0]) {
                var reader = new FileReader();

                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }

                reader.readAsDataURL(fileInput.files[0]);
            } else {
                imagePreview.src = "";
                imagePreview.style.display = 'none';
            }
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            var bookModal = document.getElementById('editBookModal');
            var studentModal = document.getElementById('editStudentModal');
            if (event.target == bookModal) {
                bookModal.style.display = 'none';
            }
            if (event.target == studentModal) {
                studentModal.style.display = 'none';
            }
        }

        // Attach event listeners to all edit book buttons
        document.addEventListener('DOMContentLoaded', function() {
            var editBookButtons = document.querySelectorAll('button[name="edit-book"]');
            editBookButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    openEditBookModal(this);
                });
            });
        });
    </script>

    <?php
    // Get admin information from database based on logged in username
    $username = $_SESSION['username']; // Assuming the username is stored in the session
    $queryAdminInfo = "
        SELECT 
            tbadmininfo.adminFname, 
            tbadmininfo.adminMname, 
            tbadmininfo.adminLname, 
            tbadmininfo.adminEmail, 
            tbadmininfo.adminContact, 
            users.password 
        FROM 
            tbadmininfo 
        JOIN 
            users ON tbadmininfo.username = users.username 
        WHERE 
            tbadmininfo.username = ?";
    $stmt = $conn->prepare($queryAdminInfo);
    $stmt->bind_param("s", $username); // Bind the username
    $stmt->execute();
    $resultAdminInfo = $stmt->get_result();

    if ($resultAdminInfo->num_rows > 0) {
        $adminInfo = $resultAdminInfo->fetch_assoc();
        $adminFName = htmlspecialchars($adminInfo['adminFname']);
        $adminMName = htmlspecialchars($adminInfo['adminMname']);
        $adminLName = htmlspecialchars($adminInfo['adminLname']);
        $adminContacts = htmlspecialchars($adminInfo['adminContact']);
        $adminEmail = htmlspecialchars($adminInfo['adminEmail']);
        $password = htmlspecialchars($adminInfo['password']); // Fetch the password
    } else {
        // Default values if no admin info is found
        $adminFName = "Unknown";
        $adminMName = "Unknown";
        $adminLName = "Unknown";
        $adminContacts = "Unknown";
        $adminEmail = "Unknown";
        $password = "Not found"; // Handle case where password is not found
    }
    ?>

    <div class="text" id="profile" style="display:none;">
        <h2 class="main-text"> PROFILE </h2>

        <div class="admin-info">

        </div>

        <div class="profile-modal-content">

            <h2 class="main-text">Profile Settings</h2>

            <div class="profile-info">

                <img src="../icons-png/user.png" alt="Profile Picture" class="profile-picture">

                <div class="profile-details">
                    <div class="Fullname">
                        <p><strong>Name:</strong></p>
                        <div class="name-inputs">
                            <input type="text" name="adminFname" id="displayFName" value="<?php echo $adminFName; ?>" disabled>
                            <input type="text" name="adminMname" id="displayMName" value="<?php echo $adminMName; ?>" disabled>
                            <input type="text" name="adminLname" id="displayLName" value="<?php echo $adminLName; ?>" disabled>
                        </div>
                    </div>

                    <p><strong>Contact Number:</strong> <input type="number" name="adminContact" id="displayContact" value="<?php echo $adminContacts; ?>" disabled></p>
                    <p><strong>Email:</strong> <input type="email" name="adminEmail" id="displayEmail" value="<?php echo $adminEmail; ?>" disabled></p>
                    <p><strong>Password:</strong> <input type="text" name="password" id="displayPassword" value="<?php echo $password; ?>" disabled></p>
                </div>

                <div class="profile-actions">
                    <form action="../save_adminprofile.php" method="POST">
                        <input type="hidden" name="username" value="<?php echo $_SESSION['username']; ?>"> <!-- Assuming adminId is stored in session -->
                        <button type="button" name="editButton" onclick="toggleEdit()">Edit Profile</button>
                        <button type="button" name="cancelButton" style="background-color: red; color: white;" hidden onclick="toggleEdit()">Cancel</button>
                        <button type="submit" name="saveButton" style="background-color: green; color: white;" hidden>Save</button>
                    </form>

                    <script>
                        function toggleEdit() {
                            const inputs = document.querySelectorAll('.profile-details input');
                            const editButton = document.querySelector('button[name="editButton"]');
                            const cancelButton = document.querySelector('button[name="cancelButton"]');
                            const saveButton = document.querySelector('button[name="saveButton"]');
                            const passwordInput = document.getElementById('displayPassword');

                            inputs.forEach(input => {
                                input.disabled = !input.disabled; // Toggle disabled state
                            });

                            // Toggle visibility of password input
                            if (passwordInput.disabled) {
                                passwordInput.type = 'text'; // Show password
                            } else {
                                passwordInput.type = 'text'; // Hide password
                            }

                            // Toggle visibility of buttons
                            editButton.hidden = !editButton.hidden;
                            cancelButton.hidden = !cancelButton.hidden;
                            saveButton.hidden = !saveButton.hidden;
                        }
                    </script>

                </div>

            </div>

        </div>

    </div>

    <script>
        document.getElementById('editButton').addEventListener('click', function() {
            // Enable all input fields
            const inputs = document.querySelectorAll('.profile-info input');
            inputs.forEach(input => {
                input.disabled = false;
            });

            // Show confirm and cancel buttons
            document.querySelector('button[name="confirmButton"]').style.display = 'inline-block';
            document.getElementById('cancelButton').style.display = 'inline-block';
            this.style.display = 'none'; // Hide the edit button
        });

        document.getElementById('cancelButton').addEventListener('click', function() {
            // Disable all input fields
            const inputs = document.querySelectorAll('.profile-info input');
            inputs.forEach(input => {
                input.disabled = true;
            });

            // Hide confirm and cancel buttons
            document.querySelector('button[name="confirmButton"]').style.display = 'none';
            this.style.display = 'none'; // Hide the cancel button
            document.getElementById('editButton').style.display = 'inline-block'; // Show the edit button again
        });
    </script>

</body>

</html>