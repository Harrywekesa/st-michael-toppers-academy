<!-- admin/parent_notices.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'parent') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Fetch all notices
$notices = $pdo->query("SELECT * FROM notices ORDER BY date_posted DESC")->fetchAll();
?>

<?php include 'includes/parent_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/parent_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">School Announcements</h1>
            </div>
            
            <?php if(empty($notices)): ?>
                <div class="alert alert-info">
                    <p>No announcements at this time.</p>
                </div>
            <?php else: ?>
                <?php foreach($notices as $notice): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?php echo htmlspecialchars($notice['title']); ?></h5>
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br(htmlspecialchars($notice['content'])); ?></p>
                            <small class="text-muted">
                                Posted on <?php echo date('M j, Y g:i A', strtotime($notice['date_posted'])); ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/parent_footer.php'; ?>