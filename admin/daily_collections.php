<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'accountant') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Get filter parameters
$date = $_GET['date'] ?? date('Y-m-d');

// Get payments for the selected date
$stmt = $pdo->prepare("
    SELECT p.*, s.name as student_name, s.admission_no, c.name as class_name
    FROM payments p
    JOIN students s ON p.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE p.payment_date = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$date]);
$daily_payments = $stmt->fetchAll();

// Calculate total for the day
$total_amount = 0;
foreach($daily_payments as $payment) {
    $total_amount += $payment['amount_paid'];
}
?>

<?php include 'includes/accountant_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/accountant_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Daily Collections</h1>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Select Date</label>
                            <input type="date" class="form-control" name="date" value="<?php echo $date; ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">View Collections</button>
                        </div>
                        <div class="col-md-6 text-end">
                            <h4 class="mt-4">Total Collections: <span class="text-success">KES <?php echo number_format($total_amount, 2); ?></span></h4>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Payments on <?php echo date('M j, Y', strtotime($date)); ?></h5>
                    <button class="btn btn-success" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
                <div class="card-body">
                    <?php if(empty($daily_payments)): ?>
                        <div class="alert alert-info">No payments recorded for this date.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Student</th>
                                        <th>Admission No</th>
                                        <th>Class</th>
                                        <th>Term</th>
                                        <th>Year</th>
                                        <th>Amount (KES)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($daily_payments as $payment): ?>
                                    <tr>
                                        <td><?php echo date('g:i A', strtotime($payment['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($payment['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['admission_no']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['class_name'] ?? 'Unassigned'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['term']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['year']); ?></td>
                                        <td><?php echo number_format($payment['amount_paid'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-dark">
                                    <tr>
                                        <th colspan="6" class="text-end">Total:</th>
                                        <th>KES <?php echo number_format($total_amount, 2); ?></th>
                                    </tr>
                                </tfoot>
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