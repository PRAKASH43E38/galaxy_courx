<?php
// Debug mode (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection details
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'newRegiz'; // ✅ DB name changed

// Enable error reporting for MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Create connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Variables
$message = '';
$message_type = '';
$edit_student_data = null;

// --- DELETE Student ---
if (isset($_GET['action']) && $_GET['action'] == 'delete_student' && isset($_GET['id'])) {
    $student_id = intval($_GET['id']);
    $sql = "DELETE FROM registrations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);

    if ($stmt->execute()) {
        $message = "Student record deleted successfully!";
        $message_type = 'success';
    } else {
        $message = "Error deleting record: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
    header("Location: admin.php?msg=" . urlencode($message) . "&type=" . $message_type);
    exit();
}

// --- UPDATE Student ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_student') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $full_name = htmlspecialchars($_POST['fullName'] ?? '');
    $age = intval($_POST['age'] ?? 0);
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
            $message = "Error updating record: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message = "Invalid student ID for update.";
        $message_type = 'error';
    }
    header("Location: admin.php?msg=" . urlencode($message) . "&type=" . $message_type);
    exit();
}

// --- INSERT (New Registration) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    $full_name = htmlspecialchars($_POST['fullName'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $gender = htmlspecialchars($_POST['gender'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $phone = htmlspecialchars($_POST['phoneNumber'] ?? '');
    $course = htmlspecialchars($_POST['courseType'] ?? '');

    $sql = "INSERT INTO registrations (full_name, age, gender, email, phone_number, course_type) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sissss", $full_name, $age, $gender, $email, $phone, $course);

    header('Content-Type: application/json');
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Registration successful!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error during registration: ' . $stmt->error]);
    }
    $stmt->close();
    exit();
}

// --- Edit Student Form ---
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

// --- Message ---
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['msg']);
    $message_type = htmlspecialchars($_GET['type']);
}

// --- Count Students ---
$count_sql = "SELECT COUNT(*) AS total_students FROM registrations";
$count_result = $conn->query($count_sql);
$total_students = 0;
if ($count_result && $count_result->num_rows > 0) {
    $count_row = $count_result->fetch_assoc();
    $total_students = $count_row['total_students'];
}

// --- Fetch All Students ---
$sql = "SELECT id, full_name, age, gender, email, phone_number, course_type FROM registrations ORDER BY full_name ASC";
$result = $conn->query($sql);

// Close connection after queries
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    
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

