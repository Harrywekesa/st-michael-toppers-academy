<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require __DIR__ . '/vendor/autoload.php';
include 'includes/db.php';

$msg = '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password === $confirm_password) {
        // Check if token is valid
        $stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if ($reset && strtotime($reset['expires_at']) > time()) {
            // Update password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                ->execute([$hashedPassword, $reset['user_id']]);

            // Delete used token
            $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);

            $msg = "<div class='alert alert-success'>Password reset successful. <a href='login.php'>Login here</a>.</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Invalid or expired token.</div>";
        }
    } else {
        $msg = "<div class='alert alert-warning'>Passwords do not match.</div>";
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4>Reset Password</h4>
                </div>
                <div class="card-body">
                    <?php echo $msg; ?>
                    <?php if ($token && empty($msg)): ?>
                        <form method="POST">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Reset Password</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="login.php">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
