<?php
// index.php - Main Homepage
require_once 'config/database.php'; // Include the database connection

// Fetch doctor's profile data
$doctor_profile = null;
$sql = "SELECT * FROM doctor_profile LIMIT 1"; // LIMIT 1 as there should only be one doctor profile
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $doctor_profile = $result->fetch_assoc();
}

include 'includes/header.php'; // Include the header
?>

<div class="jumbotron text-center">
    <?php if ($doctor_profile): ?>
        <?php if (!empty($doctor_profile['profile_image_path'])): ?>
            <img src="<?php echo htmlspecialchars($doctor_profile['profile_image_path']); ?>" alt="<?php echo htmlspecialchars($doctor_profile['name']); ?>" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
        <?php endif; ?>
        <h1 class="display-4">Welcome to Dr. <?php echo htmlspecialchars($doctor_profile['name']); ?>'s Practice</h1>
        <p class="lead">Specializing in <?php echo htmlspecialchars($doctor_profile['specialty']); ?></p>
        <hr class="my-4">
        <p><?php echo nl2br(htmlspecialchars($doctor_profile['bio'])); ?></p>
        <p class="lead">
            <a class="btn btn-primary btn-lg" href="appointments.php" role="button">Book an Appointment</a>
            <a class="btn btn-outline-primary btn-lg" href="services.php" role="button">Learn More About Services</a>
        </p>
    <?php else: ?>
        <h1 class="display-4">Welcome!</h1>
        <p class="lead">Doctor profile not yet configured. Please access the admin panel to set it up.</p>
        <p class="lead">
            <a class="btn btn-primary btn-lg" href="dr_portfolio/appointments.php" role="button">Book an Appointment</a>
            <a class="btn btn-outline-primary btn-lg" href="services.php" role="button">Learn More About Services</a>
        </p>
    <?php endif; ?>
</div>

<section class="mb-5">
    <h2>About Dr. <?php echo ($doctor_profile ? htmlspecialchars($doctor_profile['name']) : '[Your Name]'); ?></h2>
    <?php if ($doctor_profile): ?>
        <p><?php echo nl2br(htmlspecialchars($doctor_profile['experience'])); ?></p>
        <h3>Qualifications:</h3>
        <p><?php echo nl2br(htmlspecialchars($doctor_profile['qualifications'])); ?></p>
    <?php else: ?>
        <p>Details about the doctor's experience and qualifications will be displayed here once configured in the admin panel.</p>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; // Include the footer ?>