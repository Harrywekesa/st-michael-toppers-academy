<!-- admin/my_classes.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'teacher') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Get teacher's classes with student counts
$stmt = $pdo->prepare("
    SELECT c.*, COUNT(s.id) as student_count
    FROM classes c
    LEFT JOIN students s ON c.id = s.class_id AND s.status = 'active'
    WHERE c.teacher_id = ?
    GROUP BY c.id, c.name
    ORDER BY c.name
");
$stmt->execute([$_SESSION['user_id']]);
$my_classes = $stmt->fetchAll();

// Get all subjects for this teacher
$stmt = $pdo->prepare("
    SELECT DISTINCT s.name as subject_name, s.id as subject_id
    FROM subjects s
    JOIN class_subjects cs ON s.id = cs.subject_id
    JOIN classes c ON cs.class_id = c.id
    WHERE c.teacher_id = ?
    ORDER BY s.name
");
$stmt->execute([$_SESSION['user_id']]);
$my_subjects = $stmt->fetchAll();
?>

<?php include 'includes/teacher_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/teacher_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Classes</h1>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Classes Assigned to Me</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($my_classes)): ?>
                                <div class="alert alert-info">
                                    <p>You are not assigned to any classes yet. Please contact the administrator.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Class Name</th>
                                                <th>Number of Students</th>
                                                <th>Subjects</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($my_classes as $class): 
                                                // Get subjects for this specific class
                                                $stmt = $pdo->prepare("
                                                    SELECT s.name
                                                    FROM subjects s
                                                    JOIN class_subjects cs ON s.id = cs.subject_id
                                                    WHERE cs.class_id = ?
                                                    ORDER BY s.name
                                                ");
                                                $stmt->execute([$class['id']]);
                                                $class_subjects = $stmt->fetchAll();
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($class['name']); ?></td>
                                                <td><?php echo $class['student_count']; ?></td>
                                                <td>
                                                    <?php if(empty($class_subjects)): ?>
                                                        <span class="text-muted">No subjects assigned</span>
                                                    <?php else: ?>
                                                        <?php 
                                                        $subject_names = array_column($class_subjects, 'name');
                                                        echo implode(', ', array_map('htmlspecialchars', $subject_names));
                                                        ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="marks.php?class_id=<?php echo $class['id']; ?>" class="btn btn-outline-primary" title="Manage Marks">
                                                            <i class="fas fa-chart-line"></i>
                                                        </a>
                                                        <a href="attendance.php?class_id=<?php echo $class['id']; ?>" class="btn btn-outline-success" title="Mark Attendance">
                                                            <i class="fas fa-calendar-check"></i>
                                                        </a>
                                                        <a href="reports.php?class_id=<?php echo $class['id']; ?>" class="btn btn-outline-warning" title="Class Reports">
                                                            <i class="fas fa-file-alt"></i>
                                                        </a>
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
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Subjects I Teach</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($my_subjects)): ?>
                                <p class="text-muted">No subjects assigned to you.</p>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach($my_subjects as $subject): ?>
                                        <li class="list-group-item">
                                            <i class="fas fa-book me-2 text-primary"></i>
                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Class Information</h5>
                        </div>
                        <div class="card-body">
                            <p>As a class teacher, you are responsible for:</p>
                            <ul>
                                <li>Maintaining class records</li>
                                <li>Marking daily attendance</li>
                                <li>Entering student marks</li>
                                <li>Communicating with parents</li>
                                <li>Preparing class reports</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/teacher_footer.php'; ?>