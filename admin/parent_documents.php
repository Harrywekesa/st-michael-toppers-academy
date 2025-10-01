<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'parent') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Fetch all documents with uploader information
$documents = $pdo->query("
    SELECT d.*, u.name as uploader_name 
    FROM documents d 
    JOIN users u ON d.uploaded_by = u.id 
    ORDER BY d.date_uploaded DESC
")->fetchAll();
?>

<?php include 'includes/parent_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/parent_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">School Documents</h1>
            </div>
            
            <?php if(empty($documents)): ?>
                <div class="alert alert-info">
                    <p>No documents available at this time.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach($documents as $document): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($document['title']); ?></h5>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            Uploaded by: <?php echo htmlspecialchars($document['uploader_name']); ?><br>
                                            Date: <?php echo date('M j, Y g:i A', strtotime($document['date_uploaded'])); ?>
                                        </small>
                                    </p>
                                    <a href="<?php echo htmlspecialchars($document['file_path']); ?>" 
                                       class="btn btn-primary" 
                                       download>
                                        <i class="fas fa-download"></i> Download Document
                                    </a>
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