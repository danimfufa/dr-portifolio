<?php
// dr_portfolio/admin/profile.php
session_start();

// Access control: Check if the user is logged in and is an admin/doctor
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'doctor')) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';

$message = '';
$message_type = ''; // 'success' or 'danger'

// Define the absolute server-side path for the uploads directory
// This is generally the most robust way to handle file system operations.
// __DIR__ is the directory of the current file (admin/), so ../ goes up to dr_portfolio/ then into uploads/
$upload_base_dir = __DIR__ . '/../uploads/';

// Ensure the upload directory exists and is writable
if (!is_dir($upload_base_dir)) {
    // Attempt to create the directory with read/write/execute permissions for owner and group, read/execute for others
    if (!mkdir($upload_base_dir, 0775, true)) {
        $message = 'Error creating upload directory. Please check file system permissions for: ' . htmlspecialchars($upload_base_dir);
        $message_type = 'danger';
    }
}
// Double check if it's writable after creation attempt or if it already existed
if (!is_writable($upload_base_dir) && empty($message)) { // Don't overwrite existing mkdir error
    $message = 'Upload directory is not writable. Please check folder permissions for: ' . htmlspecialchars($upload_base_dir);
    $message_type = 'danger';
}

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $specialty = $_POST['specialty'] ?? '';
    $qualifications = $_POST['qualifications'] ?? '';
    $experience = $_POST['experience'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';
    $contact_phone = $_POST['contact_phone'] ?? '';
    $clinic_address = $_POST['clinic_address'] ?? '';
    $social_media_links = $_POST['social_media_links'] ?? '';

    // This is the currently stored path from the DB, sent via hidden field (e.g., 'uploads/old_image.jpg')
    $current_profile_image_db_path = $_POST['current_profile_image_path'] ?? '';
    $profile_image_path_for_db = $current_profile_image_db_path; // Default: keep the existing image path

    // --- Image Upload Processing ---
    // Check if a new file was actually uploaded and without errors
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['profile_image']['tmp_name'];
        $original_file_name = basename($_FILES['profile_image']['name']);
        $file_ext = strtolower(pathinfo($original_file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = uniqid('profile_') . '.' . $file_ext; // Generate a unique name
            $destination_server_path = $upload_base_dir . $new_file_name; // Absolute path for server-side move

            if (move_uploaded_file($file_tmp_name, $destination_server_path)) {
                // SUCCESSFUL UPLOAD: Update the path that will be stored in the database
                // This path is relative to the project root for web accessibility
                $profile_image_path_for_db = 'uploads/' . $new_file_name;

                // Delete the old image file from the server if a new one was uploaded
                // and a valid old path existed in the database.
                if (!empty($current_profile_image_db_path) && strpos($current_profile_image_db_path, 'uploads/') === 0) {
                    $old_image_server_path = __DIR__ . '/../' . $current_profile_image_db_path; // Reconstruct server path
                    if (file_exists($old_image_server_path)) {
                        unlink($old_image_server_path); // Delete the old file
                    }
                }
            } else {
                $message = 'Failed to move uploaded file. Check directory permissions for ' . htmlspecialchars($upload_base_dir) . '.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.';
            $message_type = 'danger';
        }
    }
    // Handle other potential upload errors (e.g., file too large, partial upload)
    else if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $message = 'File upload error: ' . $_FILES['profile_image']['error'] . ' (See PHP upload error codes for details).';
        $message_type = 'danger';
    }


    // Only proceed with DB update if no critical errors (especially image upload problems)
    if (empty($message)) {
        // Check if a profile already exists
        $stmt_check = $conn->prepare("SELECT id FROM doctor_profile LIMIT 1");
        $stmt_check->execute();
        $check_result = $stmt_check->get_result();

        if ($check_result->num_rows > 0) {
            // Update existing profile
            $sql = "UPDATE doctor_profile SET
                    name = ?, specialty = ?, qualifications = ?, experience = ?, bio = ?,
                    profile_image_path = ?, contact_email = ?, contact_phone = ?,
                    clinic_address = ?, social_media_links = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssss", $name, $specialty, $qualifications, $experience, $bio,
                               $profile_image_path_for_db, $contact_email, $contact_phone,
                               $clinic_address, $social_media_links);
        } else {
            // Insert new profile
            $sql = "INSERT INTO doctor_profile (name, specialty, qualifications, experience, bio,
                                                profile_image_path, contact_email, contact_phone,
                                                clinic_address, social_media_links)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssss", $name, $specialty, $qualifications, $experience, $bio,
                               $profile_image_path_for_db, $contact_email, $contact_phone,
                               $clinic_address, $social_media_links);
        }

        if ($stmt->execute()) {
            $message = 'Doctor profile updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error updating profile: ' . $stmt->error;
            $message_type = 'danger';
        }
        $stmt->close();
        $stmt_check->close();
    }
}

// --- Fetch Doctor Profile Data for Display (after any updates) ---
$doctor_profile = null;
$sql = "SELECT * FROM doctor_profile LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $doctor_profile = $result->fetch_assoc();
}

// Prepare default values for the form if no profile exists
$default_name = $doctor_profile['name'] ?? '';
$default_specialty = $doctor_profile['specialty'] ?? 'General Surgery';
$default_qualifications = $doctor_profile['qualifications'] ?? '';
$default_experience = $doctor_profile['experience'] ?? '';
$default_bio = $doctor_profile['bio'] ?? '';
// This variable holds the path from the database (e.g., 'uploads/image.jpg')
$default_profile_image_path = $doctor_profile['profile_image_path'] ?? '';
$default_contact_email = $doctor_profile['contact_email'] ?? '';
$default_contact_phone = $doctor_profile['contact_phone'] ?? '';
$default_clinic_address = $doctor_profile['clinic_address'] ?? '';
$default_social_media_links = $doctor_profile['social_media_links'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile - Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-sidebar {
            background-color: #343a40;
            color: #f8f9fa;
            padding: 20px;
            height: 100vh;
        }
        .admin-sidebar .nav-link {
            color: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
        }
        .admin-sidebar .nav-link:hover {
            background-color: #007bff;
            color: #fff;
        }
        .admin-sidebar .nav-item.active .nav-link {
            background-color: #007bff;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <div class="bg-dark border-right admin-sidebar" id="sidebar-wrapper">
            <div class="sidebar-heading text-white py-4">Dr. <?php echo htmlspecialchars($_SESSION['username']); ?>'s Admin</div>
            <div class="list-group list-group-flush">
                <a href="index.php" class="list-group-item list-group-item-action bg-dark text-white">Dashboard</a>
                <a href="profile.php" class="list-group-item list-group-item-action bg-dark text-white active">Manage Profile</a>
                <a href="services.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Services</a>
                <a href="appointments.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Appointments</a>
                <a href="testimonials.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Testimonials</a>
                <a href="logout.php" class="list-group-item list-group-item-action bg-dark text-white">Logout</a>
            </div>
        </div>
        <div id="page-content-wrapper" class="flex-grow-1">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="menu-toggle">Toggle Menu</button>
                    <h5 class="my-2 ml-3">Manage Doctor Profile</h5>
                </div>
            </nav>

            <div class="container-fluid py-4">
                <h1 class="mt-4">Doctor Profile</h1>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Doctor's Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($default_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="specialty">Specialty</label>
                        <input type="text" class="form-control" id="specialty" name="specialty" value="<?php echo htmlspecialchars($default_specialty); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="qualifications">Qualifications (HTML allowed for formatting)</label>
                        <textarea class="form-control" id="qualifications" name="qualifications" rows="5"><?php echo htmlspecialchars($default_qualifications); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="experience">Experience (HTML allowed for formatting)</label>
                        <textarea class="form-control" id="experience" name="experience" rows="5"><?php echo htmlspecialchars($default_experience); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="bio">Short Bio (HTML allowed for formatting)</label>
                        <textarea class="form-control" id="bio" name="bio" rows="5"><?php echo htmlspecialchars($default_bio); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="profile_image">Profile Image</label>
                        <input type="file" class="form-control-file" id="profile_image" name="profile_image" accept="image/*">
                        <?php if (!empty($default_profile_image_path)): ?>
                            <small class="form-text text-muted mt-2">Current Image:</small>
                            <img src="<?php echo htmlspecialchars($default_profile_image_path); ?>" alt="Current Profile Image" class="img-thumbnail mt-2" style="max-width: 150px;">
                            <input type="hidden" name="current_profile_image_path" value="<?php echo htmlspecialchars($default_profile_image_path); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="contact_email">Contact Email</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($default_contact_email); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_phone">Contact Phone</label>
                        <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($default_contact_phone); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="clinic_address">Clinic Address</label>
                        <input type="text" class="form-control" id="clinic_address" name="clinic_address" value="<?php echo htmlspecialchars($default_clinic_address); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="social_media_links">Social Media Links (Comma separated or JSON string)</label>
                        <textarea class="form-control" id="social_media_links" name="social_media_links" rows="3"><?php echo htmlspecialchars($default_social_media_links); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Profile</button>
                </form>
            </div>
        </div>
        </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Optional: Toggle sidebar for smaller screens
        $("#menu-toggle").click(function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });
    </script>
</body>
</html>
<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>