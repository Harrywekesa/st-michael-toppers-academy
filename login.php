<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'includes/db.php';
$base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        
        // Redirect based on role
        switch ($user['role']) {
            case 'admin':
                header("Location: {$base}/admin/dashboard.php");
                break;
            case 'teacher':
                header("Location: {$base}/admin/teacher_dashboard.php");
                break;
            case 'accountant':
                header("Location: {$base}/admin/accountant_dashboard.php");
                break;
            case 'parent':
                header("Location: {$base}/admin/parent_dashboard.php");
                break;
            default:
                // fallback if role is unknown
                header("Location: {$base}/login.php");
        }
        exit();
    } else {
        $error = 'Invalid email or password';
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white text-center">
                    <h4>St. Michael Toppers Academy</h4>
                    <p class="mb-0">Login to Your Account</p>
                </div>
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="student_application.php">Apply for Admission</a> | 
                        <a href="forgot_password.php">Forgot Password?</a>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>