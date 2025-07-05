<?php
// dr_portfolio/admin/reset_password.php
session_start(); // Start session to display messages

require_once '../config/database.php'; // Adjust path for database connection

$message = '';
$message_type = ''; // 'success' or 'danger'
$valid_token = false;
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

// --- Verify Token on Page Load ---
if (!empty($email) && !empty($token)) {
    $sql = "SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_token_expires_at > CURRENT_TIMESTAMP";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $valid_token = true;
        $user = $result->fetch_assoc(); // Get user ID
    } else {
        $message = 'Invalid or expired password reset link.';
        $message_type = 'danger';
    }
    $stmt->close();
} else {
    $message = 'Invalid password reset request.';
    $message_type = 'danger';
}

// --- Handle New Password Submission ---
if ($valid_token && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';
    $user_id = $user['id']; // User ID fetched from token verification

    if (empty($new_password) || empty($confirm_new_password)) {
        $message = 'New password and confirmation are required.';
        $message_type = 'danger';
    } elseif ($new_password !== $confirm_new_password) {
        $message = 'New password and confirm new password do not match.';
        $message_type = 'danger';
    } elseif (strlen($new_password) < 8) {
        $message = 'New password must be at least 8 characters long.';
        $message_type = 'danger';
    }
    // Optional: Add more password complexity checks
    /*
    elseif (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) ||
            !preg_match('/[0-9]/', $new_password) || !preg_match('/[^A-Za-z0-9]/', $new_password)) {
        $message = 'New password must include uppercase, lowercase, numbers, and special characters.';
        $message_type = 'danger';
    }
    */
    else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password and invalidate token
        $sql_update_pass = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt_update_pass = $conn->prepare($sql_update_pass);
        $stmt_update_pass->bind_param("si", $hashed_password, $user_id);

        if ($stmt_update_pass->execute()) {
            $_SESSION['reset_success_message'] = 'Your password has been successfully reset. You can now log in with your new password.';
            header("Location: login.php"); // Redirect to login page
            exit();
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
    <title>Reset Password - Dr. Portfolio Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css"> <style>
        body {
            background-color: #f8f9fa;
        }
        .reset-password-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            background-color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-password-container">
            <h2 class="text-center mb-4">Reset Your Password</h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($valid_token): ?>
                <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>&email=<?php echo urlencode($email); ?>" method="POST">
                    <div class="form-group">
                        <label for="new_password">New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                        <small class="form-text text-muted">Password must be at least 8 characters long.</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_new_password">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required minlength="8">
                    </div>
                    <button type="submit" name="reset_password" class="btn btn-primary btn-block">Reset Password</button>
                </form>
            <?php else: ?>
                <p class="text-center">Please request a new password reset link if the current one is invalid or expired.</p>
                <p class="text-center mt-3"><a href="forgot_password.php">Request New Reset Link</a></p>
            <?php endif; ?>

            <p class="text-center mt-3"><a href="login.php">Back to Login</a></p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>