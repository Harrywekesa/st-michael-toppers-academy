<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'parent') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';
?>

<?php include 'includes/parent_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/parent_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Children</h1>
            </div>
            
            <?php
            $stmt = $pdo->prepare("
                SELECT s.*, c.name as class_name, 
                       COUNT(a.id) as total_days,
                       SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_days
                FROM students s 
                LEFT JOIN classes c ON s.class_id = c.id 
                LEFT JOIN attendance a ON s.id = a.student_id AND a.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                WHERE s.parent_id = ? AND s.status = 'active'
                GROUP BY s.id, s.name, s.admission_no, s.dob, s.class_id, c.name
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $children = $stmt->fetchAll();
            ?>
            
            <?php if(empty($children)): ?>
                <div class="alert alert-info">
                    <p>You don't have any children registered in the system.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach($children as $child): 
                        $attendance_rate = $child['total_days'] > 0 ? 
                            ($child['present_days'] / $child['total_days']) * 100 : 0;
                    ?>
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($child['name']); ?></h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Admission No:</strong></td>
                                            <td><?php echo htmlspecialchars($child['admission_no']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Date of Birth:</strong></td>
                                            <td><?php echo date('M j, Y', strtotime($child['dob'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Class:</strong></td>
                                            <td><?php echo $child['class_name'] ? htmlspecialchars($child['class_name']) : '<span class="text-muted">Not assigned</span>'; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Attendance (30 days):</strong></td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $attendance_rate; ?>%" aria-valuenow="<?php echo $attendance_rate; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo number_format($attendance_rate, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <div class="btn-group w-100" role="group">
                                        <a href="parent_report_card.php?student_id=<?php echo $child['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-file-alt"></i> Report Card
                                        </a>
                                        <a href="parent_attendance.php?student_id=<?php echo $child['id']; ?>" class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-calendar-check"></i> Attendance
                                        </a>
                                        <a href="parent_fees.php?student_id=<?php echo $child['id']; ?>" class="btn btn-outline-warning btn-sm">
                                            <i class="fas fa-money-bill-wave"></i> Fees
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/parent_footer.php'; ?>