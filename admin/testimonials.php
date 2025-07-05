<?php
// dr_portfolio/admin/testimonials.php
session_start();

// Access control: Check if the user is logged in and is an admin/doctor
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'doctor')) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php'; // Adjust path for include

$message = '';
$message_type = ''; // 'success' or 'danger'

// --- Handle Form Submissions (Add, Edit, Update Status, Delete) ---

// Handle Add/Edit Testimonial
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_testimonial'])) {
    $patient_name = $_POST['patient_name'] ?? '';
    $testimonial_text = $_POST['testimonial_text'] ?? '';
    $rating = $_POST['rating'] ?? null;
    $status = $_POST['status'] ?? 'pending'; // Default status when adding/editing
    $testimonial_id = $_POST['testimonial_id'] ?? null; // For editing

    // Basic validation
    if (empty($patient_name) || empty($testimonial_text)) {
        $message = 'Patient Name and Testimonial cannot be empty.';
        $message_type = 'danger';
    } else {
        // Ensure rating is an integer or null
        $rating = ($rating !== '' && $rating !== null) ? (int)$rating : null;
        if ($rating < 1 || $rating > 5) {
             $rating = null; // Reset to null if out of range
        }


        if ($testimonial_id) {
            // Update existing testimonial
            $sql = "UPDATE testimonials SET patient_name = ?, testimonial_text = ?, rating = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $conn->prepare($sql);
            // 'ssisi' -> string, string, integer, string, integer
            $stmt->bind_param("ssisi", $patient_name, $testimonial_text, $rating, $status, $testimonial_id);
            if ($stmt->execute()) {
                $message = 'Testimonial updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating testimonial: ' . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            // Add new testimonial
            $sql = "INSERT INTO testimonials (patient_name, testimonial_text, rating, status) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            // 'ssis' -> string, string, integer, string
            $stmt->bind_param("ssis", $patient_name, $testimonial_text, $rating, $status);
            if ($stmt->execute()) {
                $message = 'Testimonial added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding testimonial: ' . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

// Handle Update Status Only (from the list table)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status_only'])) {
    $testimonial_id = $_POST['testimonial_id'] ?? null;
    $new_status = $_POST['new_status'] ?? '';

    if (!empty($testimonial_id) && in_array($new_status, ['pending', 'approved', 'rejected'])) {
        $sql = "UPDATE testimonials SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_status, $testimonial_id);
        if ($stmt->execute()) {
            $message = 'Testimonial status updated successfully!';
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


// Handle Delete Testimonial
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $testimonial_id = $_GET['id'];
    $sql = "DELETE FROM testimonials WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $testimonial_id);
    if ($stmt->execute()) {
        $message = 'Testimonial deleted successfully!';
        $message_type = 'success';
        // Redirect to clear the GET parameters after deletion
        header("Location: testimonials.php?message=" . urlencode($message) . "&type=" . $message_type);
        exit();
    } else {
        $message = 'Error deleting testimonial: ' . $stmt->error;
        $message_type = 'danger';
    }
    $stmt->close();
}

// Fetch message from GET parameters if redirected after deletion/update
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// --- Fetch Testimonials for Display ---
$testimonials = [];
$current_filter_status = $_GET['filter_status'] ?? 'all'; // Default filter

$sql = "SELECT * FROM testimonials";
$where_clauses = [];
$bind_params = [];
$bind_types = '';

// Apply filter if not 'all'
if ($current_filter_status != 'all') {
    $where_clauses[] = "status = ?";
    $bind_params[] = $current_filter_status;
    $bind_types .= 's';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($bind_params)) {
    $stmt->bind_param($bind_types, ...$bind_params);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $testimonials[] = $row;
    }
}
$stmt->close();

// --- Pre-fill form for editing ---
$edit_testimonial = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $testimonial_id = $_GET['id'];
    $sql = "SELECT * FROM testimonials WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $testimonial_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_testimonial = $result->fetch_assoc();
    } else {
        $message = 'Testimonial not found for editing.';
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
    <title>Manage Testimonials - Admin Dashboard</title>
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
        .badge-approved { background-color: #28a745; color: #fff; } /* Green */
        .badge-rejected { background-color: #dc3545; color: #fff; } /* Red */
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
                <a href="appointments.php" class="list-group-item list-group-item-action bg-dark text-white">Manage Appointments</a>
                <a href="testimonials.php" class="list-group-item list-group-item-action bg-dark text-white active">Manage Testimonials</a>
                <a href="logout.php" class="list-group-item list-group-item-action bg-dark text-white">Logout</a>
            </div>
        </div>
        <div id="page-content-wrapper" class="flex-grow-1">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="menu-toggle">Toggle Menu</button>
                    <h5 class="my-2 ml-3">Manage Testimonials</h5>
                </div>
            </nav>

            <div class="container-fluid py-4">
                <h1 class="mt-4">Manage Testimonials</h1>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <?php echo $edit_testimonial ? 'Edit Testimonial' : 'Add New Testimonial'; ?>
                    </div>
                    <div class="card-body">
                        <form action="testimonials.php" method="POST">
                            <?php if ($edit_testimonial): ?>
                                <input type="hidden" name="testimonial_id" value="<?php echo htmlspecialchars($edit_testimonial['id']); ?>">
                            <?php endif; ?>
                            <div class="form-group">
                                <label for="patient_name">Patient Name</label>
                                <input type="text" class="form-control" id="patient_name" name="patient_name" value="<?php echo htmlspecialchars($edit_testimonial['patient_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="testimonial_text">Testimonial</label>
                                <textarea class="form-control" id="testimonial_text" name="testimonial_text" rows="5" required><?php echo htmlspecialchars($edit_testimonial['testimonial_text'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="rating">Rating (1-5 Stars)</label>
                                <input type="number" class="form-control" id="rating" name="rating" min="1" max="5" value="<?php echo htmlspecialchars($edit_testimonial['rating'] ?? ''); ?>" placeholder="Optional (1-5)">
                            </div>
                             <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="pending" <?php echo ($edit_testimonial['status'] ?? 'pending') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo ($edit_testimonial['status'] ?? '') == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo ($edit_testimonial['status'] ?? '') == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <button type="submit" name="submit_testimonial" class="btn btn-primary">
                                <?php echo $edit_testimonial ? 'Update Testimonial' : 'Add Testimonial'; ?>
                            </button>
                            <?php if ($edit_testimonial): ?>
                                <a href="testimonials.php" class="btn btn-secondary ml-2">Cancel Edit</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="mb-3">
                    <form action="testimonials.php" method="GET" class="form-inline">
                        <label for="filter_status" class="mr-2">Filter by Status:</label>
                        <select name="filter_status" id="filter_status" class="form-control mr-2" onchange="this.form.submit()">
                            <option value="all" <?php echo ($current_filter_status == 'all') ? 'selected' : ''; ?>>All</option>
                            <option value="pending" <?php echo ($current_filter_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo ($current_filter_status == 'approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo ($current_filter_status == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <noscript><button type="submit" class="btn btn-primary">Filter</button></noscript>
                    </form>
                </div>


                <h2 class="mt-4">Existing Testimonials</h2>
                <?php if (!empty($testimonials)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient Name</th>
                                    <th>Testimonial</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($testimonials as $testimonial): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($testimonial['id']); ?></td>
                                        <td><?php echo htmlspecialchars($testimonial['patient_name']); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($testimonial['testimonial_text'])); ?></td>
                                        <td><?php echo htmlspecialchars($testimonial['rating'] ?? 'N/A'); ?></td>
                                        <td><span class="badge badge-pill badge-<?php echo htmlspecialchars($testimonial['status']); ?>"><?php echo ucfirst(htmlspecialchars($testimonial['status'])); ?></span></td>
                                        <td><?php echo htmlspecialchars($testimonial['created_at']); ?></td>
                                        <td>
                                            <form action="testimonials.php" method="POST" class="d-inline-block mr-1">
                                                <input type="hidden" name="testimonial_id" value="<?php echo htmlspecialchars($testimonial['id']); ?>">
                                                <select name="new_status" class="form-control form-control-sm d-inline-block w-auto" onchange="this.form.submit()">
                                                    <option value="pending" <?php echo ($testimonial['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="approved" <?php echo ($testimonial['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                                    <option value="rejected" <?php echo ($testimonial['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                </select>
                                                <input type="hidden" name="update_status_only" value="1">
                                            </form>
                                            <a href="testimonials.php?action=edit&id=<?php echo htmlspecialchars($testimonial['id']); ?>" class="btn btn-sm btn-info mt-1">Edit</a>
                                            <a href="testimonials.php?action=delete&id=<?php echo htmlspecialchars($testimonial['id']); ?>" class="btn btn-sm btn-danger mt-1" onclick="return confirm('Are you sure you want to delete this testimonial?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No testimonials found <?php echo ($current_filter_status != 'all') ? 'with status "' . htmlspecialchars($current_filter_status) . '"' : ''; ?>.</p>
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