<!-- admin/applications.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve_application'])) {
        $application_id = $_POST['application_id'];
        $reporting_date = $_POST['reporting_date'];
        $additional_requirements = $_POST['additional_requirements'];
        
        try {
            $stmt = $pdo->prepare("UPDATE student_applications SET 
                status='Approved', 
                approval_date=NOW(), 
                reporting_date=?, 
                additional_requirements=? 
                WHERE id=?");
            $stmt->execute([$reporting_date, $additional_requirements, $application_id]);
            
            // Here you would typically send an email notification to the parent
            $success = "Application approved successfully!";
        } catch(PDOException $e) {
            $error = "Error approving application: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['reject_application'])) {
        $application_id = $_POST['application_id'];
        $rejection_reason = $_POST['rejection_reason'];
        
        try {
            $stmt = $pdo->prepare("UPDATE student_applications SET 
                status='Rejected', 
                approval_date=NOW(),
                additional_requirements=? 
                WHERE id=?");
            $stmt->execute([$rejection_reason, $application_id]);
            
            $success = "Application rejected!";
        } catch(PDOException $e) {
            $error = "Error rejecting application: " . $e->getMessage();
        }
    }
}

// Fetch applications
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM student_applications WHERE 1=1";
$params = [];

if ($status_filter != 'all') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $query .= " AND (student_name LIKE ? OR parent_name LIKE ? OR parent_email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll();
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Student Applications</h1>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Status Filter</label>
                            <select class="form-control" name="status" onchange="this.form.submit()">
                                <option value="all" <?php echo ($status_filter == 'all') ? 'selected' : ''; ?>>All Applications</option>
                                <option value="Pending" <?php echo ($status_filter == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Approved" <?php echo ($status_filter == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo ($status_filter == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by student or parent name...">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h6>Total Applications</h6>
                            <h3><?php echo count($applications); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body text-center">
                            <h6>Pending</h6>
                            <?php
                            $pending_count = 0;
                            foreach($applications as $app) {
                                if($app['status'] == 'Pending') $pending_count++;
                            }
                            ?>
                            <h3><?php echo $pending_count; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h6>Approved</h6>
                            <?php
                            $approved_count = 0;
                            foreach($applications as $app) {
                                if($app['status'] == 'Approved') $approved_count++;
                            }
                            ?>
                            <h3><?php echo $approved_count; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h6>Rejected</h6>
                            <?php
                            $rejected_count = 0;
                            foreach($applications as $app) {
                                if($app['status'] == 'Rejected') $rejected_count++;
                            }
                            ?>
                            <h3><?php echo $rejected_count; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Applications List</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($applications)): ?>
                        <div class="alert alert-info">No applications found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Grade</th>
                                        <th>Parent</th>
                                        <th>Date Applied</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($applications as $application): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($application['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($application['grade_applying_for']); ?></td>
                                        <td><?php echo htmlspecialchars($application['parent_name']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($application['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $application['status'] == 'Pending' ? 'warning' : 
                                                    ($application['status'] == 'Approved' ? 'success' : 'danger'); ?>">
                                                <?php echo htmlspecialchars($application['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewApplication(<?php echo $application['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- View Application Modal -->
<div class="modal fade" id="viewApplicationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Application Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="applicationDetails">
                <!-- Application details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewApplication(id) {
    // In a real implementation, you would fetch application details via AJAX
    // For now, we'll redirect to a detailed view page
    window.location.href = 'application_details.php?id=' + id;
}

function approveApplication(id) {
    document.getElementById('approve_application_id').value = id;
    new bootstrap.Modal(document.getElementById('approveApplicationModal')).show();
}

function rejectApplication(id) {
    document.getElementById('reject_application_id').value = id;
    new bootstrap.Modal(document.getElementById('rejectApplicationModal')).show();
}
</script>

<?php include 'includes/admin_footer.php'; ?>