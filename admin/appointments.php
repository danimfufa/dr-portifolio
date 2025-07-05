<?php
// dr_portfolio/admin/appointments.php
session_start();

// Access control: Check if the user is logged in and is an admin/doctor
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'doctor')) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php'; // Adjust path for include

$message = '';
$message_type = ''; // 'success' or 'danger'

// --- Handle Actions (Update Status, Delete) ---

// Handle Update Status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $new_status = $_POST['new_status'] ?? '';

    if (!empty($appointment_id) && in_array($new_status, ['pending', 'confirmed', 'cancelled', 'completed'])) {
        $sql = "UPDATE appointments SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_status, $appointment_id);
        if ($stmt->execute()) {
            $message = 'Appointment status updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error updating status: ' . $stmt->error;
            $message_type = 'danger';
        }
        $stmt->close();
    } else {
        $message = 'Invalid request for status update.';
        $message_type = 'danger';
    }
}

// Handle Delete Appointment
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $appointment_id = $_GET['id'];
    $sql = "DELETE FROM appointments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
    if ($stmt->execute()) {
        $message = 'Appointment deleted successfully!';
        $message_type = 'success';
        // Redirect to clear the GET parameters after deletion
        header("Location: appointments.php?message=" . urlencode($message) . "&type=" . $message_type);
        exit();
    } else {
        $message = 'Error deleting appointment: ' . $stmt->error;
        $message_type = 'danger';
    }
    $stmt->close();
}

// Fetch message from GET parameters if redirected after deletion/update
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// --- Fetch Appointments for Display ---
$appointments = [];
$current_filter_status = $_GET['filter_status'] ?? 'all'; // Default filter

$sql = "SELECT a.*, s.service_name FROM appointments a LEFT JOIN services s ON a.service_id = s.id";
$where_clauses = [];
$bind_params = [];
$bind_types = '';

// Apply filter if not 'all'
if ($current_filter_status != 'all') {
    $where_clauses[] = "a.status = ?";
    $bind_params[] = $current_filter_status;
    $bind_types .= 's';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";


$stmt = $conn->prepare($sql);

if (!empty($bind_params)) {
    $stmt->bind_param($bind_types, ...$bind_params);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - Admin Dashboard</title>
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
        /* Status Badges */
        .badge-pending { background-color: #ffc107; color: #343a40; } /* Yellow */
        .badge-confirmed { background-color: #28a745; color: #fff; } /* Green */
        .badge-cancelled { background-color: #dc3545; color: #fff; } /* Red */
        .badge-completed { background-color: #6c757d; color: #fff; } /* Gray */
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <div class="bg-dark border-right admin-sidebar" id="sidebar-wrapper">
            <div class="sidebar-heading text-white py-4">Dr. <?php echo htmlspecialchars($_SESSION['username']); ?>'s Admin</div>
            <div class="list-group list-group-flush">
                <a href="index.php" class="list-group-item list-group-item-action bg-dark text-white">Dashboard</a>
                <a href="profile.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Profile</a>
                <a href="services.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Services</a>
                <a href="appointments.php" class="list-group-item list-group-item-action bg-dark text-white active">Manage Appointments</a>
                <a href="testimonials.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Testimonials</a>
                <a href="logout.php" class="list-group-item list-group-item-action bg-dark text-white">Logout</a>
            </div>
        </div>
        <div id="page-content-wrapper" class="flex-grow-1">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="menu-toggle">Toggle Menu</button>
                    <h5 class="my-2 ml-3">Manage Appointments</h5>
                </div>
            </nav>

            <div class="container-fluid py-4">
                <h1 class="mt-4">Manage Appointments</h1>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <form action="appointments.php" method="GET" class="form-inline">
                        <label for="filter_status" class="mr-2">Filter by Status:</label>
                        <select name="filter_status" id="filter_status" class="form-control mr-2" onchange="this.form.submit()">
                            <option value="all" <?php echo ($current_filter_status == 'all') ? 'selected' : ''; ?>>All</option>
                            <option value="pending" <?php echo ($current_filter_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo ($current_filter_status == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo ($current_filter_status == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="completed" <?php echo ($current_filter_status == 'completed') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                        <noscript><button type="submit" class="btn btn-primary">Filter</button></noscript>
                    </form>
                </div>


                <h2 class="mt-4">All Appointments</h2>
                <?php if (!empty($appointments)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Service</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Booked At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['id']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_email']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
                                        <td><?php echo htmlspecialchars(date('h:i A', strtotime($appointment['appointment_time']))); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['service_name'] ?? 'Not specified'); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($appointment['message'] ?? '')); ?></td>
                                        <td><span class="badge badge-pill badge-<?php echo htmlspecialchars($appointment['status']); ?>"><?php echo ucfirst(htmlspecialchars($appointment['status'])); ?></span></td>
                                        <td><?php echo htmlspecialchars($appointment['created_at']); ?></td>
                                        <td>
                                            <form action="appointments.php" method="POST" class="d-inline-block mr-1">
                                                <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['id']); ?>">
                                                <select name="new_status" class="form-control form-control-sm d-inline-block w-auto" onchange="this.form.submit()">
                                                    <option value="pending" <?php echo ($appointment['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="confirmed" <?php echo ($appointment['status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="cancelled" <?php echo ($appointment['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                    <option value="completed" <?php echo ($appointment['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                            <a href="appointments.php?action=delete&id=<?php echo htmlspecialchars($appointment['id']); ?>" class="btn btn-sm btn-danger mt-1" onclick="return confirm('Are you sure you want to delete this appointment?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No appointments found <?php echo ($current_filter_status != 'all') ? 'with status "' . htmlspecialchars($current_filter_status) . '"' : ''; ?>.</p>
                <?php endif; ?>

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
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../js/script.js"></script>
</body>
</html>
<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>