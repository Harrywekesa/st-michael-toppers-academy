<!-- admin/classes.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_class'])) {
        $name = $_POST['name'];
        $teacher_id = $_POST['teacher_id'] ?: null;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO classes (name, teacher_id) VALUES (?, ?)");
            $stmt->execute([$name, $teacher_id]);
            $success = "Class added successfully!";
        } catch(PDOException $e) {
            $error = "Error adding class: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_class'])) {
        $id = $_POST['class_id'];
        $name = $_POST['name'];
        $teacher_id = $_POST['teacher_id'] ?: null;
        
        try {
            $stmt = $pdo->prepare("UPDATE classes SET name=?, teacher_id=? WHERE id=?");
            $stmt->execute([$name, $teacher_id, $id]);
            $success = "Class updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating class: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_class'])) {
        $id = $_POST['class_id'];
        
        try {
            // First remove class references from students
            $stmt = $pdo->prepare("UPDATE students SET class_id=NULL WHERE class_id=?");
            $stmt->execute([$id]);
            
            // Then delete the class
            $stmt = $pdo->prepare("DELETE FROM classes WHERE id=?");
            $stmt->execute([$id]);
            
            $success = "Class deleted successfully!";
        } catch(PDOException $e) {
            $error = "Error deleting class: " . $e->getMessage();
        }
    }
}

// Fetch teachers for dropdown
$teachers = $pdo->query("SELECT * FROM users WHERE role='teacher'")->fetchAll();

// Fetch classes with teacher information
$classes = $pdo->query("
    SELECT c.*, u.name as teacher_name 
    FROM classes c 
    LEFT JOIN users u ON c.teacher_id = u.id 
    ORDER BY c.name
")->fetchAll();
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Class Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                    <i class="fas fa-plus"></i> Add New Class
                </button>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <?php foreach($classes as $class): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($class['name']); ?></h5>
                            <p class="card-text">
                                <?php if($class['teacher_name']): ?>
                                    <strong>Class Teacher:</strong> <?php echo htmlspecialchars($class['teacher_name']); ?>
                                <?php else: ?>
                                    <em>No class teacher assigned</em>
                                <?php endif; ?>
                            </p>
                            
                            <?php
                            // Count students in this class
                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE class_id=? AND status='active'");
                            $stmt->execute([$class['id']]);
                            $student_count = $stmt->fetch()['count'];
                            ?>
                            
                            <p class="card-text">
                                <strong>Students:</strong> <?php echo $student_count; ?>
                            </p>
                            
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="editClass(<?php echo $class['id']; ?>, '<?php echo $class['name']; ?>', <?php echo $class['teacher_id'] ?? 'null'; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteClass(<?php echo $class['id']; ?>, '<?php echo $class['name']; ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_class" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Class Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Class Teacher</label>
                        <select class="form-control" name="teacher_id">
                            <option value="">Select Teacher (Optional)</option>
                            <?php foreach($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Class Modal -->
<div class="modal fade" id="editClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="update_class" value="1">
                    <input type="hidden" name="class_id" id="edit_class_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Class Name</label>
                        <input type="text" class="form-control" name="name" id="edit_class_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Class Teacher</label>
                        <select class="form-control" name="teacher_id" id="edit_teacher_id">
                            <option value="">No Teacher Assigned</option>
                            <?php foreach($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Class Modal -->
<div class="modal fade" id="deleteClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="delete_class" value="1">
                    <input type="hidden" name="class_id" id="delete_class_id">
                    <p>Are you sure you want to delete <strong id="delete_class_name"></strong>? This will remove the class assignment from all students but will not delete the students.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editClass(id, name, teacher_id) {
    document.getElementById('edit_class_id').value = id;
    document.getElementById('edit_class_name').value = name;
    document.getElementById('edit_teacher_id').value = teacher_id || '';
    new bootstrap.Modal(document.getElementById('editClassModal')).show();
}

function deleteClass(id, name) {
    document.getElementById('delete_class_id').value = id;
    document.getElementById('delete_class_name').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteClassModal')).show();
}
</script>

<?php include 'includes/admin_footer.php'; ?>