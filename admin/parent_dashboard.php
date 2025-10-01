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
                <h1 class="h2">Parent Portal</h1>
                <div>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
            </div>
            
            <?php
            // Get parent's children
            $stmt = $pdo->prepare("SELECT s.*, c.name as class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.parent_id = ? AND s.status = 'active'");
            $stmt->execute([$_SESSION['user_id']]);
            $children = $stmt->fetchAll();
            ?>
            
            <?php if(empty($children)): ?>
                <div class="alert alert-info">
                    <h4>No Children Registered</h4>
                    <p>You don't have any children registered in our system yet. Please contact the school administration to register your child.</p>
                    <a href="contact_school.php" class="btn btn-primary">Contact School</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach($children as $child): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($child['name']); ?></h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Admission Number:</strong> <?php echo htmlspecialchars($child['admission_no']); ?></p>
                                    <p><strong>Class:</strong> <?php echo $child['class_name'] ? htmlspecialchars($child['class_name']) : '<span class="text-muted">Not assigned</span>'; ?></p>
                                    <p><strong>Date of Birth:</strong> <?php echo date('M j, Y', strtotime($child['dob'])); ?></p>
                                    
                                    <div class="btn-group w-100" role="group">
                                        <a href="parent_report_card.php?student_id=<?php echo $child['id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-file-alt"></i> Report Card
                                        </a>
                                        <a href="parent_attendance.php?student_id=<?php echo $child['id']; ?>" class="btn btn-outline-success">
                                            <i class="fas fa-calendar-check"></i> Attendance
                                        </a>
                                        <a href="parent_fees.php?student_id=<?php echo $child['id']; ?>" class="btn btn-outline-warning">
                                            <i class="fas fa-money-bill-wave"></i> Fees
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Recent Announcements -->
            <?php
            $notices = $pdo->query("SELECT * FROM notices ORDER BY date_posted DESC LIMIT 3")->fetchAll();
            ?>
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-bullhorn"></i> Recent Announcements</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($notices)): ?>
                        <p class="text-muted">No recent announcements.</p>
                    <?php else: ?>
                        <?php foreach($notices as $notice): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <h6><?php echo htmlspecialchars($notice['title']); ?></h6>
                                <p><?php echo nl2br(htmlspecialchars(substr($notice['content'], 0, 150))) . (strlen($notice['content']) > 150 ? '...' : ''); ?></p>
                                <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($notice['date_posted'])); ?></small>
                                <a href="parent_notice_detail.php?id=<?php echo $notice['id']; ?>" class="btn btn-sm btn-outline-primary">Read More</a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/parent_footer.php'; ?>