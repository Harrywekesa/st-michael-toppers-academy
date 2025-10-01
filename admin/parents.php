<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Create parent_details table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS parent_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        parent_id INT UNIQUE,
        phone VARCHAR(20),
        id_number VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE
    )");
} catch(PDOException $e) {
    // Continue silently if table creation fails
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_parent'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $phone = $_POST['phone'] ?? '';
        $id_number = $_POST['id_number'] ?? '';
        
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'parent'");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Parent with this email already exists.";
            } else {
                // Use email as default password if not provided
                if (empty($password)) {
                    $password = $email;
                }
                
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert parent
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'parent')");
                $stmt->execute([$name, $email, $hashed_password]);
                
                // Get inserted parent ID
                $parent_id = $pdo->lastInsertId();
                
                // Insert additional details
                if ($phone || $id_number) {
                    $stmt = $pdo->prepare("INSERT INTO parent_details (parent_id, phone, id_number) VALUES (?, ?, ?)");
                    $stmt->execute([$parent_id, $phone, $id_number]);
                }
                
                $success = "Parent added successfully!";
            }
        } catch(PDOException $e) {
            $error = "Error adding parent: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_parent'])) {
        $id = $_POST['parent_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'] ?? '';
        $id_number = $_POST['id_number'] ?? '';
        
        try {
            // Check if email already exists for another parent
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'parent' AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                $error = "Another parent with this email already exists.";
            } else {
                // Update parent
                $stmt = $pdo->prepare("UPDATE users SET name=?, email=? WHERE id=? AND role='parent'");
                $stmt->execute([$name, $email, $id]);
                
                // Update or insert additional details
                $stmt = $pdo->prepare("INSERT INTO parent_details (parent_id, phone, id_number) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE phone=VALUES(phone), id_number=VALUES(id_number)");
                $stmt->execute([$id, $phone, $id_number]);
                
                $success = "Parent updated successfully!";
            }
        } catch(PDOException $e) {
            $error = "Error updating parent: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_parent'])) {
        $id = $_POST['parent_id'];
        
        try {
            // Check if parent has students
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE parent_id = ?");
            $stmt->execute([$id]);
            $student_count = $stmt->fetch()['count'];
            
            if ($student_count > 0) {
                $error = "Cannot delete parent with associated students. Please reassign students first.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id=? AND role='parent'");
                $stmt->execute([$id]);
                $success = "Parent deleted successfully!";
            }
        } catch(PDOException $e) {
            $error = "Error deleting parent: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['reset_password'])) {
        $id = $_POST['parent_id'];
        $new_password = $_POST['new_password'];
        
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=? AND role='parent'");
            $stmt->execute([$hashed_password, $id]);
            $success = "Password reset successfully!";
        } catch(PDOException $e) {
            $error = "Error resetting password: " . $e->getMessage();
        }
    }
}

// Fetch all parents with student count and details
try {
    $parents = $pdo->query("
        SELECT u.*, 
               COALESCE(pd.phone, '') as phone, 
               COALESCE(pd.id_number, '') as id_number,
               COUNT(s.id) as student_count
        FROM users u
        LEFT JOIN parent_details pd ON u.id = pd.parent_id
        LEFT JOIN students s ON u.id = s.parent_id
        WHERE u.role = 'parent'
        GROUP BY u.id, u.name, u.email, u.created_at, pd.phone, pd.id_number
        ORDER BY u.name
    ")->fetchAll();
} catch(PDOException $e) {
    // Fallback query without parent_details
    $parents = $pdo->query("
        SELECT u.*, '' as phone, '' as id_number, COUNT(s.id) as student_count
        FROM users u
        LEFT JOIN students s ON u.id = s.parent_id
        WHERE u.role = 'parent'
        GROUP BY u.id, u.name, u.email, u.created_at
        ORDER BY u.name
    ")->fetchAll();
}
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Parent Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addParentModal">
                    <i class="fas fa-plus"></i> Add New Parent
                </button>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5>All Parents</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($parents)): ?>
                        <div class="alert alert-info">No parents found. Add your first parent.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>ID Number</th>
                                        <th>Students</th>
                                        <th>Member Since</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($parents as $parent): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($parent['name']); ?></td>
                                        <td><?php echo htmlspecialchars($parent['email']); ?></td>
                                        <td><?php echo htmlspecialchars($parent['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($parent['id_number'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $parent['student_count'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo $parent['student_count']; ?> student(s)
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($parent['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button class="btn btn-outline-primary" 
                                                        onclick="editParent(<?php echo $parent['id']; ?>, '<?php echo htmlspecialchars($parent['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($parent['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($parent['phone'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($parent['id_number'] ?? '', ENT_QUOTES); ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-warning" 
                                                        onclick="resetPassword(<?php echo $parent['id']; ?>, '<?php echo htmlspecialchars($parent['name'], ENT_QUOTES); ?>')">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <?php if ($parent['student_count'] == 0): ?>
                                                <button class="btn btn-outline-danger" 
                                                        onclick="deleteParent(<?php echo $parent['id']; ?>, '<?php echo htmlspecialchars($parent['name'], ENT_QUOTES); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
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

<!-- Add Parent Modal -->
<div class="modal fade" id="addParentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Parent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_parent" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="text" class="form-control" name="password" id="add_password">
                        <div class="form-text">Leave blank to use email as default password</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">ID Number</label>
                        <input type="text" class="form-control" name="id_number">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Parent</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Parent Modal -->
<div class="modal fade" id="editParentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Parent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="update_parent" value="1">
                    <input type="hidden" name="parent_id" id="edit_parent_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="name" id="edit_parent_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" class="form-control" name="email" id="edit_parent_email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" class="form-control" name="phone" id="edit_parent_phone">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">ID Number</label>
                        <input type="text" class="form-control" name="id_number" id="edit_parent_id_number">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Parent</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Parent Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="reset_password" value="1">
                    <input type="hidden" name="parent_id" id="reset_parent_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Reset password for <strong id="reset_parent_name"></strong></label>
                        <input type="password" class="form-control" name="new_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Parent Modal -->
<div class="modal fade" id="deleteParentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Parent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="delete_parent" value="1">
                    <input type="hidden" name="parent_id" id="delete_parent_id">
                    <p>Are you sure you want to delete <strong id="delete_parent_name"></strong>? This action cannot be undone.</p>
                    <p class="text-danger"><small>Note: Parents with associated students cannot be deleted.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Parent</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editParent(id, name, email, phone, id_number) {
    document.getElementById('edit_parent_id').value = id;
    document.getElementById('edit_parent_name').value = name;
    document.getElementById('edit_parent_email').value = email;
    document.getElementById('edit_parent_phone').value = phone;
    document.getElementById('edit_parent_id_number').value = id_number;
    new bootstrap.Modal(document.getElementById('editParentModal')).show();
}

function resetPassword(id, name) {
    document.getElementById('reset_parent_id').value = id;
    document.getElementById('reset_parent_name').textContent = name;
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}

function deleteParent(id, name) {
    document.getElementById('delete_parent_id').value = id;
    document.getElementById('delete_parent_name').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteParentModal')).show();
}

// Set default password to email when adding parent
document.getElementById('addParentModal').addEventListener('show.bs.modal', function () {
    const emailInput = document.querySelector('#addParentModal input[name="email"]');
    const passwordInput = document.querySelector('#addParentModal input[name="password"]');
    
    if (emailInput && passwordInput) {
        emailInput.addEventListener('input', function() {
            passwordInput.placeholder = 'Leave blank to use ' + this.value + ' as password';
        });
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>