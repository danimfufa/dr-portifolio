<?php
// dr_portfolio/services.php
require_once 'config/database.php'; // Adjust path for include

// --- Fetch Services for Display ---
$services = [];
// Order by service_name or id, depending on preferred display order
$sql = "SELECT id, service_name, description, duration_minutes, price FROM services ORDER BY service_name ASC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
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
    <title>Our Services - Dr. [Doctor's Name]</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> <style>
        .service-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%; /* Ensure cards in a row have equal height */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .service-card h3 {
            color: #007bff;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .service-card p {
            color: #555;
            line-height: 1.6;
            flex-grow: 1; /* Allow description to take up available space */
        }
        .service-details {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #e9ecef;
        }
        .service-details strong {
            color: #343a40;
        }
        .hero-section {
            background: linear-gradient(rgba(0, 0,0, 0.6), rgba(0, 0, 0, 0.6)), url('img/services-hero.jpg') no-repeat center center/cover; /* Example background */
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
            <h1>Our Comprehensive Services</h1>
            <p>Providing expert care across a wide range of medical needs.</p>
        </div>
    </div>

    <main class="container py-5">
        <section id="services-list">
            <h2 class="text-center mb-5">Medical Expertise You Can Trust</h2>

            <?php if (!empty($services)): ?>
                <div class="row">
                    <?php foreach ($services as $service): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="service-card">
                                <div>
                                    <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                                    <p><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                                </div>
                                <div class="service-details">
                                    <?php if (!empty($service['duration_minutes'])): ?>
                                        <p><strong>Duration:</strong> <?php echo htmlspecialchars($service['duration_minutes']); ?> minutes</p>
                                    <?php endif; ?>
                                    <?php if (!empty($service['price'])): ?>
                                        <p><strong>Price:</strong> $<?php echo number_format(htmlspecialchars($service['price']), 2); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center" role="alert">
                    No services are currently listed. Please check back later!
                </div>
            <?php endif; ?>
        </section>

        <section id="call-to-action" class="text-center my-5">
            <h2>Ready to Book an Appointment?</h2>
            <p class="lead">Contact us today to schedule your consultation or treatment.</p>
            <a href="contact.php" class="btn btn-primary btn-lg">Contact Us</a>
        </section>

    </main>

    <?php include 'includes/footer.php'; // Include your footer ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    </body>
</html>