<!-- admin/accountant_dashboard.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'accountant') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';
?>

<?php include 'includes/accountant_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/accountant_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Accountant Dashboard</h1>
                <div>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
            </div>
            
            <!-- Financial Summary Cards -->
            <div class="row">
                <?php
                // Get current academic year and term
                $current_year = date('Y');
                $current_month = date('n');
                $current_term = 'Term 1';
                if ($current_month >= 5 && $current_month <= 8) {
                    $current_term = 'Term 2';
                } elseif ($current_month >= 9) {
                    $current_term = 'Term 3';
                }
                
                // Get fee structure for current term
                $stmt = $pdo->prepare("SELECT amount FROM fees WHERE term=? AND year=?");
                $stmt->execute([$current_term, $current_year]);
                $fee_structure = $stmt->fetch();
                $required_fee = $fee_structure ? $fee_structure['amount'] : 0;
                
                // Get total students
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
                $total_students = $stmt->fetch()['count'];
                
                // Get total expected fees
                $total_expected = $total_students * $required_fee;
                
                // Get total collected this term
                $stmt = $pdo->prepare("
                    SELECT SUM(amount_paid) as total_collected 
                    FROM payments 
                    WHERE term=? AND year=?
                ");
                $stmt->execute([$current_term, $current_year]);
                $collected = $stmt->fetch()['total_collected'] ?? 0;
                
                // Get outstanding fees
                $outstanding = $total_expected - $collected;
                
                // Get today's collections
                $stmt = $pdo->prepare("
                    SELECT SUM(amount_paid) as today_collected 
                    FROM payments 
                    WHERE payment_date = CURDATE()
                ");
                $stmt->execute();
                $today_collected = $stmt->fetch()['today_collected'] ?? 0;
                ?>
                
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Students</h5>
                            <h2><?php echo $total_students; ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Collected This Term</h5>
                            <h2>KES <?php echo number_format($collected, 2); ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Outstanding Fees</h5>
                            <h2>KES <?php echo number_format($outstanding, 2); ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Today's Collections</h5>
                            <h2>KES <?php echo number_format($today_collected, 2); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Payments -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-receipt"></i> Recent Payments</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $stmt = $pdo->query("
                                SELECT p.*, s.name as student_name, s.admission_no
                                FROM payments p
                                JOIN students s ON p.student_id = s.id
                                ORDER BY p.payment_date DESC, p.created_at DESC
                                LIMIT 10
                            ");
                            $recent_payments = $stmt->fetchAll();
                            ?>
                            
                            <?php if(empty($recent_payments)): ?>
                                <p class="text-muted">No recent payments recorded.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Admission No</th>
                                                <th>Amount</th>
                                                <th>Term</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($recent_payments as $payment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['admission_no']); ?></td>
                                                <td>KES <?php echo number_format($payment['amount_paid'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($payment['term']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="fees.php" class="btn btn-primary">
                                    <i class="fas fa-money-bill-wave"></i> Manage Fees
                                </a>
                                <a href="fee_reports.php" class="btn btn-success">
                                    <i class="fas fa-chart-bar"></i> Generate Reports
                                </a>
                                <a href="payments.php" class="btn btn-warning">
                                    <i class="fas fa-receipt"></i> View All Payments
                                </a>
                                <a href="outstanding_fees.php" class="btn btn-danger">
                                    <i class="fas fa-exclamation-triangle"></i> Outstanding Fees
                                </a>
                                <a href="fee_structure.php" class="btn btn-info">
                                    <i class="fas fa-cogs"></i> Fee Structure
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Current Term Summary -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5><i class="fas fa-calendar-alt"></i> <?php echo $current_term . ' ' . $current_year; ?></h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Fee per Student:</strong> KES <?php echo number_format($required_fee, 2); ?></p>
                            <p><strong>Total Expected:</strong> KES <?php echo number_format($total_expected, 2); ?></p>
                            <p><strong>Collection Rate:</strong> <?php echo $total_expected > 0 ? number_format(($collected / $total_expected) * 100, 1) : 0; ?>%</p>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $total_expected > 0 ? ($collected / $total_expected) * 100 : 0; ?>%" aria-valuenow="<?php echo $total_expected > 0 ? ($collected / $total_expected) * 100 : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/accountant_footer.php'; ?>