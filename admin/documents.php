<?php 
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['upload_document'])) {
        $title = $_POST['title'];
        $uploaded_by = $_SESSION['user_id'];
        
        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
            $upload_dir = '../uploads/documents/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . $_FILES['document_file']['name'];
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $file_path)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO documents (title, file_path, uploaded_by) VALUES (?, ?, ?)");
                    $stmt->execute([$title, $file_path, $uploaded_by]);
                    $success = "Document uploaded successfully!";
                } catch(PDOException $e) {
                    $error = "Error saving document: " . $e->getMessage();
                    // Delete the uploaded file if database insert fails
                    unlink($file_path);
                }
            } else {
                $error = "Error uploading file.";
            }
        } else {
            $error = "Please select a file to upload.";
        }
    }
    
    if (isset($_POST['delete_document'])) {
        $id = $_POST['document_id'];
        
        try {
            // Get file path before deleting record
            $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id=?");
            $stmt->execute([$id]);
            $document = $stmt->fetch();
            
            if ($document) {
                // Delete the file
                if (file_exists($document['file_path'])) {
                    unlink($document['file_path']);
                }
                
                // Delete database record
                $stmt = $pdo->prepare("DELETE FROM documents WHERE id=?");
                $stmt->execute([$id]);
                $success = "Document deleted successfully!";
            } else {
                $error = "Document not found.";
            }
        } catch(PDOException $e) {
            $error = "Error deleting document: " . $e->getMessage();
        }
    }
}

// Fetch all documents with uploader information
$documents = $pdo->query("
    SELECT d.*, u.name as uploader_name 
    FROM documents d 
    JOIN users u ON d.uploaded_by = u.id 
    ORDER BY d.date_uploaded DESC
")->fetchAll();
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Document Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                    <i class="fas fa-upload"></i> Upload Document
                </button>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <?php if(empty($documents)): ?>
                        <div class="alert alert-info">No documents found. Upload your first document.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Uploaded By</th>
                                        <th>Upload Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($documents as $document): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($document['title']); ?></td>
                                        <td><?php echo htmlspecialchars($document['uploader_name']); ?></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($document['date_uploaded'])); ?></td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($document['file_path']); ?>" 
                                               class="btn btn-sm btn-success" 
                                               download>
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="deleteDocument(<?php echo $document['id']; ?>, '<?php echo htmlspecialchars($document['title'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="upload_document" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Document Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Select File</label>
                        <input type="file" class="form-control" name="document_file" required>
                        <div class="form-text">Supported formats: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Document Modal -->
<div class="modal fade" id="deleteDocumentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="delete_document" value="1">
                    <input type="hidden" name="document_id" id="delete_document_id">
                    <p>Are you sure you want to delete the document <strong id="delete_document_title"></strong>? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteDocument(id, title) {
    document.getElementById('delete_document_id').value = id;
    document.getElementById('delete_document_title').textContent = title;
    new bootstrap.Modal(document.getElementById('deleteDocumentModal')).show();
}
</script>

<?php include 'includes/admin_footer.php'; ?>