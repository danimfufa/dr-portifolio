<?php
// dr_portfolio/admin/services.php
session_start();

// Access control: Check if the user is logged in and is an admin/doctor
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'doctor')) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php'; // Adjust path for include

$message = '';
$message_type = ''; // 'success' or 'danger'

// --- Handle Form Submissions (Add, Edit, Delete) ---

// Handle Add/Edit Service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_service'])) {
    $service_name = $_POST['service_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $duration_minutes = $_POST['duration_minutes'] ?? null; // Can be null
    $price = $_POST['price'] ?? null; // Can be null
    $service_id = $_POST['service_id'] ?? null; // For editing

    // Basic validation
    if (empty($service_name) || empty($description)) {
        $message = 'Service Name and Description cannot be empty.';
        $message_type = 'danger';
    } else {
        // Prepare optional fields for DB
        $duration_minutes = ($duration_minutes !== '' && $duration_minutes !== null) ? (int)$duration_minutes : null;
        $price = ($price !== '' && $price !== null) ? (float)$price : null;

        if ($service_id) {
            // Update existing service
            $sql = "UPDATE services SET service_name = ?, description = ?, duration_minutes = ?, price = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $conn->prepare($sql);
            // 'ssidi' -> string, string, integer, double, integer
            $stmt->bind_param("ssidi", $service_name, $description, $duration_minutes, $price, $service_id);
            if ($stmt->execute()) {
                $message = 'Service updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating service: ' . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            // Add new service
            $sql = "INSERT INTO services (service_name, description, duration_minutes, price) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            // 'ssid' -> string, string, integer, double
            $stmt->bind_param("ssid", $service_name, $description, $duration_minutes, $price);
            if ($stmt->execute()) {
                $message = 'Service added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding service: ' . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

// Handle Delete Service
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $service_id = $_GET['id'];
    $sql = "DELETE FROM services WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $service_id);
    if ($stmt->execute()) {
        $message = 'Service deleted successfully!';
        $message_type = 'success';
        // Redirect to clear the GET parameters after deletion
        header("Location: services.php?message=" . urlencode($message) . "&type=" . $message_type);
        exit();
    } else {
        $message = 'Error deleting service: ' . $stmt->error;
        $message_type = 'danger';
    }
    $stmt->close();
}

// Fetch message from GET parameters if redirected after deletion
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// --- Fetch Services for Display ---
$services = [];
$sql = "SELECT * FROM services ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

// --- Pre-fill form for editing ---
$edit_service = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $service_id = $_GET['id'];
    $sql = "SELECT * FROM services WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_service = $result->fetch_assoc();
    } else {
        $message = 'Service not found for editing.';
        $message_type = 'danger';
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - Admin Dashboard</title>
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
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <div class="bg-dark border-right admin-sidebar" id="sidebar-wrapper">
            <div class="sidebar-heading text-white py-4">Dr. <?php echo htmlspecialchars($_SESSION['username']); ?>'s Admin</div>
            <div class="list-group list-group-flush">
                <a href="index.php" class="list-group-item list-group-item-action bg-dark text-white">Dashboard</a>
                <a href="profile.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Profile</a>
                <a href="services.php" class="list-group-item list-group-item-action bg-dark text-white active">Manage Services</a>
                <a href="appointments.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Appointments</a>
                <a href="testimonials.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Testimonials</a>
                <a href="logout.php" class="list-group-item list-group-item-action bg-dark text-white">Logout</a>
            </div>
        </div>
        <div id="page-content-wrapper" class="flex-grow-1">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="menu-toggle">Toggle Menu</button>
                    <h5 class="my-2 ml-3">Manage Services</h5>
                </div>
            </nav>

            <div class="container-fluid py-4">
                <h1 class="mt-4">Manage Services</h1>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <?php echo $edit_service ? 'Edit Service' : 'Add New Service'; ?>
                    </div>
                    <div class="card-body">
                        <form action="services.php" method="POST">
                            <?php if ($edit_service): ?>
                                <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($edit_service['id']); ?>">
                            <?php endif; ?>
                            <div class="form-group">
                                <label for="service_name">Service Name</label>
                                <input type="text" class="form-control" id="service_name" name="service_name" value="<?php echo htmlspecialchars($edit_service['service_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($edit_service['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="duration_minutes">Duration (minutes)</label>
                                <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" value="<?php echo htmlspecialchars($edit_service['duration_minutes'] ?? ''); ?>" placeholder="Optional">
                            </div>
                            <div class="form-group">
                                <label for="price">Price ($)</label>
                                <input type="text" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($edit_service['price'] ?? ''); ?>" placeholder="Optional (e.g., 99.99)">
                            </div>
                            <button type="submit" name="submit_service" class="btn btn-primary">
                                <?php echo $edit_service ? 'Update Service' : 'Add Service'; ?>
                            </button>
                            <?php if ($edit_service): ?>
                                <a href="services.php" class="btn btn-secondary ml-2">Cancel Edit</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <h2 class="mt-4">Existing Services</h2>
                <?php if (!empty($services)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Service Name</th>
                                    <th>Description</th>
                                    <th>Duration</th>
                                    <th>Price</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($service['id']); ?></td>
                                        <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($service['description'])); ?></td>
                                        <td><?php echo htmlspecialchars($service['duration_minutes'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($service['price'] !== null ? '$' . number_format($service['price'], 2) : 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($service['created_at']); ?></td>
                                        <td><?php echo htmlspecialchars($service['updated_at']); ?></td>
                                        <td>
                                            <a href="services.php?action=edit&id=<?php echo htmlspecialchars($service['id']); ?>" class="btn btn-sm btn-info">Edit</a>
                                            <a href="services.php?action=delete&id=<?php echo htmlspecialchars($service['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this service?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No services added yet. Use the form above to add your first service.</p>
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
</body>
</html>
<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>