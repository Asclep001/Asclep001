<?php
session_start(); // Add this line at the top of your file
require('../includes/dbconn.php'); // Ensure you have the database connection'


$bookResult = null; // Initialize $bookResult to avoid undefined variable warning
$suggestions = []; // Initialize suggestions array
$keywords = isset($_POST['keywords']) ? trim($_POST['keywords']) : ''; // Initialize $keywords

// Fetch all books by default
$query = "SELECT * FROM tbbook ORDER BY bookdewey ASC";
$bookResult = mysqli_query($conn, $query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keywords = isset($_POST['keywords']) ? trim($_POST['keywords']) : ''; // Check if 'keywords' is set

    // Adjust the query based on the search criteria
    if (!empty($keywords)) {
        $stmt = $conn->prepare("SELECT * FROM tbbook WHERE 
            bookTitle LIKE ? OR 
            bookAuthor LIKE ? OR 
            bookIsbn LIKE ? OR 
            bookCategory LIKE ? OR 
            bookGenre LIKE ? OR 
            bookIndexes LIKE ? OR
            bookPublisher LIKE ?"); // Ensure all relevant columns are included
        $likeKeywords = "%" . $keywords . "%"; // Prepare the LIKE pattern
        $stmt->bind_param("sssssss", $likeKeywords, $likeKeywords, $likeKeywords, $likeKeywords, $likeKeywords, $likeKeywords, $likeKeywords);
        $stmt->execute();
        $bookResult = $stmt->get_result();
    } else {
        $query = "SELECT * FROM tbbook ORDER BY bookdewey ASC"; // Show all books
        $bookResult = mysqli_query($conn, $query);
    }

    if (!$bookResult) {
        // Handle query error
        die("Database query failed: " . mysqli_error($conn));
    }
}



// Fetch student information based on the logged-in student's ID
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    // Fetch password from the 'user' table
    $query = "SELECT password FROM users WHERE username = ?"; // Fetch password from user table
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Check if user data is retrieved
    if ($user) {
        // Fetch the hashed password
        $hashedPassword = $user['password']; // Fetch the hashed password from user table

        // Now fetch student information from the 'studinfo' table
        $query = "SELECT * FROM stdinfo WHERE studID = ?"; // Adjust this query as needed
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc(); // Fetch student data

        // Check if student data is retrieved
        if (!$student) {
            echo "No student found with the given username."; // Debugging line
        }
    } else {
        echo "No user found with the given username."; // Debugging line
    }
}

// Add this part to handle password change
if (isset($_POST['newPassword'])) {
    $newPassword = $_POST['newPassword']; // Get the new password from the input
    $username = $_SESSION['username']; // Get the logged-in user's username

    // **DO NOT HASH THE PASSWORD**
    // Store the plain text password directly
    $updateQuery = "UPDATE users SET password = ? WHERE username = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ss", $newPassword, $username); // Bind the plain text password

    if ($stmt->execute()) {
        echo "<script>alert('Password changed successfully!');</script>";
    } else {
        echo "<script>alert('Error changing password: " . htmlspecialchars($stmt->error) . "');</script>";
    }

    $stmt->close();
}



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search</title>
    <link rel="stylesheet" href="../css/searchingpage.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .modal {
            display: none;
            /* Hidden by default */
            position: fixed;
            /* Stay in place */
            z-index: 1000;
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

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            /* Could be more or less, depending on screen size */
            max-width: 600px;
            /* Maximum width */
            border-radius: 5px;
            /* Rounded corners */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            /* Shadow effect */
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
    </style>
</head>

<body>

    <header>
        <nav class="navbar">
            <div class="logo">LIBRARY MANAGEMENT SYSTEM</div>

            <?php if (isset($_SESSION['status']) && $_SESSION['status'] === 'valid') { ?>

                <div class="nav-links">
                    <a href="" class="nav-link" data-section="home">Search Book</a>
                    <a href="" class="nav-link" data-section="issued-books">View Issued Book </a>
                    <a href="" class="nav-link" data-section="return-history">View Return History</a>
                </div>

                <div class="nav-buttons" style="display: flex; align-items: center;">

                    <form action="" method="POST" style="margin-right: 10px;">
                        <button type="button" class="profile-button" style="border-radius: 50%; width: 50px; height: 50px; padding: 0; border: none; background: none; vertical-align: middle;" id="profileButton">
                            <?php
                            if (empty($student['studImg'])) {
                                $studImage = ($student['studGender'] === 'Male') ? 'studimg/defaultman.png' : 'studimg/defaultwoman.png';
                            } else {
                                $studImage = $student['studImg'];
                            }
                            ?>
                            <img src="../<?php echo htmlspecialchars($studImage); ?>" alt="Profile" width="100%" height="100%" style="border-radius: 50%; border: 2px solid #007bff;">
                        </button>
                    </form>

                    <form action="../logout.php" method="POST">
                        <button type="submit" class="logout-button" style="padding: 10px 15px; border-radius: 5px; background-color: #dc3545; color: white; border: none; cursor: pointer;">
                            Logout
                        </button>
                    </form>
                </div>
                <script>
                    document.getElementById('profileButton').addEventListener('click', function() {
                        const studentInfo = document.getElementById('studentInfo');
                        studentInfo.style.display = studentInfo.style.display === 'none' ? 'block' : 'none'; // Toggle visibility
                    });
                </script>
            <?php } else { ?>
                <form action="../online-login.php" method="POST">
                    <button type="submit" class="login-button" style="padding: 10px 15px; border-radius: 5px; background-color: #ffff; color: black; border: none; cursor: pointer;">
                        Login
                    </button>
                </form>
            <?php } ?>
        </nav>
    </header>

    <?php if (isset($_SESSION['username'])) { // Check if user is logged in 
    ?>
        <div class="student-info-container" id="studentInfoContainer">
            <div class="student-info" id="studentInfo" style="display: block;">
                <h3>Welcome, <?php echo htmlspecialchars($student['studFname']) . ' ' . htmlspecialchars($student['studLname']); ?></h3>

                <div class="student-details">
                    <div class="detail-item">
                        <label>ID:</label>
                        <input type="text" value="<?php echo htmlspecialchars($student['studID']); ?>" disabled>
                    </div>
                    <div class="detail-item">
                        <label>Gender:</label>
                        <input type="text" value="<?php echo htmlspecialchars($student['studGender']); ?>" disabled>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <input type="text" value="<?php echo htmlspecialchars($student['studEmail']); ?>" disabled>
                    </div>
                    <div class="detail-item">
                        <label>Contact:</label>
                        <input type="text" value="<?php echo htmlspecialchars($student['studContact']); ?>" disabled>
                    </div>
                    <div class="detail-item">
                        <label>Course:</label>
                        <input type="text" value="<?php echo htmlspecialchars($student['course_name']); ?>" disabled>
                    </div>
                    <div class="detail-item">
                        <label>Address:</label>
                        <input type="text" value="<?php echo htmlspecialchars($student['studAddress']); ?>" disabled>
                    </div>
                </div>

                <div class="password-container">
                    <label for="passwordInput" style="font-weight: bold; font-size: 1.2em;">Password:</label>
                    <input type="password" id="passwordInput" value="<?php echo htmlspecialchars($hashedPassword); ?>" disabled style="width: 50%; padding: 10px; margin-top: 10px; border: 1px solid #ccc; border-radius: 4px;">
                    <a href="#" id="changePasswordLink" style="color: #007bff; text-decoration: underline; cursor: pointer; margin-top: 10px; display: inline-block;">Change Password</a>
                    <div id="passwordActions" style="display: none; margin-top: 10px;">
                        <button id="confirmButton" style="padding: 10px 15px; border-radius: 5px; background-color: #28a745; color: white; border: none; cursor: pointer;">Confirm</button>
                        <button id="cancelButton" style="padding: 10px 15px; border-radius: 5px; background-color: #dc3545; color: white; border: none; cursor: pointer;">Cancel</button>
                    </div>
                </div>

            </div>
        </div>
    <?php }  ?>



    <section class="book-grid">
        <?php if (isset($_SESSION['username'])): ?>
            <section class="recommended-books">
                <h2>Recommended Books</h2>
                <div class="book-cards">
                    <?php
                    // Fetch the user's most returned books
                    $username = $_SESSION['username']; // Get the logged-in user's username
                    $recommendedBooks = []; // Initialize an array for recommended books

                    // Query to get the most returned books by the user
                    $returnedQuery = "SELECT b.bookTitle, b.bookAuthor, COUNT(rr.book_id) as return_count 
                                  FROM return_record rr 
                                  JOIN tbbook b ON rr.book_id = b.bookdewey 
                                  WHERE rr.student_id = ? 
                                  GROUP BY rr.book_id 
                                  ORDER BY return_count DESC 
                                  LIMIT 3"; // Limit to top 3 returned books
                    $stmt = $conn->prepare($returnedQuery);
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($book = $result->fetch_assoc()) {
                        $recommendedBooks[] = [
                            'title' => $book['bookTitle'],
                            'author' => $book['bookAuthor'],
                            'image' => isset($book['image_column_name']) ? $book['image_column_name'] : '../bookImg/default.jpg'
                        ];
                    }

                    // Display recommended books
                    foreach ($recommendedBooks as $book) {
                        echo '<div class="book-card">';
                        echo '<h3>' . htmlspecialchars($book['title']) . '</h3>';
                        echo '<img src="' . htmlspecialchars($book['image']) . '" alt="' . htmlspecialchars($book['title']) . '">';
                        echo '<p>By: ' . htmlspecialchars($book['author']) . '</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </section>
        <?php endif; ?>

        <div class="search-container" id="searchContainer">
            <form method="POST" action="">
                <div class="search-box">
                    <div class="search-input-wrapper">
                        <input type="text" name="keywords" placeholder="Enter Keywords?" autocomplete="off" value="<?php echo htmlspecialchars($keywords); ?>" id="searchInput">
                    </div>
                    <button type="submit" class="search-button" name="submit">
                        <i class='bx bx-search search-icon'></i>
                    </button>
                </div>
                <div class="suggestions" style="margin-top: 20px;">
                    <ul id="suggestionsList"></ul>
                </div>
            </form>
        </div>

        <div class="book-cards">
            <?php if ($bookResult) {
        ?>
                <?php while ($book = mysqli_fetch_assoc($bookResult)) { ?>
                    <a  class="book-card" style="cursor: pointer; text-decoration: none;" title="View details of <?php echo htmlspecialchars($book['bookTitle']); ?>"
                        onclick="showBookDetails('<?php echo htmlspecialchars($book['bookTitle']); ?>',
                     '<?php echo htmlspecialchars($book['bookAuthor']); ?>',
                     '<?php echo htmlspecialchars($book['bookPublisher']); ?>', 
                     '<?php echo htmlspecialchars($book['bookCategory']); ?>', 
                     '<?php echo htmlspecialchars($book['bookIsbn']); ?>',
                     '<?php echo htmlspecialchars($book['book_img']); ?>', 
                     '<?php echo htmlspecialchars($book['bookPages']); ?>', 
                     '<?php echo htmlspecialchars($book['bookIndexes']); ?>', 
                     '<?php echo htmlspecialchars($book['bookQuantity']); ?>', 
                     '<?php echo htmlspecialchars($book['booktotalQuantity']); ?>',
                     '<?php echo htmlspecialchars($book['bookGenre']); ?>',
                     '<?php echo htmlspecialchars($book['bookDescription']); ?>',
                     '<?php echo htmlspecialchars($book['bookdewey']); ?>')">


                        <h2><?php echo htmlspecialchars($book['bookdewey']); ?></h2>
                        <img src="<?php echo !empty($book['book_img']) ? '../' . htmlspecialchars($book['book_img']) : '../bookImg/default.jpg'; ?>" alt="<?php echo htmlspecialchars($book['bookTitle']); ?>">
                        <h3><?php echo htmlspecialchars($book['bookTitle']); ?></h3>
                        <p>By: <?php echo htmlspecialchars($book['bookAuthor']); ?></p>
                        <p>Publisher: <?php echo htmlspecialchars($book['bookPublisher']); ?></p>
                        <p style="display: none;">ISBN: <?php echo htmlspecialchars($book['bookIsbn']); ?></p>
                        <p>Category: <?php echo htmlspecialchars($book['bookCategory']) ?></p>
                        <p style="display: none;">Pages: <?php echo htmlspecialchars($book['bookPages']) ?></p>
                        <p>Genre: <?php echo htmlspecialchars($book['bookGenre']) ?></p>
                        <p style="display: none;">Description: <?php echo htmlspecialchars($book['bookDescription']) ?></p>
                        <p style="display: none;">Indexes: <?php echo htmlspecialchars($book['bookIndexes']) ?></p>
                        <p class="availability">
                            <?php echo htmlspecialchars($book['bookQuantity']) . ' out of ' . htmlspecialchars($book['booktotalQuantity']); ?>
                        </p>
                    </a>

                    <!-- Modal for Book Details -->
                    <div id="bookDetailsModal" class="modal" style="display:none;">
                        <div class="modal-content">
                            <span class="close" onclick="closeModal()">&times;</span>
                            <h2 id="modalBookTitle" style="text-align: center;"></h2>
                            <img id="modalBookImage" src="../<?php echo '../bookImg/default.jpg'; ?>" alt="Book Image" style="width: 100%; height: auto; border-radius: 5px; margin-bottom: 15px;">
                            <div class="book-details">
                                <p><strong>Dewey:</strong> <span id="modalBookDewey"></span></p>
                                <p><strong>Author:</strong> <span id="modalBookAuthor"></span></p>
                                <p><strong>Publisher:</strong> <span id="modalBookPublisher"></span></p>
                                <p><strong>Category:</strong> <span id="modalBookCategory"></span></p>
                                <p><strong>Available:</strong> <span id="modalBookAvailability"></span></p>
                                <p><strong>ISBN:</strong> <span id="modalBookIsbn"></span></p>
                                <p><strong>Genre:</strong> <span id="modalBookGenre"></span></p>
                                <p><strong>Pages:</strong> <span id="modalBookPages"></span></p>
                                <p><strong>Description:</strong> <span id="modalBookDescription"></span></p>
                                <p><strong>Indexes:</strong> <span id="modalBookIndexes"></span></p>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p>No books found.</p>
            <?php } ?>
        </div>
    </section>

    <!-- Similar Books Section -->
    <section class="similar-books">
        <h2>Similar Books You Can Find</h2>
        <div id="similarBooksContainer">
            <!-- Similar books will be displayed here -->
        </div>
    </section>

    <!-- ISSUED BOOK -->

    <section class="issued-books" id="issuedBooksSection" style="display: none;">
        <h2 style="text-align: center; margin-top: 20px;">Issued Book Records</h2>
        <div class="table-container">
            <table class="issued-books-table">
                <thead>
                    <tr>
                        <th>Book Title</th>
                        <th>Issued Date</th>
                        <th>Return Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch issued books for the logged-in user
                    if (isset($_SESSION['username'])) {
                        $username = $_SESSION['username'];
                        // Fetch the student ID from the stdinfo table using the users table
                        $studentQuery = "SELECT si.studID FROM stdinfo si 
                                         JOIN users u ON si.studID = u.username 
                                         WHERE u.username = ?";
                        $stmt = $conn->prepare($studentQuery);
                        $stmt->bind_param("s", $username);
                        $stmt->execute();
                        $studentResult = $stmt->get_result();
                        $student = $studentResult->fetch_assoc();

                        if ($student) {
                            $studentId = $student['studID'];
                            // Adjusted query to join borrow_records with tbbook
                            $issuedQuery = "SELECT b.bookTitle, br.borrow_date, br.expectedreturn_date 
                                            FROM borrow_records br 
                                            JOIN tbbook b ON br.book_id = b.bookdewey 
                                            WHERE br.student_id = ?";
                            $stmt = $conn->prepare($issuedQuery);
                            $stmt->bind_param("s", $studentId);
                            $stmt->execute();
                            $issuedResult = $stmt->get_result();

                            if ($issuedResult->num_rows > 0) {
                                while ($issuedBook = $issuedResult->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($issuedBook['bookTitle']) . '</td>';
                                    echo '<td>' . date("F j, Y", strtotime(htmlspecialchars($issuedBook['borrow_date']))) . '</td>'; // Format borrow date
                                    echo '<td>' . date("F j, Y", strtotime(htmlspecialchars($issuedBook['expectedreturn_date']))) . '</td>'; // Format expected return date
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="3">No issued books found.</td></tr>';
                            }
                        } else {
                            echo '<tr><td colspan="3">No student found with the given username.</td></tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- RETURN RECORDS -->

    <section class="return-history" id="returnHistorySection" style="display: none;">
        <h2 style="text-align: center; margin-top: 20px;">Return History</h2>
        <div class="table-container">
            <table class="return-history-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                        <th style="padding: 10px; border: 1px solid #ddd; background-color: #007bff; color: white; text-align: center;">Book Title</th>
                        <th style="padding: 10px; border: 1px solid #ddd; background-color: #007bff; color: white; text-align: center;">Return Date</th>
                        <th style="padding: 10px; border: 1px solid #ddd; background-color: #007bff; color: white; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch return history for the logged-in user
                    if (isset($_SESSION['username'])) {
                        $username = $_SESSION['username'];
                        // Fetch the student ID from the stdinfo table using the users table
                        $studentQuery = "SELECT si.studID FROM stdinfo si 
                                         JOIN users u ON si.studID = u.username 
                                         WHERE u.username = ?";
                        $stmt = $conn->prepare($studentQuery);
                        $stmt->bind_param("s", $username);
                        $stmt->execute();
                        $studentResult = $stmt->get_result();
                        $student = $studentResult->fetch_assoc();

                        if ($student) {
                            $studentId = $student['studID'];
                            // Adjusted query to fetch return history
                            $returnQuery = "SELECT b.bookTitle, r.return_date, r.status 
                                            FROM return_record r 
                                            JOIN tbbook b ON r.book_id = b.bookdewey 
                                            WHERE r.student_id = ?";
                            $stmt = $conn->prepare($returnQuery);
                            $stmt->bind_param("s", $studentId);
                            $stmt->execute();
                            $returnResult = $stmt->get_result();

                            if ($returnResult->num_rows > 0) {
                                while ($returnRecord = $returnResult->fetch_assoc()) {
                                    echo '<tr style="border: 1px solid #ddd; transition: background-color 0.3s;" onmouseover="this.style.backgroundColor=\'#f9f9f9\'" onmouseout="this.style.backgroundColor=\'\'">';
                                    echo '<td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($returnRecord['bookTitle']) . '</td>';
                                    echo '<td style="padding: 10px; border: 1px solid #ddd;">' . date("F j, Y", strtotime($returnRecord['return_date'])) . '</td>';
                                    echo '<td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($returnRecord['status']) . '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="3" style="padding: 10px;">No return history found.</td></tr>';
                            }
                        } else {
                            echo '<tr><td colspan="3" style="padding: 10px; text-align: center;">No student found with the given username.</td></tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const keywordsInput = document.querySelector('input[name="keywords"]');
            const suggestionsContainer = document.querySelector('.suggestions ul');

            keywordsInput.addEventListener('input', function() {
                const keywords = this.value;

                // Only fetch suggestions if there are keywords
                if (keywords.trim() === '') {
                    suggestionsContainer.innerHTML = ''; // Clear suggestions if input is empty
                    return; // Exit the function
                }

                // Fetch suggestions
                fetch('suggestion.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'keywords=' + encodeURIComponent(keywords)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Suggestions:', data); // Debugging line
                        suggestionsContainer.innerHTML = ''; // Clear previous suggestions
                        if (data.length > 0) {
                            data.forEach(suggestion => {
                                const li = document.createElement('li');
                                li.textContent = `${suggestion.title} by ${suggestion.author}`; // Display title and author
                                li.style.cursor = 'pointer'; // Change cursor to pointer
                                li.addEventListener('click', function(event) {
                                    event.preventDefault(); // Prevent default form submission
                                    keywordsInput.value = suggestion.title; // Set input value to suggestion
                                    this.closest('form').submit(); // Submit the form
                                });
                                suggestionsContainer.appendChild(li);
                            });
                        } else {
                            suggestionsContainer.innerHTML = '<li>No suggestions found.</li>'; // Handle no suggestions
                        }
                    })
                    .catch(error => console.error('Error fetching suggestions:', error));
            });
        });

        document.getElementById('changePasswordLink').addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default link behavior
            const passwordInput = document.getElementById('passwordInput');
            const passwordActions = document.getElementById('passwordActions');
            passwordInput.disabled = false; // Enable the password input
            passwordInput.type = 'text'; // Show the password
            passwordActions.style.display = 'block'; // Show confirm and cancel buttons
        });

        document.getElementById('cancelButton').addEventListener('click', function() {
            const passwordInput = document.getElementById('passwordInput');
            const passwordActions = document.getElementById('passwordActions');
            passwordInput.disabled = true; // Disable the password input
            passwordInput.type = 'password'; // Hide the password
            passwordActions.style.display = 'none'; // Hide confirm and cancel buttons
        });

        document.getElementById('confirmButton').addEventListener('click', function() {
            const newPassword = document.getElementById('passwordInput').value; // Get the new password
            if (newPassword.length < 8) {
                alert('Password must be at least 8 characters long.'); // Notify if password is too short
                return; // Exit the function
            }
            if (newPassword) {
                // Create a form to submit the new password
                const form = new FormData();
                form.append('newPassword', newPassword);

                fetch('../user/searchpage.php', { // Ensure this points to the correct file
                        method: 'POST',
                        body: form
                    })
                    .then(response => {
                        if (response.ok) {
                            alert('Password changed successfully!'); // Notify success
                            // Close the password actions
                            const passwordInput = document.getElementById('passwordInput');
                            const passwordActions = document.getElementById('passwordActions');
                            passwordInput.disabled = true; // Disable the password input
                            passwordInput.type = 'password'; // Hide the password
                            passwordActions.style.display = 'none'; // Hide confirm and cancel buttons
                        } else {
                            alert('Error changing password. Status: ' + response.status); // Notify error with status
                        }
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                alert('Please enter a new password.'); // Notify if no password entered
            }
        });

        document.querySelector('.nav-link[data-section="issued-books"]').addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default link behavior
            const issuedBooksSection = document.getElementById('issuedBooksSection');
            const bookGridSection = document.querySelector('.book-grid'); // Select the book-grid section
            const returnHistorySection = document.getElementById('returnHistorySection'); // Select the return history section

            // Hide other sections
            bookGridSection.style.display = 'none'; // Hide book grid
            returnHistorySection.style.display = 'none'; // Hide return history
            issuedBooksSection.style.display = 'block'; // Show issued books
        });

        document.querySelector('.nav-link[data-section="return-history"]').addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default link behavior
            const returnHistorySection = document.getElementById('returnHistorySection');
            const bookGridSection = document.querySelector('.book-grid'); // Select the book-grid section
            const issuedBooksSection = document.getElementById('issuedBooksSection'); // Select the issued books section

            // Hide all sections
            bookGridSection.style.display = 'none'; // Hide book grid
            issuedBooksSection.style.display = 'none'; // Hide issued books
            returnHistorySection.style.display = 'block'; // Show return history
        });

        function showBookDetails(title, author, publisher, category, isbn, bookimage, pages, indexes, quantity, totalQuantity, genre, description, dewey) {
            const keywordsInput = document.querySelector('input[name="keywords"]');
            const searchTerm = keywordsInput.value; // Get the current search term

            // Highlight the search term in the modal
            const highlightedTitle = title.replace(new RegExp(`(${searchTerm})`, 'gi'), '<span style="background-color: yellow;">$1</span>');
            const highlightedAuthor = author.replace(new RegExp(`(${searchTerm})`, 'gi'), '<span style="background-color: yellow;">$1</span>');
            const highlightedPublisher = publisher.replace(new RegExp(`(${searchTerm})`, 'gi'), '<span style="background-color: yellow;">$1</span>');
            const highlightedCategory = category.replace(new RegExp(`(${searchTerm})`, 'gi'), '<span style="background-color: yellow;">$1</span>');
            const highlightedGenre = genre.replace(new RegExp(`(${searchTerm})`, 'gi'), '<span style="background-color: yellow;">$1</span>');
            const highlightedDescription = description.replace(new RegExp(`(${searchTerm})`, 'gi'), '<span style="background-color: yellow;">$1</span>');
            const highlightedIndexes = indexes.replace(new RegExp(`(${searchTerm})`, 'gi'), '<span style="background-color: yellow;">$1</span>');

            document.getElementById('modalBookTitle').innerHTML = highlightedTitle; // Use innerHTML to allow HTML tags
            document.getElementById('modalBookAuthor').innerHTML = highlightedAuthor;
            document.getElementById('modalBookPublisher').innerHTML = highlightedPublisher;
            document.getElementById('modalBookCategory').innerHTML = highlightedCategory;
            document.getElementById('modalBookGenre').innerHTML = highlightedGenre;
            document.getElementById('modalBookDescription').innerHTML = highlightedDescription;
            document.getElementById('modalBookIndexes').innerHTML = highlightedIndexes;
            document.getElementById('modalBookIsbn').innerText = isbn;
            document.getElementById('modalBookImage').src = bookimage;
            document.getElementById('modalBookDewey').innerText = dewey;
            document.getElementById('modalBookPages').innerText = pages;

            // Check if quantity or totalQuantity is missing and display a message if so
            if (!quantity || !totalQuantity) {
                document.getElementById('modalBookAvailability').innerText = 'Information not available';
            } else {
                document.getElementById('modalBookAvailability').innerText = quantity + ' out of ' + totalQuantity;
            }

            document.getElementById('bookDetailsModal').style.display = 'block'; // Show the modal
        }

        function closeModal() {
            document.getElementById('bookDetailsModal').style.display = 'none'; // Hide the modal
        }

        
    </script>

    <script src="js/geminiapi.js"></script>
</body>

</html>