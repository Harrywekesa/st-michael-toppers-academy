<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'accountant') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Get current academic year and term
$current_year = date('Y');
$current_term = 'Term ' . ceil(date('n') / 4);

// Get fee structure for current term
$stmt = $pdo->prepare("SELECT amount FROM fees WHERE term=? AND year=?");
$stmt->execute([$current_term, $current_year]);
$fee_structure = $stmt->fetch();
$required_fee = $fee_structure ? $fee_structure['amount'] : 0;

// Get students with outstanding fees
$stmt = $pdo->prepare("
    SELECT s.id, s.admission_no, s.name, c.name as class_name,
           COALESCE(SUM(p.amount_paid), 0) as total_paid,
           ? as required_fee,
           (? - COALESCE(SUM(p.amount_paid), 0)) as outstanding
    FROM students s
    LEFT JOIN payments p ON s.id = p.student_id AND p.term = ? AND p.year = ?
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE s.status = 'active'
    GROUP BY s.id, s.admission_no, s.name, c.name
    HAVING outstanding > 0
    ORDER BY outstanding DESC
");
$stmt->execute([$required_fee, $required_fee, $current_term, $current_year]);
$students_with_outstanding = $stmt->fetchAll();

// Calculate totals
$total_students = count($students_with_outstanding);
$total_outstanding = 0;
foreach($students_with_outstanding as $student) {
    $total_outstanding += $student['outstanding'];
}
?>

<?php include 'includes/accountant_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/accountant_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Outstanding Fees</h1>
                <button class="btn btn-success" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h6>Students with Outstanding Fees</h6>
                            <h3><?php echo $total_students; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-dark">
                        <div class="card-body text-center">
                            <h6>Total Outstanding (KES)</h6>
                            <h3><?php echo number_format($total_outstanding, 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h6>Current Term</h6>
                            <h3><?php echo $current_term . ' ' . $current_year; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Students with Outstanding Fees - <?php echo $current_term . ' ' . $current_year; ?></h5>
                </div>
                <div class="card-body">
                    <?php if(empty($students_with_outstanding)): ?>
                        <div class="alert alert-success">All students have paid their fees in full!</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Admission No</th>
                                        <th>Student Name</th>
                                        <th>Class</th>
                                        <th>Required Fee (KES)</th>
                                        <th>Total Paid (KES)</th>
                                        <th>Outstanding (KES)</th>
                                        <th>Percentage Paid</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($students_with_outstanding as $student): 
                                        $percentage_paid = $student['required_fee'] > 0 ? 
                                            ($student['total_paid'] / $student['required_fee']) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['class_name'] ?? 'Unassigned'); ?></td>
                                        <td><?php echo number_format($student['required_fee'], 2); ?></td>
                                        <td><?php echo number_format($student['total_paid'], 2); ?></td>
                                        <td><?php echo number_format($student['outstanding'], 2); ?></td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage_paid; ?>%" aria-valuenow="<?php echo $percentage_paid; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo number_format($percentage_paid, 1); ?>%
                                                </div>
                                            </div>
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