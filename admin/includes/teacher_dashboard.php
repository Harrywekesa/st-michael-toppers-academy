<!-- admin/teacher_dashboard.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'teacher') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Get teacher's classes
$stmt = $pdo->prepare("SELECT * FROM classes WHERE teacher_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$my_classes = $stmt->fetchAll();

// Get teacher's subjects
$stmt = $pdo->prepare("
    SELECT DISTINCT s.name as subject_name
    FROM subjects s
    JOIN class_subjects cs ON s.id = cs.subject_id
    JOIN classes c ON cs.class_id = c.id
    WHERE c.teacher_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$my_subjects = $stmt->fetchAll();

// Get recent announcements
$announcements = $pdo->query("SELECT * FROM notices ORDER BY date_posted DESC LIMIT 5")->fetchAll();

// Get today's date for attendance
$today = date('Y-m-d');
?>

<?php include 'includes/teacher_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/teacher_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Teacher Dashboard</h1>
                <div>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">My Classes</h5>
                            <h2><?php echo count($my_classes); ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Subjects Teaching</h5>
                            <h2><?php echo count($my_subjects); ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Today's Date</h5>
                            <h2><?php echo date('M j, Y'); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-school"></i> My Classes</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($my_classes)): ?>
                                <p class="text-muted">You are not assigned to any classes yet.</p>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach($my_classes as $class): 
                                        // Get student count for this class
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE class_id = ? AND status = 'active'");
                                        $stmt->execute([$class['id']]);
                                        $student_count = $stmt->fetch()['count'];
                                    ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($class['name']); ?></h5>
                                                    <p class="card-text">
                                                        <strong>Students:</strong> <?php echo $student_count; ?>
                                                    </p>
                                                    <div class="btn-group w-100" role="group">
                                                        <a href="marks.php?class_id=<?php echo $class['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-chart-line"></i> Marks
                                                        </a>
                                                        <a href="attendance.php?class_id=<?php echo $class['id']; ?>" class="btn btn-outline-success btn-sm">
                                                            <i class="fas fa-calendar-check"></i> Attendance
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-bullhorn"></i> Recent Announcements</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($announcements)): ?>
                                <p class="text-muted">No recent announcements.</p>
                            <?php else: ?>
                                <?php foreach($announcements as $announcement): ?>
                                    <div class="border-bottom pb-2 mb-2">
                                        <h6><?php echo htmlspecialchars($announcement['title']); ?></h6>
                                        <p class="mb-1"><?php echo substr(htmlspecialchars($announcement['content']), 0, 100) . '...'; ?></p>
                                        <small class="text-muted"><?php echo date('M j, Y', strtotime($announcement['date_posted'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="marks.php" class="btn btn-primary">
                                    <i class="fas fa-chart-line"></i> Enter Marks
                                </a>
                                <a href="attendance.php" class="btn btn-success">
                                    <i class="fas fa-calendar-check"></i> Mark Attendance
                                </a>
                                <a href="reports.php" class="btn btn-warning">
                                    <i class="fas fa-file-alt"></i> Generate Reports
                                </a>
                                <a href="announcements.php" class="btn btn-info">
                                    <i class="fas fa-bullhorn"></i> Post Announcement
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/teacher_footer.php'; ?>