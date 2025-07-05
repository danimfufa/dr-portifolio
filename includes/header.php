<?php
// includes/header.php
// You might start a session here if you plan to use user authentication (for doctor login)
// session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dr. [Your Name] - General Surgeon</title>
    <link rel="stylesheet" href="/dr_portfolio/css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/dr_portfolio/index.php">Dr. [Your Name]</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/dr_portfolio/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/dr_portfolio/services.php">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/dr_portfolio/appointments.php">Book Appointment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/dr_portfolio/contact.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/dr_portfolio/admin/login.php">Admin Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    