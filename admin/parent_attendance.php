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
        header('Location: parent_attendance.php');
        exit();
    }
}

// Get parent's children
$stmt = $pdo->prepare("SELECT * FROM students WHERE parent_id = ? AND status = 'active'");
$stmt->execute([$_SESSION['user_id']]);
$children = $stmt->fetchAll();

// Get attendance data if student is selected
if ($student_id) {
    // Get attendance data for the last 30 days
    $stmt = $pdo->prepare("
        SELECT date, status 
        FROM attendance 
        WHERE student_id = ? 
        AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ORDER BY date DESC
    ");
    $stmt->execute([$student_id]);
    $attendance_records = $stmt->fetchAll();
    
    // Calculate attendance statistics
    $total_days = count($attendance_records);
    $present_days = 0;
    $absent_days = 0;
    $late_days = 0;
    
    foreach($attendance_records as $record) {
        switch($record['status']) {
            case 'Present': $present_days++; break;
            case 'Absent': $absent_days++; break;
            case 'Late': $late_days++; break;
        }
    }
    
    $attendance_rate = $total_days > 0 ? ($present_days / $total_days) * 100 : 0;
}
?>

<?php include 'includes/parent_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/parent_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Attendance Records</h1>
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
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6>Present</h6>
                                    <h3><?php echo $present_days; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h6>Absent</h6>
                                    <h3><?php echo $absent_days; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body text-center">
                                    <h6>Late</h6>
                                    <h3><?php echo $late_days; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h6>Attendance Rate</h6>
                                    <h3><?php echo number_format($attendance_rate, 1); ?>%</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5><?php echo htmlspecialchars($student['name']); ?>'s Attendance (Last 30 Days)</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($attendance_records)): ?>
                                <div class="alert alert-info">No attendance records found for the last 30 days.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($attendance_records as $record): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $record['status'] == 'Present' ? 'success' : 
                                                            ($record['status'] == 'Absent' ? 'danger' : 'warning'); ?>">
                                                        <?php echo htmlspecialchars($record['status']); ?>
                                                    </span>
                                                </td>
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
                        <p>Please select a child to view attendance records.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/parent_footer.php'; ?>