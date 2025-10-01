<?php 
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['upload_marks'])) {
        $class_id = $_POST['class_id'];
        $subject_id = $_POST['subject_id'];
        $term = $_POST['term'];
        $year = $_POST['year'];
        
        try {
            // Process each student's marks
            foreach($_POST['marks'] as $student_id => $score) {
                if ($score !== '') {
                    // Check if mark already exists
                    $stmt = $pdo->prepare("SELECT id FROM marks WHERE student_id=? AND subject_id=? AND term=? AND year=?");
                    $stmt->execute([$student_id, $subject_id, $term, $year]);
                    $existing = $stmt->fetch();
                    
                    if ($existing) {
                        // Update existing mark
                        $stmt = $pdo->prepare("UPDATE marks SET score=? WHERE id=?");
                        $stmt->execute([$score, $existing['id']]);
                    } else {
                        // Insert new mark
                        $stmt = $pdo->prepare("INSERT INTO marks (student_id, subject_id, term, year, score) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$student_id, $subject_id, $term, $year, $score]);
                    }
                }
            }
            $success = "Marks uploaded successfully!";
        } catch(PDOException $e) {
            $error = "Error uploading marks: " . $e->getMessage();
        }
    }
}

// Fetch classes based on user role
if ($_SESSION['user_role'] == 'admin') {
    $classes = $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll();
} else {
    // Teachers can only see their classes
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE teacher_id = ? ORDER BY name");
    $stmt->execute([$_SESSION['user_id']]);
    $classes = $stmt->fetchAll();
}

// Initialize variables
$students = [];
$class_id = '';
$subject_id = '';
$term = '';
$year = date('Y');

// Get marks data if class and subject are selected
if (isset($_GET['class_id']) && isset($_GET['subject_id']) && isset($_GET['term'])) {
    $class_id = $_GET['class_id'];
    $subject_id = $_GET['subject_id'];
    $term = $_GET['term'];
    $year = $_GET['year'] ?? date('Y');
    
    // Verify access rights
    if ($_SESSION['user_role'] == 'teacher') {
        // Teachers can only access their own classes
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$class_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            $error = "You don't have permission to access this class.";
        }
    }
    
    if (!isset($error)) {
        // Fetch students in the selected class
        $stmt = $pdo->prepare("
            SELECT s.*, m.score 
            FROM students s 
            LEFT JOIN marks m ON s.id = m.student_id AND m.subject_id = ? AND m.term = ? AND m.year = ?
            WHERE s.class_id = ? AND s.status = 'active'
            ORDER BY s.name
        ");
        $stmt->execute([$subject_id, $term, $year, $class_id]);
        $students = $stmt->fetchAll();
    }
}

// Fetch subjects
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Marks Management</h1>
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
                        <div class="col-md-3">
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
                            <label class="form-label">Subject</label>
                            <select class="form-control" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>" <?php echo ($subject_id == $subject['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Term</label>
                            <select class="form-control" name="term" required>
                                <option value="">Select Term</option>
                                <option value="Term 1" <?php echo ($term == 'Term 1') ? 'selected' : ''; ?>>Term 1</option>
                                <option value="Term 2" <?php echo ($term == 'Term 2') ? 'selected' : ''; ?>>Term 2</option>
                                <option value="Term 3" <?php echo ($term == 'Term 3') ? 'selected' : ''; ?>>Term 3</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Year</label>
                            <input type="number" class="form-control" name="year" min="2010" max="2030" value="<?php echo $year; ?>" required>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Load Students</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if(!empty($students) && !isset($error)): ?>
            <div class="card">
                <div class="card-header">
                    <h5>
                        Marks for <?php 
                            foreach($classes as $class) {
                                if($class['id'] == $class_id) echo htmlspecialchars($class['name']);
                            }
                            echo " - ";
                            foreach($subjects as $subject) {
                                if($subject['id'] == $subject_id) echo htmlspecialchars($subject['name']);
                            }
                            echo " ($term $year)";
                        ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="upload_marks" value="1">
                        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                        <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                        <input type="hidden" name="term" value="<?php echo $term; ?>">
                        <input type="hidden" name="year" value="<?php echo $year; ?>">
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Admission No</th>
                                        <th>Student Name</th>
                                        <th>Marks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td>
                                            <input type="number" 
                                                   class="form-control" 
                                                   name="marks[<?php echo $student['id']; ?>]" 
                                                   value="<?php echo $student['score']; ?>" 
                                                   min="0" 
                                                   max="100" 
                                                   step="0.1"
                                                   placeholder="Enter marks">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Marks
                        </button>
                        
                        <a href="reports.php?class_id=<?php echo $class_id; ?>&subject_id=<?php echo $subject_id; ?>&term=<?php echo $term; ?>&year=<?php echo $year; ?>" 
                           class="btn btn-primary">
                            <i class="fas fa-chart-bar"></i> Generate Report
                        </a>
                    </form>
                </div>
            </div>
            <?php elseif(isset($_GET['class_id']) && !isset($error)): ?>
            <div class="alert alert-info">
                Please select a class, subject, and term to view students.
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>