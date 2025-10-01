<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'teacher') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Fetch teacher's classes
$stmt = $pdo->prepare("SELECT * FROM classes WHERE teacher_id = ? ORDER BY name");
$stmt->execute([$_SESSION['user_id']]);
$my_classes = $stmt->fetchAll();

// Initialize variables
$attendance_data = [];
$class_id = $_GET['class_id'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get attendance data if parameters are provided
if ($class_id) {
    // Verify this teacher owns this class
    $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$class_id, $_SESSION['user_id']]);
    $class_check = $stmt->fetch();
    
    if ($class_check) {
        // Fetch attendance data for the period
        $stmt = $pdo->prepare("
            SELECT s.admission_no, s.name, 
                   SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present,
                   SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent,
                   SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late,
                   COUNT(a.id) as total_days
            FROM students s
            LEFT JOIN attendance a ON s.id = a.student_id 
                AND a.date BETWEEN ? AND ?
            WHERE s.class_id = ? AND s.status = 'active'
            GROUP BY s.id, s.admission_no, s.name
            ORDER BY s.name
        ");
        $stmt->execute([$start_date, $end_date, $class_id]);
        $attendance_data = $stmt->fetchAll();
        
        // Calculate total school days in the period
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT date) as school_days
            FROM attendance
            WHERE date BETWEEN ? AND ?
        ");
        $stmt->execute([$start_date, $end_date]);
        $school_days_result = $stmt->fetch();
        $school_days = $school_days_result['school_days'] ?: 1;
    } else {
        $error = "You don't have permission to access this class.";
    }
}
?>

<?php include 'includes/teacher_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/teacher_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Attendance Reports</h1>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Class</label>
                            <select class="form-control" name="class_id" required onchange="this.form.submit()">
                                <option value="">Select Class</option>
                                <?php foreach($my_classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo ($class_id == $class['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>" onchange="this.form.submit()">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>" onchange="this.form.submit()">
                        </div>
                        
                        <?php if($class_id): ?>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-success w-100" onclick="window.print()">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <?php if(!empty($attendance_data)): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>
                        <?php 
                            $class_name = '';
                            foreach($my_classes as $class) {
                                if($class['id'] == $class_id) $class_name = $class['name'];
                            }
                            echo "Attendance Report: $class_name (" . date('M j, Y', strtotime($start_date)) . " to " . date('M j, Y', strtotime($end_date)) . ")";
                        ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Admission No</th>
                                    <th>Student Name</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                    <th>Attendance Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($attendance_data as $data): 
                                    $attendance_rate = $school_days > 0 ? ($data['present'] / $school_days) * 100 : 0;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($data['admission_no']); ?></td>
                                    <td><?php echo htmlspecialchars($data['name']); ?></td>
                                    <td><?php echo $data['present']; ?></td>
                                    <td><?php echo $data['absent']; ?></td>
                                    <td><?php echo $data['late']; ?></td>
                                    <td><?php echo number_format($attendance_rate, 1); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php elseif($class_id): ?>
                <div class="alert alert-info">
                    No attendance data found for the selected period.
                </div>
            <?php elseif(isset($_GET['class_id'])): ?>
                <div class="alert alert-info">
                    Please select a class to generate attendance reports.
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

<?php include 'includes/teacher_footer.php'; ?>