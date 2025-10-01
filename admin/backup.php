<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Handle backup creation
if (isset($_GET['action']) && $_GET['action'] == 'create_backup') {
    try {
        // Get database name
        $stmt = $pdo->query("SELECT DATABASE()");
        $db_name = $stmt->fetchColumn();
        
        // Create backup filename
        $filename = "backup_" . $db_name . "_" . date('Y-m-d_H-i-s') . ".sql";
        $backup_file = "../backups/" . $filename;
        
        // Create backups directory if it doesn't exist
        if (!is_dir('../backups')) {
            mkdir('../backups', 0777, true);
        }
        
        // Get all table names
        $tables = [];
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        // Create backup content
        $backup_content = "-- Database Backup\n";
        $backup_content .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            // Get table structure
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $backup_content .= "\n-- Table structure for table `$table`\n";
            $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
            $backup_content .= $row[1] . ";\n\n";
            
            // Get table data
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $num_fields = $stmt->columnCount();
            
            if ($stmt->rowCount() > 0) {
                $backup_content .= "-- Dumping data for table `$table`\n";
                
                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    $backup_content .= "INSERT INTO `$table` VALUES(";
                    for ($i = 0; $i < $num_fields; $i++) {
                        if (isset($row[$i])) {
                            $backup_content .= "'" . addslashes($row[$i]) . "'";
                        } else {
                            $backup_content .= "NULL";
                        }
                        if ($i < ($num_fields - 1)) {
                            $backup_content .= ",";
                        }
                    }
                    $backup_content .= ");\n";
                }
                $backup_content .= "\n";
            }
        }
        
        // Save backup to file
        file_put_contents($backup_file, $backup_content);
        $success = "Backup created successfully: $filename";
    } catch(Exception $e) {
        $error = "Error creating backup: " . $e->getMessage();
    }
}

// Get list of existing backups
$backups = [];
if (is_dir('../backups')) {
    $files = scandir('../backups');
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $backups[] = [
                'name' => $file,
                'size' => filesize("../backups/$file"),
                'date' => date('M j, Y g:i A', filemtime("../backups/$file"))
            ];
        }
    }
    // Sort by date (newest first)
    usort($backups, function($a, $b) {
        return filemtime("../backups/{$b['name']}") - filemtime("../backups/{$a['name']}");
    });
}
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Backup & Security</h1>
                <a href="?action=create_backup" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Create Backup
                </a>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Database Backups</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($backups)): ?>
                                <p class="text-muted">No backups found. Create your first backup.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Backup File</th>
                                                <th>Size</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($backups as $backup): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($backup['name']); ?></td>
                                                <td><?php echo round($backup['size'] / 1024, 2); ?> KB</td>
                                                <td><?php echo $backup['date']; ?></td>
                                                <td>
                                                    <a href="../backups/<?php echo urlencode($backup['name']); ?>" 
                                                       class="btn btn-sm btn-success" 
                                                       download>
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-danger" 
                                                            onclick="deleteBackup('<?php echo htmlspecialchars($backup['name'], ENT_QUOTES); ?>')">
                                                        <i class="fas fa-trash"></i>
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
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Security Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Password Policy</label>
                                <p class="text-muted">Passwords must be at least 8 characters long and include uppercase, lowercase, and numbers.</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Session Timeout</label>
                                <p class="text-muted">Users are automatically logged out after 30 minutes of inactivity.</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Two-Factor Authentication</label>
                                <p class="text-muted">Coming soon - Optional 2FA for admin accounts.</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Login Attempts</label>
                                <p class="text-muted">Account lockout after 5 failed login attempts.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>System Information</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                            <p><strong>Database:</strong> MySQL</p>
                            <p><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                            <p><strong>Total Backups:</strong> <?php echo count($backups); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Delete Backup Modal -->
<div class="modal fade" id="deleteBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Backup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the backup <strong id="delete_backup_name"></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteBackup()">Delete Backup</button>
            </div>
        </div>
    </div>
</div>

<script>
let backupToDelete = '';

function deleteBackup(filename) {
    backupToDelete = filename;
    document.getElementById('delete_backup_name').textContent = filename;
    new bootstrap.Modal(document.getElementById('deleteBackupModal')).show();
}

function confirmDeleteBackup() {
    window.location.href = '?action=delete_backup&file=' + encodeURIComponent(backupToDelete);
}

<?php if (isset($_GET['action']) && $_GET['action'] == 'delete_backup' && isset($_GET['file'])): ?>
<?php
    $file = basename($_GET['file']);
    $backup_path = "../backups/$file";
    if (file_exists($backup_path) && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
        unlink($backup_path);
        echo "window.location.href = '?';";
    }
?>
<?php endif; ?>
</script>

<?php include 'includes/admin_footer.php'; ?>