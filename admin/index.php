<?php
// dr_portfolio/admin/index.php - Admin Dashboard
session_start(); // Start the session at the very beginning of the script

// --- Access Control ---
// Check if the user is logged in (i.e., 'user_id' is set in the session)
// AND if their role is either 'admin' or 'doctor'.
// If not, redirect them to the login page and stop script execution.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'doctor')) {
    header("Location: login.php"); // Redirect to the login page
    exit(); // Stop further script execution
}

// Include the database connection file.
// The path '../config/database.php' means: go up one directory from 'admin/'
// to 'dr_portfolio/', then go into the 'config/' directory.
require_once '../config/database.php';

// --- Fetch Data for Dashboard Overview ---
// We'll fetch the next 5 pending appointments to display on the dashboard.
$pending_appointments = []; // Initialize an empty array to hold appointment data
$sql = "SELECT id, patient_name, appointment_date, appointment_time, reason_for_visit 
        FROM appointments 
        WHERE status = 'pending' 
        ORDER BY appointment_date ASC, appointment_time ASC 
        LIMIT 5"; // Order by date and time, limit to 5 results

$result = $conn->query($sql); // Execute the query

// Check if the query was successful and returned any rows
if ($result && $result->num_rows > 0) {
    // Loop through each row and add it to the $pending_appointments array
    while ($row = $result->fetch_assoc()) {
        $pending_appointments[] = $row;
    }
}

// --- HTML Structure for Admin Dashboard ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Dr. Portfolio</title>
    <!-- Link to Bootstrap CSS for responsive styling -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Link to your custom stylesheet (adjust path if necessary).
         '../css/style.css' means: go up one directory from 'admin/'
         to 'dr_portfolio/', then go into the 'css/' directory. -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Custom styles for the admin sidebar */
        .admin-sidebar {
            background-color: #343a40; /* Dark background */
            color: #f8f9fa; /* Light text color */
            padding: 20px;
            height: 100vh; /* Make sidebar take full viewport height */
            position: sticky; /* Keep sidebar fixed when scrolling */
            top: 0;
        }
        .admin-sidebar .nav-link {
            color: #f8f9fa; /* Link color */
            padding: 10px 15px;
            border-radius: 5px; /* Rounded corners for links */
        }
        .admin-sidebar .nav-link:hover {
            background-color: #007bff; /* Highlight on hover */
            color: #fff;
        }
        .admin-sidebar .nav-item.active .nav-link {
            background-color: #007bff; /* Active link styling */
            color: #fff;
        }
        /* Basic styling for the main content area */
        #page-content-wrapper {
            min-width: 0;
            width: 100%;
        }
        /* Styling for the toggle button (optional, for responsive sidebar) */
        #wrapper.toggled #sidebar-wrapper {
            margin-left: -15rem; /* Hide sidebar */
        }
        @media (min-width: 768px) {
            #sidebar-wrapper {
                margin-left: 0; /* Show sidebar on larger screens */
            }
            #page-content-wrapper {
                min-width: 0;
                width: 100%;
            }
            #wrapper.toggled #sidebar-wrapper {
                margin-left: -15rem;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark border-right admin-sidebar" id="sidebar-wrapper">
            <div class="sidebar-heading text-white py-4">Dr. <?php echo htmlspecialchars($_SESSION['username']); ?>'s Admin</div>
            <div class="list-group list-group-flush">
                <!-- Navigation links for admin section -->
                <a href="index.php" class="list-group-item list-group-item-action bg-dark text-white active">Dashboard</a>
                <a href="profile.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Profile</a>
                <a href="services.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Services</a>
                <a href="appointments.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Appointments</a>
                <a href="testimonials.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Testimonials</a>
                <a href="logout.php" class="list-group-item list-group-item-action bg-dark text-white">Logout</a>
            </div>
        </div>
        <!-- /#sidebar-wrapper -->

        <!-- Page Content -->
        <div id="page-content-wrapper" class="flex-grow-1">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <!-- Button to toggle sidebar on smaller screens -->
                    <button class="btn btn-primary" id="menu-toggle">Toggle Menu</button>
                    <h5 class="my-2 ml-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h5>
                </div>
            </nav>

            <div class="container-fluid py-4">
                <h1 class="mt-4">Admin Dashboard</h1>
                <p>Overview of your practice.</p>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-warning text-white">
                                Pending Appointments (Next 5)
                            </div>
                            <div class="card-body">
                                <?php if (!empty($pending_appointments)): ?>
                                    <ul class="list-group">
                                        <?php foreach ($pending_appointments as $appointment): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>
                                                    <strong><?php echo htmlspecialchars($appointment['patient_name']); ?></strong><br>
                                                    <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?> at <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                </span>
                                                <!-- Link to view details of the appointment (will be implemented later) -->
                                                <a href="appointments.php?view=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-info">View</a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="card-text">No pending appointments.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- You can add more dashboard cards here, e.g., total appointments, recent testimonials, etc. -->
                </div>
            </div>
        </div>
        <!-- /#page-content-wrapper -->
    </div>
    <!-- /#wrapper -->

    <!-- Bootstrap core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // JavaScript to toggle the sidebar (for responsive design)
        $("#menu-toggle").click(function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });
    </script>
</body>
</html>
<?php
// Close the database connection at the end of the script.
if (isset($conn)) {
    $conn->close();
}
?>