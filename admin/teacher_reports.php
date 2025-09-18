<!-- admin/teacher_reports.php -->
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
$report_data = [];
$class_id = $_GET['class_id'] ?? '';
$subject_id = $_GET['subject_id'] ?? '';
$term = $_GET['term'] ?? 'Term 1';
$year = $_GET['year'] ?? date('Y');

// Get subjects for the selected class
$subjects = [];
if ($class_id) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.name
        FROM subjects s
        JOIN class_subjects cs ON s.id = cs.subject_id
        JOIN classes c ON cs.class_id = c.id
        WHERE cs.class_id = ? AND c.teacher_id = ?
        ORDER BY s.name
    ");
    $stmt->execute([$class_id, $_SESSION['user_id']]);
    $subjects = $stmt->fetchAll();
}

// Get report data if parameters are provided
if ($class_id && $subject_id) {
    // Verify this teacher owns this class
    $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$class_id, $_SESSION['user_id']]);
    $class_check = $stmt->fetch();
    
    if ($class_check) {
        // Fetch report data
        $stmt = $pdo->prepare("
            SELECT s.admission_no, s.name, m.score
            FROM students s
            LEFT JOIN marks m ON s.id = m.student_id AND m.subject_id = ? AND m.term = ? AND m.year = ?
            WHERE s.class_id = ? AND s.status = 'active'
            ORDER BY s.name
        ");
        $stmt->execute([$subject_id, $term, $year, $class_id]);
        $report_data = $stmt->fetchAll();
        
        // Calculate statistics
        $scores = array_filter(array_column($report_data, 'score'), function($score) {
            return $score !== null;
        });
        
        $total_students = count($report_data);
        $students_with_marks = count($scores);
        $average = $students_with_marks > 0 ? array_sum($scores) / $students_with_marks : 0;
        $highest = $students_with_marks > 0 ? max($scores) : 0;
        $lowest = $students_with_marks > 0 ? min($scores) : 0;
    } else {
        $error = "You don't have permission to access this class.";
    }
}

// Function to calculate grade
function calculateGrade($score) {
    if ($score === null) return 'N/A';
    if ($score >= 80) return 'A';
    if ($score >= 75) return 'A-';
    if ($score >= 70) return 'B+';
    if ($score >= 65) return 'B';
    if ($score >= 60) return 'B-';
    if ($score >= 55) return 'C+';
    if ($score >= 50) return 'C';
    if ($score >= 45) return 'C-';
    if ($score >= 40) return 'D+';
    if ($score >= 35) return 'D';
    return 'E';
}
?>

<?php include 'includes/teacher_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/teacher_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Class Performance Reports</h1>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
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
                        
                        <?php if($class_id): ?>
                        <div class="col-md-3">
                            <label class="form-label">Subject</label>
                            <select class="form-control" name="subject_id" required onchange="this.form.submit()">
                                <option value="">Select Subject</option>
                                <?php foreach($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>" <?php echo ($subject_id == $subject['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col-md-2">
                            <label class="form-label">Term</label>
                            <select class="form-control" name="term" onchange="this.form.submit()">
                                <option value="Term 1" <?php echo ($term == 'Term 1') ? 'selected' : ''; ?>>Term 1</option>
                                <option value="Term 2" <?php echo ($term == 'Term 2') ? 'selected' : ''; ?>>Term 2</option>
                                <option value="Term 3" <?php echo ($term == 'Term 3') ? 'selected' : ''; ?>>Term 3</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Year</label>
                            <input type="number" class="form-control" name="year" min="2010" max="2030" value="<?php echo $year; ?>" onchange="this.form.submit()">
                        </div>
                        
                        <?php if($class_id && $subject_id): ?>
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
            
            <?php if(!empty($report_data)): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>
                        <?php 
                            $class_name = '';
                            $subject_name = '';
                            foreach($my_classes as $class) {
                                if($class['id'] == $class_id) $class_name = $class['name'];
                            }
                            foreach($subjects as $subject) {
                                if($subject['id'] == $subject_id) $subject_name = $subject['name'];
                            }
                            echo "Marks Report: $class_name - $subject_name ($term $year)";
                        ?>
                    </h5>
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
                                    <h6>Students with Marks</h6>
                                    <h3><?php echo $students_with_marks; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h6>Average Score</h6>
                                    <h3><?php echo number_format($average, 1); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body text-center">
                                    <h6>Range</h6>
                                    <h3><?php echo "$lowest - $highest"; ?></h3>
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
                                    <th>Marks</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($report_data as $data): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($data['admission_no']); ?></td>
                                    <td><?php echo htmlspecialchars($data['name']); ?></td>
                                    <td><?php echo $data['score'] !== null ? number_format($data['score'], 1) : 'N/A'; ?></td>
                                    <td><?php echo calculateGrade($data['score']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php elseif($class_id): ?>
                <?php if(empty($subjects)): ?>
                    <div class="alert alert-info">
                        No subjects assigned to you for this class.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Please select a subject to generate reports.
                    </div>
                <?php endif; ?>
            <?php elseif(isset($_GET['class_id'])): ?>
                <div class="alert alert-info">
                    Please select a class to generate reports.
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