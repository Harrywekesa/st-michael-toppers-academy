<?php 
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'accountant')) {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Initialize variables
$class_id = '';
$term = '';
$year = date('Y');

// Fetch classes
$classes = $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll();

// Get report data if parameters are provided
if (isset($_GET['class_id']) && isset($_GET['term']) && isset($_GET['year'])) {
    $class_id = $_GET['class_id'];
    $term = $_GET['term'];
    $year = $_GET['year'];
    
    // Get fee structure for the term
    $stmt = $pdo->prepare("SELECT amount FROM fees WHERE term=? AND year=?");
    $stmt->execute([$term, $year]);
    $fee_structure = $stmt->fetch();
    $required_fee = $fee_structure ? $fee_structure['amount'] : 0;
    
    // Get students and their payment status
    $stmt = $pdo->prepare("
        SELECT s.id, s.admission_no, s.name, 
               COALESCE(SUM(p.amount_paid), 0) as total_paid
        FROM students s
        LEFT JOIN payments p ON s.id = p.student_id AND p.term = ? AND p.year = ?
        WHERE s.class_id = ? AND s.status = 'active'
        GROUP BY s.id, s.admission_no, s.name
        ORDER BY s.name
    ");
    $stmt->execute([$term, $year, $class_id]);
    $students = $stmt->fetchAll();
    
    // Calculate statistics
    $total_students = count($students);
    $paid_students = 0;
    $total_paid = 0;
    $total_outstanding = 0;
    
    foreach($students as $student) {
        $total_paid += $student['total_paid'];
        $outstanding = $required_fee - $student['total_paid'];
        if ($outstanding <= 0) {
            $paid_students++;
        }
        $total_outstanding += max(0, $outstanding);
    }
}
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
                <h1 class="h2">Fee Reports</h1>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Class</label>
                            <select class="form-control" name="class_id" required>
                                <option value="">Select Class</option>
                                <?php foreach($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo ($class_id == $class['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Term</label>
                            <select class="form-control" name="term" required>
                                <option value="">Select Term</option>
                                <option value="Term 1" <?php echo ($term == 'Term 1') ? 'selected' : ''; ?>>Term 1</option>
                                <option value="Term 2" <?php echo ($term == 'Term 2') ? 'selected' : ''; ?>>Term 2</option>
                                <option value="Term 3" <?php echo ($term == 'Term 3') ? 'selected' : ''; ?>>Term 3</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Year</label>
                            <input type="number" class="form-control" name="year" min="2010" max="2030" value="<?php echo $year; ?>" required>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if(isset($students)): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>
                        <?php 
                            $class_name = '';
                            foreach($classes as $class) {
                                if($class['id'] == $class_id) $class_name = $class['name'];
                            }
                            echo "Fee Report: $class_name ($term $year)";
                        ?>
                    </h5>
                    <button class="btn btn-success" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h6>Total Students</h6>
                                    <h3><?php echo $total_students; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6>Paid Students</h6>
                                    <h3><?php echo $paid_students; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body text-center">
                                    <h6>Total Paid (KES)</h6>
                                    <h3><?php echo number_format($total_paid, 2); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h6>Total Outstanding (KES)</h6>
                                    <h3><?php echo number_format($total_outstanding, 2); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Admission No</th>
                                    <th>Student Name</th>
                                    <th>Required Fee (KES)</th>
                                    <th>Total Paid (KES)</th>
                                    <th>Outstanding (KES)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($students as $student): 
                                    $outstanding = $required_fee - $student['total_paid'];
                                    $status = $outstanding <= 0 ? 'Paid' : ($student['total_paid'] > 0 ? 'Partial' : 'Not Paid');
                                    $status_class = $outstanding <= 0 ? 'success' : ($student['total_paid'] > 0 ? 'warning' : 'danger');
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo number_format($required_fee, 2); ?></td>
                                    <td><?php echo number_format($student['total_paid'], 2); ?></td>
                                    <td><?php echo number_format(max(0, $outstanding), 2); ?></td>
                                    <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php elseif(isset($_GET['class_id'])): ?>
            <div class="alert alert-info">
                Please select a class, term, and year to generate the fee report.
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
@media print {
    .sidebar, .btn, form, .alert {
        display: none !important;
    }
    .card-header {
        border: none !important;
    }
    body {
        padding: 0 !important;
    }
}
</style>

<?php include 'includes/admin_footer.php'; ?>