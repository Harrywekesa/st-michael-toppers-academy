<!-- admin/fee_structure.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'accountant') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['set_fee'])) {
        $class_id = $_POST['class_id'];
        $term = $_POST['term'];
        $year = $_POST['year'];
        $amount = $_POST['amount'];
        
        try {
            // Check if fee structure already exists
            $stmt = $pdo->prepare("SELECT id FROM class_fees WHERE class_id=? AND term=? AND year=?");
            $stmt->execute([$class_id, $term, $year]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing fee
                $stmt = $pdo->prepare("UPDATE class_fees SET amount=? WHERE id=?");
                $stmt->execute([$amount, $existing['id']]);
            } else {
                // Insert new fee
                $stmt = $pdo->prepare("INSERT INTO class_fees (class_id, term, year, amount) VALUES (?, ?, ?, ?)");
                $stmt->execute([$class_id, $term, $year, $amount]);
            }
            $success = "Class fee structure updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating fee structure: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_fee'])) {
        $fee_id = $_POST['fee_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM class_fees WHERE id = ?");
            $stmt->execute([$fee_id]);
            $success = "Fee structure deleted successfully!";
        } catch(PDOException $e) {
            $error = "Error deleting fee structure: " . $e->getMessage();
        }
    }
}

// Create class_fees table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS class_fees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_id INT,
        term ENUM('Term 1', 'Term 2', 'Term 3') NOT NULL,
        year YEAR NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (class_id) REFERENCES classes(id)
    )");
} catch(PDOException $e) {
    // Table creation failed, but we'll continue
}

// Fetch classes
$classes = $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll();

// Fetch all class fees
$stmt = $pdo->query("
    SELECT cf.*, c.name as class_name
    FROM class_fees cf
    JOIN classes c ON cf.class_id = c.id
    ORDER BY cf.year DESC, cf.term, c.name
");
$class_fees = $stmt->fetchAll();
?>

<?php include 'includes/accountant_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/accountant_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Fee Structure</h1>
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
                            <h5>Set Class Fee Structure</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="set_fee" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label">Class</label>
                                    <select class="form-control" name="class_id" required>
                                        <option value="">Select Class</option>
                                        <?php foreach($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>">
                                                <?php echo htmlspecialchars($class['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Term</label>
                                    <select class="form-control" name="term" required>
                                        <option value="">Select Term</option>
                                        <option value="Term 1">Term 1</option>
                                        <option value="Term 2">Term 2</option>
                                        <option value="Term 3">Term 3</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Year</label>
                                    <input type="number" class="form-control" name="year" min="2010" max="2030" value="<?php echo date('Y'); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Amount (KES)</label>
                                    <input type="number" class="form-control" name="amount" min="0" step="0.01" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Set Fee</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Fee Structure Overview</h5>
                        </div>
                        <div class="card-body">
                            <p>Set different fee amounts for different classes and terms.</p>
                            <p>Current system has <?php echo count($classes); ?> classes with <?php echo count($class_fees); ?> fee structures defined.</p>
                            
                            <div class="mt-3">
                                <h6>Quick Stats</h6>
                                <?php
                                // Get current year fee structures
                                $current_year = date('Y');
                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM class_fees WHERE year = ?");
                                $stmt->execute([$current_year]);
                                $current_year_fees = $stmt->fetch()['count'];
                                ?>
                                <p>Fee structures for <?php echo $current_year; ?>: <?php echo $current_year_fees; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>All Class Fee Structures</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($class_fees)): ?>
                        <div class="alert alert-info">No class fee structures defined yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>Term</th>
                                        <th>Year</th>
                                        <th>Amount (KES)</th>
                                        <th>Date Set</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($class_fees as $fee): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($fee['class_name']); ?></td>
                                        <td><?php echo htmlspecialchars($fee['term']); ?></td>
                                        <td><?php echo htmlspecialchars($fee['year']); ?></td>
                                        <td><?php echo number_format($fee['amount'], 2); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($fee['created_at'])); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="delete_fee" value="1">
                                                <input type="hidden" name="fee_id" value="<?php echo $fee['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this fee structure?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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

<?php include 'includes/accountant_footer.php'; ?>