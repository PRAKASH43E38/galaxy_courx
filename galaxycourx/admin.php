<?php
// Database connection details
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'course_registration';

// Create connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    // If connection fails, respond with JSON for AJAX, or die for direct access
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
        exit();
    } else {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Initialize variables for messages and edit data
$message = '';
$message_type = ''; // 'success' or 'error'
$edit_student_data = null;

// --- Handle DELETE Request ---
if (isset($_GET['action']) && $_GET['action'] == 'delete_student' && isset($_GET['id'])) {
    $student_id = intval($_GET['id']); // Ensure ID is an integer

    $sql = "DELETE FROM registrations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);

    if ($stmt->execute()) {
        $message = "Student record deleted successfully!";
        $message_type = 'success';
    } else {
        $message = "Error deleting record: " . htmlspecialchars($stmt->error);
        $message_type = 'error';
    }
    $stmt->close();
    // Redirect to clean URL after action
    header("Location: admin.php?msg=" . urlencode($message) . "&type=" . $message_type);
    exit();
}

// --- Handle UPDATE Request ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_student') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $full_name = htmlspecialchars($_POST['fullName'] ?? '');
    $age = htmlspecialchars($_POST['age'] ?? '');
    $gender = htmlspecialchars($_POST['gender'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $phone = htmlspecialchars($_POST['phoneNumber'] ?? '');
    $course = htmlspecialchars($_POST['courseType'] ?? '');

    if ($student_id > 0) {
        $sql = "UPDATE registrations SET full_name=?, age=?, gender=?, email=?, phone_number=?, course_type=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissssi", $full_name, $age, $gender, $email, $phone, $course, $student_id);

        if ($stmt->execute()) {
            $message = "Student record updated successfully!";
            $message_type = 'success';
        } else {
            $message = "Error updating record: " . htmlspecialchars($stmt->error);
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message = "Invalid student ID for update.";
        $message_type = 'error';
    }
    // Redirect to clean URL after action
    header("Location: admin.php?msg=" . urlencode($message) . "&type=" . $message_type);
    exit();
}

// --- Handle INSERT (Registration) Request via AJAX or regular POST ---
// Check if it's a POST request AND it's not an update action
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    // This is a registration submission
    $full_name = htmlspecialchars($_POST['fullName'] ?? '');
    $age = htmlspecialchars($_POST['age'] ?? '');
    $gender = htmlspecialchars($_POST['gender'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $phone = htmlspecialchars($_POST['phoneNumber'] ?? '');
    $course = htmlspecialchars($_POST['courseType'] ?? '');

    $sql = "INSERT INTO registrations (full_name, age, gender, email, phone_number, course_type)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sissss", $full_name, $age, $gender, $email, $phone, $course);

    // Set header for JSON response (important for AJAX)
    header('Content-Type: application/json');

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Registration successful!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error during registration: ' . $stmt->error]);
    }
    $stmt->close();
    exit(); // Exit after sending JSON response for AJAX requests
}

// --- Handle Displaying Edit Form Data ---
if (isset($_GET['action']) && $_GET['action'] == 'edit_student_form' && isset($_GET['id'])) {
    $student_id = intval($_GET['id']);
    $sql = "SELECT id, full_name, age, gender, email, phone_number, course_type FROM registrations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_student_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- Get Message from URL Parameters ---
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['msg']);
    $message_type = htmlspecialchars($_GET['type']);
}

// --- Count Total Registered Students ---
$count_sql = "SELECT COUNT(*) AS total_students FROM registrations";
$count_result = $conn->query($count_sql);
$total_students = 0;
if ($count_result && $count_result->num_rows > 0) {
    $count_row = $count_result->fetch_assoc();
    $total_students = $count_row['total_students'];
}

// --- Fetch All Registrations for Display ---
$sql = "SELECT id, full_name, age, gender, email, phone_number, course_type FROM registrations ORDER BY full_name ASC";
$result = $conn->query($sql);

// Close the database connection after all operations are complete
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        /* General body styling for the dashboard page */
        body {
            background: url("jdgh.jpg") center/cover no-repeat;
            color: #fff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            margin: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Container for the entire dashboard content */
        .dashboard-container {
            width: 90%;
            max-width: 1200px;
            background-color: rgba(0, 0, 0, 0.6);
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        /* Styling for the main heading "STUDENTS LIST" */
        h2 {
            font-size: 28px;
            color: #00e676;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        /* Styling for the table that displays student data */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            text-align: left;
            background-color: rgba(0, 0, 0, 0.4);
            border-radius: 8px;
            overflow: hidden;
        }

        /* Styling for table header cells (NAME, AGE, GENDER, etc.) */
        th {
            background-color: transparent;
            padding: 15px;
            font-weight: bold;
            color: #00e676;
            border-bottom: 2px solid #00e676;
            text-transform: uppercase;
            font-size: 15px;
            letter-spacing: 0.8px;
        }

        /* Styling for table data cells */
        td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #ccc;
            font-size: 14px;
        }

        /* Hover effect for table rows */
        tr:hover {
            background-color: rgba(255, 255, 255, 0.08);
            transition: background-color 0.3s ease;
        }

        /* Container for the "Back to Home" button */
        .back-btn-container {
            margin-top: 40px;
        }

        /* Styling for buttons */
        .submit-btn, .action-btn {
            display: inline-block;
            width: auto;
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            margin: 5px; /* Added margin for spacing between buttons */
        }

        .submit-btn { /* For main form submission and back to home */
            background-color: #00e676;
            color: #000;
        }

        .action-btn.edit {
            color: black;
            background-color: #000000ff; /* Red for delete */
            box-shadow: 0px 0px 5px white;
            color: white;
        }

        .action-btn.delete {
            color: black;
            background-color: #000000ff; /* Red for delete */
            box-shadow: 0px 0px 5px white;
            color: white;
        }

        .submit-btn:hover {
            background-color: #00c853;
            transform: translateY(-2px);
        }
        .action-btn.edit:hover {
            background-color: #484848ff;
            transform: translateY(-2px);
        }
        .action-btn.delete:hover {
            background-color: #484848ff;
            transform: translateY(-2px);
        }

        /* Styles for messages */
        .message-container {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        .message-container.show {
            opacity: 1;
        }
        .message-container.success {
            background-color: #4CAF50;
            color: white;
        }
        .message-container.error {
            background-color: #f44336;
            color: white;
        }

        /* Styles for the custom confirmation modal */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.7); /* Black w/ opacity */
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #333;
            margin: auto;
            padding: 30px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        }

        .modal-content p {
            margin-bottom: 20px;
            font-size: 18px;
            color: #fff;
        }

        .modal-buttons button {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .modal-buttons #confirmDeleteBtn {
            background-color: #f44336;
            color: white;
        }

        .modal-buttons #confirmDeleteBtn:hover {
            background-color: #da190b;
        }

        .modal-buttons #cancelDeleteBtn {
            background-color: #555;
            color: white;
        }

        .modal-buttons #cancelDeleteBtn:hover {
            background-color: #777;
        }

        /* Edit Form Styling */
        .edit-form-container {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: left;
        }

        .edit-form-container h3 {
            color: #00e676;
            margin-bottom: 20px;
            text-align: center;
        }

        .edit-form-container .form-group {
            margin-bottom: 15px;
        }

        .edit-form-container .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #ccc;
        }

        .edit-form-container .form-group input,
        .edit-form-container .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #555;
            border-radius: 5px;
            background-color: #444;
            color: #fff;
            outline: none;
        }

        .edit-form-container .form-group input:focus,
        .edit-form-container .form-group select:focus {
            border-color: #00e676;
        }

        .edit-form-container .form-actions {
            text-align: center;
            margin-top: 20px;
        }
        .edit-form-container .form-actions .submit-btn {
            width: auto;
            padding: 10px 25px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php if (!empty($message)): ?>
            <div class="message-container <?php echo $message_type; ?> show">
                <?php echo $message; ?>
            </div>
            <script>
                // JavaScript to make the message fade out
                document.addEventListener('DOMContentLoaded', function() {
                    const messageDiv = document.querySelector('.message-container.show');
                    if (messageDiv) {
                        setTimeout(() => {
                            messageDiv.classList.remove('show');
                            // Remove from DOM after transition
                            messageDiv.addEventListener('transitionend', () => messageDiv.remove());
                        }, 3000); // Message visible for 3 seconds
                    }
                });
            </script>
        <?php endif; ?>

        <?php if ($edit_student_data): ?>
            <div class="edit-form-container">
                <h3>Edit Student Record</h3>
                <form method="POST" action="admin.php">
                    <input type="hidden" name="action" value="update_student">
                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($edit_student_data['id']); ?>">
                    
                    <div class="form-group">
                        <label for="editFullName">Full Name:</label>
                        <input type="text" id="editFullName" name="fullName" value="<?php echo htmlspecialchars($edit_student_data['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="editAge">Age:</label>
                        <input type="number" id="editAge" name="age" value="<?php echo htmlspecialchars($edit_student_data['age']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="editGender">Gender:</label>
                        <select id="editGender" name="gender" required>
                            <option value="male" <?php echo ($edit_student_data['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($edit_student_data['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo ($edit_student_data['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editEmail">Email:</label>
                        <input type="email" id="editEmail" name="email" value="<?php echo htmlspecialchars($edit_student_data['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="editPhoneNumber">Phone Number:</label>
                        <input type="text" id="editPhoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($edit_student_data['phone_number']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="editCourseType">Course Type:</label>
                        <select id="editCourseType" name="courseType" required>
                            <option value="web" <?php echo ($edit_student_data['course_type'] == 'web') ? 'selected' : ''; ?>>Web Development</option>
                            <option value="ai" <?php echo ($edit_student_data['course_type'] == 'ai') ? 'selected' : ''; ?>>AI & ML</option>
                            <option value="genai" <?php echo ($edit_student_data['course_type'] == 'genai') ? 'selected' : ''; ?>>Generative AI</option>
                            <option value="data" <?php echo ($edit_student_data['course_type'] == 'data') ? 'selected' : ''; ?>>Data Analytics</option>
                            <option value="blockchain" <?php echo ($edit_student_data['course_type'] == 'blockchain') ? 'selected' : ''; ?>>Blockchain</option>
                            <option value="cyber" <?php echo ($edit_student_data['course_type'] == 'cyber') ? 'selected' : ''; ?>>Cyber Security</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="submit-btn">Update Record</button>
                        <a href="admin.php" class="action-btn cancel-btn">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <h2>STUDENTS LIST (Total: <?php echo $total_students; ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>NAME</th>
                    <th>AGE</th>
                    <th>GENDER</th>
                    <th>EMAIL</th>
                    <th>PHONE NUMBER</th>
                    <th>COURSE TYPE</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    $row_count = 0; // Initialize a counter for rows
                    while($row = $result->fetch_assoc()) {
                        $row_count++; // Increment the counter for each row
                        // Conditional statement to skip rows 2, 3, 4, and 5 for display
                        if ($row_count == 1 || $row_count > 0) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['age']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['phone_number']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['course_type']) . "</td>";
                            echo "<td>";
                            echo "<a href='admin.php?action=edit_student_form&id=" . htmlspecialchars($row['id']) . "' class='action-btn edit'>Edit</a>";
                            echo "<button class='action-btn delete' data-id='" . htmlspecialchars($row['id']) . "'>Delete</button>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    }
                } else {
                    echo "<tr><td colspan='7'>No students registered yet.</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <div class="back-btn-container">
            <a href="index.php" class="submit-btn">Back to Home</a>
        </div>
    </div>

    <!-- Custom Confirmation Modal for Delete -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <p>Are you sure you want to delete this record?</p>
            <div class="modal-buttons">
                <button id="confirmDeleteBtn">Confirm</button>
                <button id="cancelDeleteBtn">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.action-btn.delete');
            const confirmModal = document.getElementById('confirmModal');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            let studentToDeleteId = null;

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    studentToDeleteId = this.dataset.id;
                    confirmModal.style.display = 'flex'; // Show the modal
                });
            });

            confirmDeleteBtn.addEventListener('click', function() {
                if (studentToDeleteId) {
                    window.location.href = 'admin.php?action=delete_student&id=' + studentToDeleteId;
                }
            });

            cancelDeleteBtn.addEventListener('click', function() {
                confirmModal.style.display = 'none'; // Hide the modal
                studentToDeleteId = null; // Reset ID
            });

            // Hide modal if clicked outside content
            confirmModal.addEventListener('click', function(event) {
                if (event.target === confirmModal) {
                    confirmModal.style.display = 'none';
                    studentToDeleteId = null;
                }
            });
        });
    </script>
</body>
</html>
