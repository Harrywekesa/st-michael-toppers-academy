<!-- admin/audit_logs.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Create audit_logs table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        user_name VARCHAR(100),
        action VARCHAR(255),
        description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
} catch(PDOException $e) {
    // Table creation failed, but we'll continue
}

// Function to log actions
function logAction($pdo, $user_id, $user_name, $action, $description = '') {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, user_name, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $user_name, $action, $description, $ip_address, $user_agent]);
    } catch(PDOException $e) {
        // Log error silently
    }
}

// Log current visit to audit logs
logAction($pdo, $_SESSION['user_id'], $_SESSION['user_name'], 'Viewed Audit Logs', 'Accessed system audit logs page');

// Handle clear logs
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    try {
        $stmt = $pdo->prepare("DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
        $stmt->execute();
        $success = "Old audit logs (older than 90 days) cleared successfully!";
    } catch(PDOException $e) {
        $error = "Error clearing logs: " . $e->getMessage();
    }
}

// Fetch audit logs with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM audit_logs");
$total_logs = $stmt->fetch()['count'];
$total_pages = ceil($total_logs / $limit);

// Get logs for current page - FIXED SQL SYNTAX
$stmt = $pdo->prepare("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Audit Logs</h1>
                <a href="?action=clear" class="btn btn-danger" onclick="return confirm('This will delete audit logs older than 90 days. Continue?')">
                    <i class="fas fa-trash-alt"></i> Clear Old Logs
                </a>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5>System Activity Log</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($logs)): ?>
                        <p class="text-muted">No audit logs found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Description</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($logs as $log): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                                        <td><?php echo htmlspecialchars($log['description']); ?></td>
                                        <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if($total_pages > 1): ?>
                        <nav aria-label="Audit logs pagination">
                            <ul class="pagination justify-content-center">
                                <?php if($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Log Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h6>Total Logs</h6>
                                    <h3><?php echo $total_logs; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6>Today's Logs</h6>
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM audit_logs WHERE DATE(created_at) = CURDATE()");
                                    $today_logs = $stmt->fetch()['count'];
                                    ?>
                                    <h3><?php echo $today_logs; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body text-center">
                                    <h6>This Week</h6>
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                                    $week_logs = $stmt->fetch()['count'];
                                    ?>
                                    <h3><?php echo $week_logs; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h6>Unique Users</h6>
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as count FROM audit_logs");
                                    $unique_users = $stmt->fetch()['count'];
                                    ?>
                                    <h3><?php echo $unique_users; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>