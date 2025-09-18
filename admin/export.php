<!-- admin/export.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Handle export requests
if (isset($_GET['action']) && isset($_GET['type'])) {
    $type = $_GET['type'];
    $format = $_GET['format'] ?? 'csv';
    
    switch($type) {
        case 'students':
            exportStudents($pdo, $format);
            break;
        case 'marks':
            exportMarks($pdo, $format);
            break;
        case 'attendance':
            exportAttendance($pdo, $format);
            break;
        case 'fees':
            exportFees($pdo, $format);
            break;
    }
    exit();
}

function exportStudents($pdo, $format) {
    $stmt = $pdo->query("
        SELECT s.admission_no, s.name, s.dob, s.gender, c.name as class_name, s.status
        FROM students s
        LEFT JOIN classes c ON s.class_id = c.id
        ORDER BY s.admission_no
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $headers = ['Admission No', 'Name', 'Date of Birth', 'Gender', 'Class', 'Status'];
    exportData($data, $headers, 'students', $format);
}

function exportMarks($pdo, $format) {
    $stmt = $pdo->query("
        SELECT s.admission_no, s.name, sub.name as subject, m.term, m.year, m.score
        FROM marks m
        JOIN students s ON m.student_id = s.id
        JOIN subjects sub ON m.subject_id = sub.id
        ORDER BY s.admission_no, sub.name
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $headers = ['Admission No', 'Student Name', 'Subject', 'Term', 'Year', 'Score'];
    exportData($data, $headers, 'marks', $format);
}

function exportAttendance($pdo, $format) {
    $stmt = $pdo->query("
        SELECT s.admission_no, s.name, a.date, a.status
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        ORDER BY s.admission_no, a.date
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $headers = ['Admission No', 'Student Name', 'Date', 'Status'];
    exportData($data, $headers, 'attendance', $format);
}

function exportFees($pdo, $format) {
    $stmt = $pdo->query("
        SELECT s.admission_no, s.name, p.term, p.year, p.amount_paid, p.payment_date
        FROM payments p
        JOIN students s ON p.student_id = s.id
        ORDER BY s.admission_no, p.payment_date
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $headers = ['Admission No', 'Student Name', 'Term', 'Year', 'Amount Paid', 'Payment Date'];
    exportData($data, $headers, 'fees', $format);
}

function exportData($data, $headers, $filename, $format) {
    $filename .= '_' . date('Y-m-d');
    
    if ($format == 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
    } else {
        // Excel format using PHPExcel or similar library would go here
        // For now, we'll just export as CSV
        exportData($data, $headers, $filename, 'csv');
    }
}
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Export & Print</h1>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Export Data</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Students Data</label>
                                <div class="btn-group w-100" role="group">
                                    <a href="?action=export&type=students&format=csv" class="btn btn-primary">
                                        <i class="fas fa-file-csv"></i> Export CSV
                                    </a>
                                    <a href="?action=export&type=students&format=excel" class="btn btn-success">
                                        <i class="fas fa-file-excel"></i> Export Excel
                                    </a>
                                </div>
                                <small class="text-muted">Export all student records with class assignments</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Marks Data</label>
                                <div class="btn-group w-100" role="group">
                                    <a href="?action=export&type=marks&format=csv" class="btn btn-primary">
                                        <i class="fas fa-file-csv"></i> Export CSV
                                    </a>
                                    <a href="?action=export&type=marks&format=excel" class="btn btn-success">
                                        <i class="fas fa-file-excel"></i> Export Excel
                                    </a>
                                </div>
                                <small class="text-muted">Export all student marks across all subjects</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Attendance Data</label>
                                <div class="btn-group w-100" role="group">
                                    <a href="?action=export&type=attendance&format=csv" class="btn btn-primary">
                                        <i class="fas fa-file-csv"></i> Export CSV
                                    </a>
                                    <a href="?action=export&type=attendance&format=excel" class="btn btn-success">
                                        <i class="fas fa-file-excel"></i> Export Excel
                                    </a>
                                </div>
                                <small class="text-muted">Export all attendance records</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Fees Data</label>
                                <div class="btn-group w-100" role="group">
                                    <a href="?action=export&type=fees&format=csv" class="btn btn-primary">
                                        <i class="fas fa-file-csv"></i> Export CSV
                                    </a>
                                    <a href="?action=export&type=fees&format=excel" class="btn btn-success">
                                        <i class="fas fa-file-excel"></i> Export Excel
                                    </a>
                                </div>
                                <small class="text-muted">Export all fee payment records</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Print Reports</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Student List</label>
                                <a href="reports.php" class="btn btn-primary w-100">
                                    <i class="fas fa-print"></i> Print Student List
                                </a>
                                <small class="text-muted">Print alphabetical list of all students</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Class Lists</label>
                                <a href="reports.php" class="btn btn-primary w-100">
                                    <i class="fas fa-print"></i> Print Class Lists
                                </a>
                                <small class="text-muted">Print student lists organized by class</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Report Cards</label>
                                <a href="report_card.php" class="btn btn-primary w-100">
                                    <i class="fas fa-print"></i> Print Report Cards
                                </a>
                                <small class="text-muted">Print individual or bulk report cards</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Fee Reports</label>
                                <a href="fee_reports.php" class="btn btn-primary w-100">
                                    <i class="fas fa-print"></i> Print Fee Reports
                                </a>
                                <small class="text-muted">Print fee payment status reports</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>Export Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="includeHeaders" checked>
                                <label class="form-check-label" for="includeHeaders">
                                    Include column headers
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="includeTimestamp">
                                <label class="form-check-label" for="includeTimestamp">
                                    Include export timestamp
                                </label>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Date Range (for applicable exports)</label>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="date" class="form-control" id="startDate">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" class="form-control" id="endDate" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <button class="btn btn-secondary">Save Settings</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>