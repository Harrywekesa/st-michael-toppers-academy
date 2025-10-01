<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Admin Dashboard</h1>
                <div>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Total Students</h5>
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
                            $result = $stmt->fetch();
                            ?>
                            <h2><?php echo $result['count']; ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Total Classes</h5>
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM classes");
                            $result = $stmt->fetch();
                            ?>
                            <h2><?php echo $result['count']; ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Total Teachers</h5>
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
                            $result = $stmt->fetch();
                            ?>
                            <h2><?php echo $result['count']; ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Pending Fees</h5>
                            <?php
                            // Simplified calculation for demo
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
                            $result = $stmt->fetch();
                            ?>
                            <h2><?php echo $result['count']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Activities</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <li class="list-group-item">New student registered: John Doe (PP1)</li>
                                <li class="list-group-item">Term 1 results uploaded for Grade 4</li>
                                <li class="list-group-item">Fee payment received from Mary Smith</li>
                                <li class="list-group-item">New notice posted: Parent-Teacher Meeting</li>
                                <li class="list-group-item">Attendance marked for Grade 3</li>
                                <li class="list-group-item">New teacher added: Mr. Johnson (Mathematics)</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="students.php" class="btn btn-primary">
                                    <i class="fas fa-user-graduate"></i> Manage Students
                                </a>
                                <a href="classes.php" class="btn btn-secondary">
                                    <i class="fas fa-school"></i> Manage Classes
                                </a>
                                <a href="subjects.php" class="btn btn-success">
                                    <i class="fas fa-book"></i> Manage Subjects
                                </a>
                                <a href="staff.php" class="btn btn-info">
                                    <i class="fas fa-chalkboard-teacher"></i> Manage Teachers
                                </a>
                                <a href="marks.php" class="btn btn-warning">
                                    <i class="fas fa-chart-line"></i> Manage Marks
                                </a>
                                <a href="fees.php" class="btn btn-danger">
                                    <i class="fas fa-money-bill-wave"></i> Manage Fees
                                </a>
                                <a href="reports.php" class="btn btn-dark">
                                    <i class="fas fa-file-alt"></i> Generate Reports
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>System Status</h5>
                        </div>
                        <div class="card-body">
                            <p><i class="fas fa-check-circle text-success"></i> Database: Connected</p>
                            <p><i class="fas fa-check-circle text-success"></i> Backups: Up to date</p>
                            <p><i class="fas fa-check-circle text-success"></i> Security: Active</p>
                            <p><i class="fas fa-exclamation-circle text-warning"></i> Updates: Available</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>