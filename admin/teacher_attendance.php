<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'teacher') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['mark_attendance'])) {
        $class_id = $_POST['class_id'];
        $date = $_POST['date'];
        
        try {
            // Process each student's attendance
            foreach($_POST['attendance'] as $student_id => $status) {
                // Check if attendance already exists for this date
                $stmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id=? AND date=?");
                $stmt->execute([$student_id, $date]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Update existing attendance
                    $stmt = $pdo->prepare("UPDATE attendance SET status=? WHERE id=?");
                    $stmt->execute([$status, $existing['id']]);
                } else {
                    // Insert new attendance
                    $stmt = $pdo->prepare("INSERT INTO attendance (student_id, date, status) VALUES (?, ?, ?)");
                    $stmt->execute([$student_id, $date, $status]);
                }
            }
            $success = "Attendance marked successfully!";
        } catch(PDOException $e) {
            $error = "Error marking attendance: " . $e->getMessage();
        }
    }
}

// Fetch teacher's classes
$stmt = $pdo->prepare("SELECT * FROM classes WHERE teacher_id = ? ORDER BY name");
$stmt->execute([$_SESSION['user_id']]);
$my_classes = $stmt->fetchAll();

// Initialize variables
$students = [];
$class_id = $_GET['class_id'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

// Get attendance data if class is selected
if ($class_id) {
    // Verify this teacher owns this class
    $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$class_id, $_SESSION['user_id']]);
    $class_check = $stmt->fetch();
    
    if ($class_check) {
        // Fetch students in the selected class with attendance for the date
        $stmt = $pdo->prepare("
            SELECT s.*, a.status 
            FROM students s 
            LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ?
            WHERE s.class_id = ? AND s.status = 'active'
            ORDER BY s.name
        ");
        $stmt->execute([$date, $class_id]);
        $students = $stmt->fetchAll();
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
                <h1 class="h2">Class Attendance</h1>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
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
                        
                        <div class="col-md-5">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="date" value="<?php echo $date; ?>" onchange="this.form.submit()">
                        </div>
                        
                        <?php if($class_id): ?>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <a href="teacher_attendance_report.php?class_id=<?php echo $class_id; ?>&date=<?php echo $date; ?>" class="btn btn-primary w-100">
                                <i class="fas fa-chart-bar"></i> View Report
                            </a>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <?php if(!empty($students)): ?>
            <div class="card">
                <div class="card-header">
                    <h5>
                        Attendance for <?php 
                            foreach($my_classes as $class) {
                                if($class['id'] == $class_id) echo htmlspecialchars($class['name']);
                            }
                            echo " on " . date('M j, Y', strtotime($date));
                        ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="mark_attendance" value="1">
                        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                        <input type="hidden" name="date" value="<?php echo $date; ?>">
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Admission No</th>
                                        <th>Student Name</th>
                                        <th>Attendance Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="attendance[<?php echo $student['id']; ?>]" value="Present" <?php echo ($student['status'] == 'Present') ? 'checked' : ''; ?> required>
                                                <label class="form-check-label">Present</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="attendance[<?php echo $student['id']; ?>]" value="Absent" <?php echo ($student['status'] == 'Absent') ? 'checked' : ''; ?> required>
                                                <label class="form-check-label">Absent</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="attendance[<?php echo $student['id']; ?>]" value="Late" <?php echo ($student['status'] == 'Late') ? 'checked' : ''; ?> required>
                                                <label class="form-check-label">Late</label>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Attendance
                        </button>
                    </form>
                </div>
            </div>
            <?php elseif($class_id): ?>
                <div class="alert alert-info">
                    No students found in this class.
                </div>
            <?php elseif(isset($_GET['class_id'])): ?>
                <div class="alert alert-info">
                    Please select a class to mark attendance.
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/teacher_footer.php'; ?>