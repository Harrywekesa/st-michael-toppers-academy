<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}


include '../includes/db.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_student'])) {
        $name = $_POST['name'];
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $admission_no = $_POST['admission_no'];
        $class_id = $_POST['class_id'] ?: null;
        $parent_id = $_POST['parent_id'] ?: null;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO students (name, dob, gender, admission_no, class_id, parent_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $dob, $gender, $admission_no, $class_id, $parent_id]);
            $success = "Student added successfully!";
        } catch(PDOException $e) {
            $error = "Error adding student: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_student'])) {
        $id = $_POST['student_id'];
        $name = $_POST['name'];
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $admission_no = $_POST['admission_no'];
        $class_id = $_POST['class_id'] ?: null;
        $status = $_POST['status'];
        
        try {
            $stmt = $pdo->prepare("UPDATE students SET name=?, dob=?, gender=?, admission_no=?, class_id=?, status=? WHERE id=?");
            $stmt->execute([$name, $dob, $gender, $admission_no, $class_id, $status, $id]);
            $success = "Student updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating student: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_student'])) {
        $id = $_POST['student_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id=?");
            $stmt->execute([$id]);
            $success = "Student deleted successfully!";
        } catch(PDOException $e) {
            $error = "Error deleting student: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['assign_class'])) {
        $student_id = $_POST['student_id'];
        $class_id = $_POST['class_id'];
        
        try {
            $stmt = $pdo->prepare("UPDATE students SET class_id=? WHERE id=?");
            $stmt->execute([$class_id, $student_id]);
            $success = "Student assigned to class successfully!";
        } catch(PDOException $e) {
            $error = "Error assigning student to class: " . $e->getMessage();
        }
    }
}

// Fetch classes for dropdown
$classes = $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll();

// Fetch students with class information
$students = $pdo->query("
    SELECT s.*, c.name as class_name 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    ORDER BY s.admission_no
")->fetchAll();

// Fetch parents for dropdown
$parents = $pdo->query("SELECT * FROM users WHERE role='parent' ORDER BY name")->fetchAll();

// Get students who are active but not assigned to a class (recently approved)
$query = "
  SELECT s.id, s.name, s.admission_no, s.gender, s.status, s.created_at,
         sa.status AS application_status, sa.grade_applying_for
  FROM students s
  LEFT JOIN student_applications sa ON sa.student_id = s.id
  ORDER BY s.created_at DESC
";

?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php 
        if ($_SESSION['user_role'] == 'admin') {
            include 'includes/admin_sidebar.php';
        } else {
            include 'includes/teacher_sidebar.php';
        }
        ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Student Management</h1>
                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fas fa-plus"></i> Add New Student
                </button>
                <?php endif; ?>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Recently Approved Students (Need Class Assignment) -->
            <?php if ($_SESSION['user_role'] == 'admin' && !empty($unassigned_students)): ?>
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5><i class="fas fa-exclamation-circle"></i> Recently Approved Students (Need Class Assignment)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Admission No</th>
                                    <th>Student Name</th>
                                    <th>Applied For</th>
                                    <th>Parent</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($unassigned_students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                    <td><?php echo htmlspecialchars($student['name'] ?? $student['application_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['grade_applying_for'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        // Get parent name
                                        $stmt2 = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                                        $stmt2->execute([$student['parent_id']]);
                                        $parent = $stmt2->fetch();
                                        echo $parent ? htmlspecialchars($parent['name']) : 'N/A';
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="assignClass(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name'] ?? $student['application_name'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-school"></i> Assign Class
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5>All Students</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Admission No</th>
                                    <th>Name</th>
                                    <th>Class</th>
                                    <th>Gender</th>
                                    <th>Date of Birth</th>
                                    <th>Status</th>
                                    <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['admission_no']); ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['class_name'] ?? 'Unassigned'); ?></td>
                                    <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($student['dob'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $student['status'] == 'active' ? 'success' : 
                                                ($student['status'] == 'inactive' ? 'danger' : 'warning'); ?>">
                                            <?php echo ucfirst($student['status']); ?>
                                        </span>
                                    </td>
                                    <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editStudent(<?php echo $student['id']; ?>, '<?php echo $student['name']; ?>', '<?php echo $student['dob']; ?>', '<?php echo $student['gender']; ?>', '<?php echo $student['admission_no']; ?>', <?php echo $student['class_id'] ?? 'null'; ?>, '<?php echo $student['status']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteStudent(<?php echo $student['id']; ?>, '<?php echo $student['name']; ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php if ($_SESSION['user_role'] == 'admin'): ?>
<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_student" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" name="dob" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select class="form-control" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Admission Number</label>
                        <input type="text" class="form-control" name="admission_no" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Class</label>
                        <select class="form-control" name="class_id">
                            <option value="">Select Class (Optional)</option>
                            <?php foreach($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Parent/Guardian</label>
                        <select class="form-control" name="parent_id">
                            <option value="">Select Parent (Optional)</option>
                            <?php foreach($parents as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>">
                                    <?php echo htmlspecialchars($parent['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="update_student" value="1">
                    <input type="hidden" name="student_id" id="edit_student_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" name="dob" id="edit_dob" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select class="form-control" name="gender" id="edit_gender" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Admission Number</label>
                        <input type="text" class="form-control" name="admission_no" id="edit_admission_no" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Class</label>
                        <select class="form-control" name="class_id" id="edit_class_id">
                            <option value="">No Class Assigned</option>
                            <?php foreach($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status" id="edit_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="transferred">Transferred</option>
                            <option value="graduated">Graduated</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Student Modal -->
<div class="modal fade" id="deleteStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="delete_student" value="1">
                    <input type="hidden" name="student_id" id="delete_student_id">
                    <p>Are you sure you want to delete <strong id="delete_student_name"></strong>? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Class Modal -->
<div class="modal fade" id="assignClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Student to Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="assign_class" value="1">
                    <input type="hidden" name="student_id" id="assign_student_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <input type="text" class="form-control" id="assign_student_name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Select Class</label>
                        <select class="form-control" name="class_id" required>
                            <option value="">Select Class</option>
                            <?php foreach($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Class</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
<?php if ($_SESSION['user_role'] == 'admin'): ?>
function editStudent(id, name, dob, gender, admission_no, class_id, status) {
    document.getElementById('edit_student_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_dob').value = dob;
    document.getElementById('edit_gender').value = gender;
    document.getElementById('edit_admission_no').value = admission_no;
    document.getElementById('edit_class_id').value = class_id || '';
    document.getElementById('edit_status').value = status;
    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
}

function deleteStudent(id, name) {
    document.getElementById('delete_student_id').value = id;
    document.getElementById('delete_student_name').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteStudentModal')).show();
}

function assignClass(studentId, studentName) {
    document.getElementById('assign_student_id').value = studentId;
    document.getElementById('assign_student_name').value = studentName;
    new bootstrap.Modal(document.getElementById('assignClassModal')).show();
}
<?php endif; ?>
</script>

<?php include 'includes/admin_footer.php'; ?>