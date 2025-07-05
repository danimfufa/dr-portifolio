<?php
// dr_portfolio/contact.php
require_once 'config/database.php'; // Required for database connection, even if just for common include

$message = '';
$message_type = ''; // 'success' or 'danger'

// --- Handle Contact Form Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_contact'])) {
    $name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = filter_var($_POST['subject'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message_text = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message_text)) {
        $message = 'Please fill in all required fields.';
        $message_type = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'danger';
    } else {
        // --- IMPORTANT: This is where you would typically send an email ---
        // For demonstration, we'll just show a success message.
        // To send actual emails, you would need a library like PHPMailer
        // and configure it with your SMTP settings.

        $to = 'your_doctors_email@example.com'; // Replace with the doctor's actual email
        $email_subject = "New Contact Form Submission: " . $subject;
        $email_body = "You have received a new message from your website contact form.\n\n" .
                      "Name: " . $name . "\n" .
                      "Email: " . $email . "\n" .
                      "Subject: " . $subject . "\n" .
                      "Message:\n" . $message_text;

        $headers = "From: webmaster@yourwebsite.com\r\n"; // Replace with your website's email
        $headers .= "Reply-To: " . $email . "\r\n";
        $headers .= "Content-type: text/plain; charset=UTF-8\r\n";

        // Uncomment the line below to try sending an actual email using PHP's mail() function.
        // Note: mail() might require specific server configurations (like an SMTP server) to work.
        // if (mail($to, $email_subject, $email_body, $headers)) {
        //     $message = 'Your message has been sent successfully! We will get back to you shortly.';
        //     $message_type = 'success';
        //     $_POST = array(); // Clear form
        // } else {
        //     $message = 'There was an error sending your message. Please try again later or contact us directly.';
        //     $message_type = 'danger';
        // }

        // --- Simulated success for now ---
        $message = 'Your message has been sent successfully! (Simulated)';
        $message_type = 'success';
        $_POST = array(); // Clear form fields after successful submission (simulated)
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
    <title>Contact Us - Dr. [Doctor's Name]</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> <style>
        .contact-info-section, .contact-form-section {
            padding: 50px 0;
        }
        .info-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        .info-card i {
            font-size: 2.5rem;
            color: #007bff;
            margin-bottom: 15px;
        }
        .info-card h4 {
            color: #343a40;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .info-card p {
            color: #555;
            margin-bottom: 0;
        }
        .form-card {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .hero-section {
            background: linear-gradient(rgba(0, 0,0, 0.6), rgba(0, 0, 0, 0.6)), url('img/contact-hero.jpg') no-repeat center center/cover; /* Example background */
            color: white;
            padding: 80px 0;
            text-align: center;
            margin-bottom: 40px;
        }
        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .hero-section p {
            font-size: 1.25rem;
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; // Include your header navigation ?>

    <div class="hero-section">
        <div class="container">
            <h1>Get in Touch</h1>
            <p>We're here to answer your questions and provide the care you need.</p>
        </div>
    </div>

    <main class="container py-5">
        <section class="contact-info-section">
            <h2 class="text-center mb-5">Our Contact Information</h2>
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="info-card">
                        <i class="fas fa-phone-alt"></i>
                        <h4>Phone Number</h4>
                        <p><a href="tel:+1234567890">+1 (234) 567-890</a></p>
                        <p><small>Mon-Fri, 9am-5pm</small></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card">
                        <i class="fas fa-envelope"></i>
                        <h4>Email Address</h4>
                        <p><a href="mailto:info@yourdoctorsclinic.com">info@yourdoctorsclinic.com</a></p>
                        <p><small>We respond within 24 hours</small></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card">
                        <i class="fas fa-map-marker-alt"></i>
                        <h4>Our Location</h4>
                        <p>123 Medical Drive, Suite 456</p>
                        <p>Cityville, State 12345</p>
                        <p><small><a href="https://www.google.com/maps?q=123+Medical+Drive,Cityville" target="_blank">Get Directions</a></small></p>
                    </div>
                </div>
            </div>
        </section>

        <section class="contact-form-section">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="form-card">
                        <h2 class="text-center mb-4">Send Us a Message</h2>

                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <form action="contact.php" method="POST">
                            <div class="form-group">
                                <label for="name">Your Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Your Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="subject">Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="message">Your Message <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="message" name="message" rows="6" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" name="submit_contact" class="btn btn-primary btn-block">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; // Include your footer ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>