<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'parent') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Cards - Parent Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <button class="sidebar-toggle d-md-none">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/parent_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Report Cards</h1>
                </div>
                
                <?php
                // Get parent's children
                $stmt = $pdo->prepare("SELECT * FROM students WHERE parent_id = ? AND status = 'active'");
                $stmt->execute([$_SESSION['user_id']]);
                $children = $stmt->fetchAll();
                ?>
                
                <?php if(empty($children)): ?>
                    <div class="alert alert-info">
                        <p>You don't have any children registered in the system.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach($children as $child): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><?php echo htmlspecialchars($child['name']); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Admission No:</strong> <?php echo htmlspecialchars($child['admission_no']); ?></p>
                                        <?php
                                        // Get class name
                                        $stmt2 = $pdo->prepare("SELECT name FROM classes WHERE id = ?");
                                        $stmt2->execute([$child['class_id']]);
                                        $class = $stmt2->fetch();
                                        ?>
                                        <p><strong>Class:</strong> <?php echo $class ? htmlspecialchars($class['name']) : 'Not assigned'; ?></p>
                                        
                                        <a href="parent_report_card.php?student_id=<?php echo $child['id']; ?>" class="btn btn-primary w-100">
                                            <i class="fas fa-file-alt"></i> View Report Card
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>