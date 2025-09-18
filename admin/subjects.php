<!-- admin/subjects.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_subject'])) {
        $name = $_POST['name'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO subjects (name) VALUES (?)");
            $stmt->execute([$name]);
            $success = "Subject added successfully!";
        } catch(PDOException $e) {
            $error = "Error adding subject: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_subject'])) {
        $id = $_POST['subject_id'];
        $name = $_POST['name'];
        
        try {
            $stmt = $pdo->prepare("UPDATE subjects SET name=? WHERE id=?");
            $stmt->execute([$name, $id]);
            $success = "Subject updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating subject: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_subject'])) {
        $id = $_POST['subject_id'];
        
        try {
            // First remove subject references from class_subjects
            $stmt = $pdo->prepare("DELETE FROM class_subjects WHERE subject_id=?");
            $stmt->execute([$id]);
            
            // Then delete the subject
            $stmt = $pdo->prepare("DELETE FROM subjects WHERE id=?");
            $stmt->execute([$id]);
            
            $success = "Subject deleted successfully!";
        } catch(PDOException $e) {
            $error = "Error deleting subject: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['assign_subject'])) {
        $class_id = $_POST['class_id'];
        $subject_id = $_POST['subject_id'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO class_subjects (class_id, subject_id) VALUES (?, ?)");
            $stmt->execute([$class_id, $subject_id]);
            $success = "Subject assigned to class successfully!";
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $error = "This subject is already assigned to this class.";
            } else {
                $error = "Error assigning subject: " . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['unassign_subject'])) {
        $class_id = $_POST['class_id'];
        $subject_id = $_POST['subject_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM class_subjects WHERE class_id=? AND subject_id=?");
            $stmt->execute([$class_id, $subject_id]);
            $success = "Subject unassigned from class successfully!";
        } catch(PDOException $e) {
            $error = "Error unassigning subject: " . $e->getMessage();
        }
    }
}

// Fetch subjects
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll();

// Fetch classes
$classes = $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll();

// Get subject assignments for each class
$assignments = [];
foreach($classes as $class) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.name 
        FROM subjects s 
        JOIN class_subjects cs ON s.id = cs.subject_id 
        WHERE cs.class_id = ? 
        ORDER BY s.name
    ");
    $stmt->execute([$class['id']]);
    $assignments[$class['id']] = $stmt->fetchAll();
}
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Subject Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                    <i class="fas fa-plus"></i> Add New Subject
                </button>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>All Subjects</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($subjects)): ?>
                                <p class="text-muted">No subjects found. Add your first subject.</p>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach($subjects as $subject): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?php echo htmlspecialchars($subject['name']); ?>
                                            <div>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="editSubject(<?php echo $subject['id']; ?>, '<?php echo $subject['name']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteSubject(<?php echo $subject['id']; ?>, '<?php echo $subject['name']; ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Assign Subjects to Classes</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="assign_subject" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label">Select Class</label>
                                    <select class="form-control" name="class_id" required>
                                        <option value="">Choose a class</option>
                                        <?php foreach($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>">
                                                <?php echo htmlspecialchars($class['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Select Subject</label>
                                    <select class="form-control" name="subject_id" required>
                                        <option value="">Choose a subject</option>
                                        <?php foreach($subjects as $subject): ?>
                                            <option value="<?php echo $subject['id']; ?>">
                                                <?php echo htmlspecialchars($subject['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Assign Subject</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Subject Assignments</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($classes)): ?>
                                <p class="text-muted">No classes found.</p>
                            <?php else: ?>
                                <?php foreach($classes as $class): ?>
                                    <div class="mb-3">
                                        <h6><?php echo htmlspecialchars($class['name']); ?></h6>
                                        <?php if(empty($assignments[$class['id']])): ?>
                                            <small class="text-muted">No subjects assigned</small>
                                        <?php else: ?>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php foreach($assignments[$class['id']] as $subject): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="unassign_subject" value="1">
                                                        <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                                        <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                                        <span class="badge bg-primary">
                                                            <?php echo htmlspecialchars($subject['name']); ?>
                                                            <button type="submit" class="btn-close btn-close-white ms-1" style="font-size: 0.5rem;"></button>
                                                        </span>
                                                    </form>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_subject" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Subject Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="update_subject" value="1">
                    <input type="hidden" name="subject_id" id="edit_subject_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Subject Name</label>
                        <input type="text" class="form-control" name="name" id="edit_subject_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Subject Modal -->
<div class="modal fade" id="deleteSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="delete_subject" value="1">
                    <input type="hidden" name="subject_id" id="delete_subject_id">
                    <p>Are you sure you want to delete <strong id="delete_subject_name"></strong>? This will remove this subject from all classes.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editSubject(id, name) {
    document.getElementById('edit_subject_id').value = id;
    document.getElementById('edit_subject_name').value = name;
    new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
}

function deleteSubject(id, name) {
    document.getElementById('delete_subject_id').value = id;
    document.getElementById('delete_subject_name').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteSubjectModal')).show();
}
</script>

<?php include 'includes/admin_footer.php'; ?>