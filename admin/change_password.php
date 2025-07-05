<?php
// dr_portfolio/admin/change_password.php
session_start();

// Access control: Check if the user is logged in and is an admin/doctor
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'doctor')) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php'; // Adjust path for include

$message = '';
$message_type = ''; // 'success' or 'danger'

// --- Handle Password Change Form Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $user_id = $_SESSION['user_id']; // Get the ID of the logged-in user

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    // 1. Fetch current user's hashed password from DB
    $sql_fetch_pass = "SELECT password FROM users WHERE id = ?";
    $stmt_fetch_pass = $conn->prepare($sql_fetch_pass);
    $stmt_fetch_pass->bind_param("i", $user_id);
    $stmt_fetch_pass->execute();
    $result_fetch_pass = $stmt_fetch_pass->get_result();
    $user = $result_fetch_pass->fetch_assoc();
    $stmt_fetch_pass->close();

    if (!$user) {
        $message = 'User not found.';
        $message_type = 'danger';
    } elseif (!password_verify($current_password, $user['password'])) {
        // 2. Verify current password
        $message = 'Incorrect current password.';
        $message_type = 'danger';
    } elseif (empty($new_password) || empty($confirm_new_password)) {
        // 3. Basic validation for new passwords
        $message = 'New password and confirmation are required.';
        $message_type = 'danger';
    } elseif ($new_password !== $confirm_new_password) {
        // 4. Check if new password and confirmation match
        $message = 'New password and confirm new password do not match.';
        $message_type = 'danger';
    } elseif (strlen($new_password) < 8) {
        // 5. Password strength: minimum length
        $message = 'New password must be at least 8 characters long.';
        $message_type = 'danger';
    }
    // Optional: Add more password complexity checks (e.g., require numbers, symbols)
    /*
    elseif (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) ||
            !preg_match('/[0-9]/', $new_password) || !preg_match('/[^A-Za-z0-9]/', $new_password)) {
        $message = 'New password must include uppercase, lowercase, numbers, and special characters.';
        $message_type = 'danger';
    }
    */
    else {
        // All checks passed, hash and update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $sql_update_pass = "UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt_update_pass = $conn->prepare($sql_update_pass);
        $stmt_update_pass->bind_param("si", $hashed_password, $user_id);

        if ($stmt_update_pass->execute()) {
            $message = 'Password changed successfully!';
            $message_type = 'success';
            // Clear form fields on success
            $_POST = array();
        } else {
            $message = 'Error updating password: ' . $stmt_update_pass->error;
            $message_type = 'danger';
        }
        $stmt_update_pass->close();
    }
}

// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css"> <style>
        /* Re-using admin sidebar styles for consistency */
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
            <div class="sidebar-heading text-white py-4">Dr. <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>'s Admin</div>
            <div class="list-group list-group-flush">
                <a href="index.php" class="list-group-item list-group-item-action bg-dark text-white">Dashboard</a>
                <a href="profile.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Profile</a>
                <a href="services.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Services</a>
                <a href="appointments.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Appointments</a>
                <a href="testimonials.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Testimonials</a>
                <a href="change_password.php" class="list-group-item list-group-item-action bg-dark text-white active">Change Password</a>
                <a href="logout.php" class="list-group-item list-group-item-action bg-dark text-white">Logout</a>
            </div>
        </div>
        <div id="page-content-wrapper" class="flex-grow-1">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="menu-toggle">Toggle Menu</button>
                    <h5 class="my-2 ml-3">Change Password</h5>
                </div>
            </nav>

            <div class="container-fluid py-4">
                <h1 class="mt-4">Change Password</h1>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="card mt-4">
                    <div class="card-header">
                        Update Your Password
                    </div>
                    <div class="card-body">
                        <form action="change_password.php" method="POST">
                            <div class="form-group">
                                <label for="current_password">Current Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password">New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                <small class="form-text text-muted">Password must be at least 8 characters long.</small>
                            </div>
                            <div class="form-group">
                                <label for="confirm_new_password">Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required minlength="8">
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../js/script.js"></script>
</body>
</html>