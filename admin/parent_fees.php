<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'parent') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Get student ID from URL or default to first child
$student_id = $_GET['student_id'] ?? 0;

// Verify that the student belongs to this parent
if ($student_id) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND parent_id = ?");
    $stmt->execute([$student_id, $_SESSION['user_id']]);
    $student = $stmt->fetch();
    
    if (!$student) {
        header('Location: parent_fees.php');
        exit();
    }
}

// Get parent's children
$stmt = $pdo->prepare("SELECT * FROM students WHERE parent_id = ? AND status = 'active'");
$stmt->execute([$_SESSION['user_id']]);
$children = $stmt->fetchAll();

// Get payment data if student is selected
if ($student_id) {
    // Get payment history for this student
    $stmt = $pdo->prepare("
        SELECT p.*, f.amount as required_fee
        FROM payments p
        LEFT JOIN fees f ON p.term = f.term AND p.year = f.year
        WHERE p.student_id = ?
        ORDER BY p.year DESC, p.term
    ");
    $stmt->execute([$student_id]);
    $payments = $stmt->fetchAll();
    
    // Calculate total paid and outstanding
    $total_paid = 0;
    $balance = 0;
    
    foreach($payments as $payment) {
        $total_paid += $payment['amount_paid'];
        if ($payment['required_fee']) {
            $balance += ($payment['required_fee'] - $payment['amount_paid']);
        }
    }
}
?>

<?php include 'includes/parent_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/parent_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Fee Records</h1>
            </div>
            
            <?php if(empty($children)): ?>
                <div class="alert alert-info">
                    <p>You don't have any children registered in the system.</p>
                </div>
            <?php else: ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET">
                            <div class="row">
                                <div class="col-md-8">
                                    <label class="form-label">Select Child</label>
                                    <select class="form-control" name="student_id" onchange="this.form.submit()">
                                        <option value="">Select a child</option>
                                        <?php foreach($children as $child): ?>
                                            <option value="<?php echo $child['id']; ?>" <?php echo ($student_id == $child['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($child['name'] . " (" . $child['admission_no'] . ")"); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <a href="parent_children.php" class="btn btn-secondary w-100">Back to Children</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if($student_id && isset($student)): ?>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6>Total Paid (KES)</h6>
                                    <h3><?php echo number_format($total_paid, 2); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-dark">
                                <div class="card-body text-center">
                                    <h6>Outstanding Balance (KES)</h6>
                                    <h3><?php echo number_format(max(0, $balance), 2); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h6>Payment Status</h6>
                                    <h3><?php echo $balance <= 0 ? 'Fully Paid' : 'Pending'; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5><?php echo htmlspecialchars($student['name']); ?>'s Fee Records</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($payments)): ?>
                                <div class="alert alert-info">No payment records found.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Term</th>
                                                <th>Year</th>
                                                <th>Required Fee (KES)</th>
                                                <th>Amount Paid (KES)</th>
                                                <th>Outstanding (KES)</th>
                                                <th>Payment Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($payments as $payment): 
                                                $outstanding = $payment['required_fee'] ? 
                                                    ($payment['required_fee'] - $payment['amount_paid']) : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['term']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['year']); ?></td>
                                                <td><?php echo $payment['required_fee'] ? number_format($payment['required_fee'], 2) : 'N/A'; ?></td>
                                                <td><?php echo number_format($payment['amount_paid'], 2); ?></td>
                                                <td><?php echo number_format(max(0, $outstanding), 2); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif($student_id): ?>
                    <div class="alert alert-warning">
                        <p>Please select a child to view fee records.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/parent_footer.php'; ?>