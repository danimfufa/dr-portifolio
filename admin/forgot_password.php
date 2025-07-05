<?php
// dr_portfolio/admin/forgot_password.php
session_start(); // Start session to display messages

require_once '../config/database.php'; // Adjust path for database connection
require_once '../vendor/src/PHPMailer.php'; // PHPMailer core
require_once '../vendor/src/SMTP.php';     // PHPMailer for SMTP
require_once '../vendor/src/Exception.php'; // PHPMailer exceptions

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$message = '';
$message_type = ''; // 'success' or 'danger'

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['forgot_password'])) {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'danger';
    } else {
        // Check if the email exists in the database
        $sql = "SELECT id, username FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Email exists, generate a unique token
            $token = bin2hex(random_bytes(32)); // 64 character hex string
            $expires = date("Y-m-d H:i:s", strtotime('+1 hour')); // Token valid for 1 hour

            // Store token and expiry in the database
            $sql_update_token = "UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?";
            $stmt_update_token = $conn->prepare($sql_update_token);
            $stmt_update_token->bind_param("ssi", $token, $expires, $user['id']);

            if ($stmt_update_token->execute()) {
                // Send email
                $mail = new PHPMailer(true);
                try {
                    // Server settings (YOU NEED TO CONFIGURE THESE)
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; // Your SMTP server (e.g., 'smtp.gmail.com' for Gmail)
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'your_email@example.com'; // Your SMTP username (e.g., your Gmail address)
                    $mail->Password   = 'your_email_password';   // Your SMTP password or App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use ENCRYPTION_SMTPS for port 465, ENCRYPTION_STARTTLS for port 587
                    $mail->Port       = 587; // TCP port to connect to

                    // Recipients
                    $mail->setFrom('no-reply@yourdomain.com', 'Dr. Portfolio Support'); // Sender email and name
                    $mail->addAddress($email, $user['username']); // Recipient email and name

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request for Dr. Portfolio Account';
                    // The reset link URL. Adjust 'http://localhost/dr_portfolio/admin/' if your setup is different
                    $reset_link = "http://localhost/dr_portfolio/admin/reset_password.php?token=" . $token . "&email=" . urlencode($email);

                    $mail->Body    = "Dear " . htmlspecialchars($user['username']) . ",<br><br>"
                                   . "You have requested to reset your password for your Dr. Portfolio account.<br>"
                                   . "Please click on the following link to reset your password:<br><br>"
                                   . "<a href='" . htmlspecialchars($reset_link) . "'>Reset My Password</a><br><br>"
                                   . "This link will expire in 1 hour.<br>"
                                   . "If you did not request a password reset, please ignore this email.<br><br>"
                                   . "Thank you,<br>Dr. Portfolio Team";
                    $mail->AltBody = "Dear " . htmlspecialchars($user['username']) . ",\n\n"
                                   . "You have requested to reset your password for your Dr. Portfolio account.\n"
                                   . "Please copy and paste the following link into your browser to reset your password:\n\n"
                                   . $reset_link . "\n\n"
                                   . "This link will expire in 1 hour.\n"
                                   . "If you did not request a password reset, please ignore this email.\n\n"
                                   . "Thank you,\nDr. Portfolio Team";

                    $mail->send();
                    $message = 'If an account with that email exists, a password reset link has been sent to your email address.';
                    $message_type = 'success';
                    // For security, always show a generic message whether the email exists or not.
                    // This prevents attackers from enumerating valid email addresses.
                } catch (Exception $e) {
                    $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    $message_type = 'danger';
                }
            } else {
                $message = 'Error saving reset token: ' . $stmt_update_token->error;
                $message_type = 'danger';
            }
            $stmt_update_token->close();
        } else {
            // Email does NOT exist, but show generic success message for security
            $message = 'If an account with that email exists, a password reset link has been sent to your email address.';
            $message_type = 'success';
        }
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
    <title>Forgot Password - Dr. Portfolio Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css"> <style>
        body {
            background-color: #f8f9fa;
        }
        .forgot-password-container {
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
        <div class="forgot-password-container">
            <h2 class="text-center mb-4">Forgot Your Password?</h2>
            <p class="text-center text-muted">Enter your email address and we'll send you a link to reset your password.</p>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form action="forgot_password.php" method="POST">
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                <button type="submit" name="forgot_password" class="btn btn-primary btn-block">Send Reset Link</button>
            </form>
            <p class="text-center mt-3"><a href="login.php">Back to Login</a></p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>