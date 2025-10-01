<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'accountant') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Handle delete payment
if (isset($_GET['delete_payment'])) {
    $payment_id = $_GET['delete_payment'];
    try {
        $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
        $stmt->execute([$payment_id]);
        $success = "Payment record deleted successfully!";
    } catch(PDOException $e) {
        $error = "Error deleting payment: " . $e->getMessage();
    }
}

// Get filter parameters
$student_id = $_GET['student_id'] ?? '';
$term = $_GET['term'] ?? '';
$year = $_GET['year'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build query based on filters
$query = "
    SELECT p.*, s.name as student_name, s.admission_no, c.name as class_name
    FROM payments p
    JOIN students s ON p.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE 1=1
";

$params = [];

if ($student_id) {
    $query .= " AND p.student_id = ?";
    $params[] = $student_id;
}

if ($term) {
    $query .= " AND p.term = ?";
    $params[] = $term;
}

if ($year) {
    $query .= " AND p.year = ?";
    $params[] = $year;
}

if ($start_date) {
    $query .= " AND p.payment_date >= ?";
    $params[] = $start_date;
}

if ($end_date) {
    $query .= " AND p.payment_date <= ?";
    $params[] = $end_date;
}

$query .= " ORDER BY p.payment_date DESC, p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Fetch students for filter dropdown
$students = $pdo->query("
    SELECT s.id, s.name, s.admission_no, c.name as class_name 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    WHERE s.status = 'active' 
    ORDER BY s.name
")->fetchAll();

// Calculate totals
$total_amount = 0;
foreach($payments as $payment) {
    $total_amount += $payment['amount_paid'];
}
?>

<?php include 'includes/accountant_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/accountant_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Payment Records</h1>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Filter Payments</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Student</label>
                            <select class="form-control" name="student_id">
                                <option value="">All Students</option>
                                <?php foreach($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>" <?php echo ($student_id == $student['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['name'] . " (" . $student['admission_no'] . ")"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Term</label>
                            <select class="form-control" name="term">
                                <option value="">All Terms</option>
                                <option value="Term 1" <?php echo ($term == 'Term 1') ? 'selected' : ''; ?>>Term 1</option>
                                <option value="Term 2" <?php echo ($term == 'Term 2') ? 'selected' : ''; ?>>Term 2</option>
                                <option value="Term 3" <?php echo ($term == 'Term 3') ? 'selected' : ''; ?>>Term 3</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Year</label>
                            <input type="number" class="form-control" name="year" min="2010" max="2030" value="<?php echo $year; ?>" placeholder="Year">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Payment Records</h5>
                    <div>
                        <span class="badge bg-success">Total: KES <?php echo number_format($total_amount, 2); ?></span>
                        <button class="btn btn-success ms-2" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(empty($payments)): ?>
                        <div class="alert alert-info">No payment records found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Admission No</th>
                                        <th>Class</th>
                                        <th>Term</th>
                                        <th>Year</th>
                                        <th>Amount (KES)</th>
                                        <th>Payment Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['admission_no']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['class_name'] ?? 'Unassigned'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['term']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['year']); ?></td>
                                        <td><?php echo number_format($payment['amount_paid'], 2); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                        <td>
                                            <a href="?delete_payment=<?php echo $payment['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this payment record?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
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

<style>
@media print {
    .sidebar, .btn, form, .alert {
        display: none !important;
    }
    body {
        padding: 0 !important;
    }
}
</style>

<?php include 'includes/accountant_footer.php'; ?>