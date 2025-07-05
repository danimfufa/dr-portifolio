<?php
// dr_portfolio/admin/login.php
session_start(); // Start the session at the very beginning of the script

// Add this block to display the success message from reset_password.php
if (isset($_SESSION['reset_success_message'])) {
    $message = $_SESSION['reset_success_message'];
    $message_type = 'success';
    unset($_SESSION['reset_success_message']); // Clear the message after displaying it
}

// Include the database connection file.
// The path 'config/database.php' is relative to the dr_portfolio/admin/ directory,
// so we need '../' to go up one level to the dr_portfolio/ directory,
// then down into the config/ directory.
require_once '../config/database.php';

$error_message = ''; // Initialize an empty variable to store error messages

// Check if the form has been submitted using the POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and retrieve username and password from the form submission
    // The '??' operator provides a default empty string if the $_POST variable is not set,
    // preventing 'Undefined index' notices.
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Basic validation: Check if both fields are not empty
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        // Use prepared statements to prevent SQL Injection attacks.
        // We select the id, username, password hash, and role for the given username.
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");

        // 's' indicates that the parameter is a string.
        $stmt->bind_param("s", $username);

        // Execute the prepared statement
        $stmt->execute();

        // Get the result set from the executed statement
        $result = $stmt->get_result();

        // Check if a user with the provided username was found
        if ($result->num_rows == 1) {
            // Fetch the user's data as an associative array
            $user = $result->fetch_assoc();

            // Verify the provided password against the hashed password stored in the database.
            // password_verify() is the correct function for this.
            if (password_verify($password, $user['password'])) {
                // Password is correct! Set session variables for authentication.
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role']; // Store the user's role for access control

                // Redirect the user to the admin dashboard upon successful login.
                // header() must be called before any actual HTML output.
                header("Location: index.php");
                exit(); // Always call exit() after a header redirect to prevent further script execution.
            } else {
                // Password does not match
                $error_message = 'Invalid username or password.';
            }
        } else {
            // No user found with that username
            $error_message = 'Invalid username or password.';
        }
        // Close the prepared statement
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Dr. Portfolio</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            background-color: #f8f9fa; /* Light background for the login page */
        }
        .login-container {
            max-width: 400px; /* Max width for the login form container */
            margin: 100px auto; /* Center the container horizontally and add top margin */
            padding: 30px; /* Inner padding */
            background-color: #ffffff; /* White background for the form */
            border-radius: 8px; /* Slightly rounded corners */
            box-shadow: 0 0 15px rgba(0,0,0,0.1); /* Subtle shadow for depth */
        }
        .login-container h2 {
            margin-bottom: 30px; /* Space below the heading */
            text-align: center; /* Center the heading text */
            color: #007bff; /* Bootstrap primary blue color */
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <?php
        // Display error message if it's not empty
        if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); // Sanitize output ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button><br>
             <div class="text-center">
                    <a href="forgot_password.php">Forgot Password?</a> </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
<?php
// Close the database connection at the end of the script.
// This is good practice to free up resources.
if (isset($conn)) {
    $conn->close();
}
?>