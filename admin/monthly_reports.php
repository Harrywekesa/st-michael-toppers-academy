<!-- admin/monthly_reports.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'accountant') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Get filter parameters
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Get payments for the selected month
$stmt = $pdo->prepare("
    SELECT p.*, s.name as student_name, s.admission_no, c.name as class_name
    FROM payments p
    JOIN students s ON p.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE MONTH(p.payment_date) = ? AND YEAR(p.payment_date) = ?
    ORDER BY p.payment_date DESC, p.created_at DESC
");
$stmt->execute([$month, $year]);
$monthly_payments = $stmt->fetchAll();

// Calculate daily totals
$daily_totals = [];
foreach($monthly_payments as $payment) {
    $date = $payment['payment_date'];
    if (!isset($daily_totals[$date])) {
        $daily_totals[$date] = 0;
    }
    $daily_totals[$date] += $payment['amount_paid'];
}

// Calculate overall total
$total_amount = array_sum($daily_totals);

// Get month name for display
$month_name = date('F', mktime(0, 0, 0, $month, 10));
?>

<?php include 'includes/accountant_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/accountant_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Monthly Reports</h1>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Month</label>
                            <select class="form-control" name="month" required>
                                <?php for($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($month == $i) ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $i, 10)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Year</label>
                            <input type="number" class="form-control" name="year" min="2010" max="2030" value="<?php echo $year; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h6>Month</h6>
                            <h3><?php echo $month_name . ' ' . $year; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h6>Total Collections</h6>
                            <h3>KES <?php echo number_format($total_amount, 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h6>Payment Days</h6>
                            <h3><?php echo count($daily_totals); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Daily Collections - <?php echo $month_name . ' ' . $year; ?></h5>
                    <button class="btn btn-success" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
                <div class="card-body">
                    <?php if(empty($daily_totals)): ?>
                        <div class="alert alert-info">No payments recorded for this month.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th>Amount Collected (KES)</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $max_daily = max($daily_totals);
                                    foreach($daily_totals as $date => $amount): 
                                        $percentage = $max_daily > 0 ? ($amount / $max_daily) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($date)); ?></td>
                                        <td><?php echo date('l', strtotime($date)); ?></td>
                                        <td><?php echo number_format($amount, 2); ?></td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo number_format($percentage, 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-dark">
                                    <tr>
                                        <th colspan="2" class="text-end">Total:</th>
                                        <th>KES <?php echo number_format($total_amount, 2); ?></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div class="mt-4">
                            <h5>Payment Details</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Student</th>
                                            <th>Admission No</th>
                                            <th>Class</th>
                                            <th>Term</th>
                                            <th>Amount (KES)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($monthly_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo date('M j', strtotime($payment['payment_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($payment['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['admission_no']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['class_name'] ?? 'Unassigned'); ?></td>
                                            <td><?php echo htmlspecialchars($payment['term']); ?></td>
                                            <td><?php echo number_format($payment['amount_paid'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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