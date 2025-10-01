<?php 
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_notice'])) {
        $title = $_POST['title'];
        $content = $_POST['content'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO notices (title, content) VALUES (?, ?)");
            $stmt->execute([$title, $content]);
            $success = "Notice added successfully!";
        } catch(PDOException $e) {
            $error = "Error adding notice: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_notice'])) {
        $id = $_POST['notice_id'];
        $title = $_POST['title'];
        $content = $_POST['content'];
        
        try {
            $stmt = $pdo->prepare("UPDATE notices SET title=?, content=? WHERE id=?");
            $stmt->execute([$title, $content, $id]);
            $success = "Notice updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating notice: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_notice'])) {
        $id = $_POST['notice_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM notices WHERE id=?");
            $stmt->execute([$id]);
            $success = "Notice deleted successfully!";
        } catch(PDOException $e) {
            $error = "Error deleting notice: " . $e->getMessage();
        }
    }
}

// Fetch all notices
$notices = $pdo->query("SELECT * FROM notices ORDER BY date_posted DESC")->fetchAll();
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Notice Board</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNoticeModal">
                    <i class="fas fa-plus"></i> Add New Notice
                </button>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <?php if(empty($notices)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">No notices found. Add your first notice.</div>
                    </div>
                <?php else: ?>
                    <?php foreach($notices as $notice): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($notice['title']); ?></h5>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editNotice(<?php echo $notice['id']; ?>, '<?php echo htmlspecialchars($notice['title'], ENT_QUOTES); ?>', `<?php echo htmlspecialchars($notice['content'], ENT_QUOTES); ?>`)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteNotice(<?php echo $notice['id']; ?>, '<?php echo htmlspecialchars($notice['title'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p><?php echo nl2br(htmlspecialchars($notice['content'])); ?></p>
                                    <small class="text-muted">
                                        Posted on <?php echo date('M j, Y g:i A', strtotime($notice['date_posted'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Add Notice Modal -->
<div class="modal fade" id="addNoticeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_notice" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea class="form-control" name="content" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Notice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Notice Modal -->
<div class="modal fade" id="editNoticeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="update_notice" value="1">
                    <input type="hidden" name="notice_id" id="edit_notice_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" id="edit_title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea class="form-control" name="content" id="edit_content" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Notice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Notice Modal -->
<div class="modal fade" id="deleteNoticeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="delete_notice" value="1">
                    <input type="hidden" name="notice_id" id="delete_notice_id">
                    <p>Are you sure you want to delete the notice <strong id="delete_notice_title"></strong>? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Notice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editNotice(id, title, content) {
    document.getElementById('edit_notice_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_content').value = content;
    new bootstrap.Modal(document.getElementById('editNoticeModal')).show();
}

function deleteNotice(id, title) {
    document.getElementById('delete_notice_id').value = id;
    document.getElementById('delete_notice_title').textContent = title;
    new bootstrap.Modal(document.getElementById('deleteNoticeModal')).show();
}
</script>

<?php include 'includes/admin_footer.php'; ?>