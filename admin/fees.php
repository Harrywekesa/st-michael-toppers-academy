<!-- admin/fees.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'accountant')) {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['set_fee'])) {
        $term = $_POST['term'];
        $year = $_POST['year'];
        $amount = $_POST['amount'];
        
        try {
            // Check if fee structure already exists
            $stmt = $pdo->prepare("SELECT id FROM fees WHERE term=? AND year=?");
            $stmt->execute([$term, $year]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing fee
                $stmt = $pdo->prepare("UPDATE fees SET amount=? WHERE id=?");
                $stmt->execute([$amount, $existing['id']]);
            } else {
                // Insert new fee
                $stmt = $pdo->prepare("INSERT INTO fees (term, year, amount) VALUES (?, ?, ?)");
                $stmt->execute([$term, $year, $amount]);
            }
            $success = "Fee structure updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating fee structure: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['record_payment'])) {
        $student_id = $_POST['student_id'];
        $term = $_POST['term'];
        $year = $_POST['year'];
        $amount_paid = $_POST['amount_paid'];
        $payment_date = $_POST['payment_date'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO payments (student_id, term, year, amount_paid, payment_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $term, $year, $amount_paid, $payment_date]);
            $success = "Payment recorded successfully!";
        } catch(PDOException $e) {
            $error = "Error recording payment: " . $e->getMessage();
        }
    }
}

// Fetch fee structures
$fee_structures = $pdo->query("SELECT * FROM fees ORDER BY year DESC, term")->fetchAll();

// Fetch students for payment form
$students = $pdo->query("
    SELECT s.id, s.name, s.admission_no, c.name as class_name 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    WHERE s.status = 'active' 
    ORDER BY s.name
")->fetchAll();

// Get current fee structure
$current_year = date('Y');
$current_term = 'Term ' . ceil(date('n') / 4);
$stmt = $pdo->prepare("SELECT * FROM fees WHERE term=? AND year=?");
$stmt->execute([$current_term, $current_year]);
$current_fee = $stmt->fetch();
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php 
        if ($_SESSION['user_role'] == 'admin') {
            include 'includes/admin_sidebar.php';
        } else {
            include 'includes/accountant_sidebar.php';
        }
        ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Fee Management</h1>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Set Fee Structure</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="set_fee" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label">Term</label>
                                    <select class="form-control" name="term" required>
                                        <option value="">Select Term</option>
                                        <option value="Term 1">Term 1</option>
                                        <option value="Term 2">Term 2</option>
                                        <option value="Term 3">Term 3</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Year</label>
                                    <input type="number" class="form-control" name="year" min="2010" max="2030" value="<?php echo date('Y'); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Amount (KES)</label>
                                    <input type="number" class="form-control" name="amount" min="0" step="0.01" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Set Fee</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>Current Fee Structure</h5>
                        </div>
                        <div class="card-body">
                            <?php if($current_fee): ?>
                                <p><strong>Term:</strong> <?php echo htmlspecialchars($current_fee['term']); ?></p>
                                <p><strong>Year:</strong> <?php echo htmlspecialchars($current_fee['year']); ?></p>
                                <p><strong>Amount:</strong> KES <?php echo number_format($current_fee['amount'], 2); ?></p>
                            <?php else: ?>
                                <p class="text-muted">No fee structure set for <?php echo $current_term . ' ' . $current_year; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Record Payment</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="record_payment" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label">Student</label>
                                    <select class="form-control" name="student_id" required>
                                        <option value="">Select Student</option>
                                        <?php foreach($students as $student): ?>
                                            <option value="<?php echo $student['id']; ?>">
                                                <?php echo htmlspecialchars($student['name'] . " (" . $student['admission_no'] . ") - " . ($student['class_name'] ?? 'Unassigned')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Term</label>
                                    <select class="form-control" name="term" required>
                                        <option value="">Select Term</option>
                                        <option value="Term 1">Term 1</option>
                                        <option value="Term 2">Term 2</option>
                                        <option value="Term 3">Term 3</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Year</label>
                                    <input type="number" class="form-control" name="year" min="2010" max="2030" value="<?php echo date('Y'); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Amount Paid (KES)</label>
                                    <input type="number" class="form-control" name="amount_paid" min="0" step="0.01" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Payment Date</label>
                                    <input type="date" class="form-control" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                
                                <button type="submit" class="btn btn-success">Record Payment</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Fee Structures</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Term</th>
                                    <th>Year</th>
                                    <th>Amount (KES)</th>
                                    <th>Date Set</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($fee_structures as $fee): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fee['term']); ?></td>
                                    <td><?php echo htmlspecialchars($fee['year']); ?></td>
                                    <td><?php echo number_format($fee['amount'], 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($fee['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>