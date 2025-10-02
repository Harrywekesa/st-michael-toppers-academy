<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require __DIR__ . '/vendor/autoload.php'; // composer autoload
include 'includes/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(50));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)")
            ->execute([$user['id'], $token, $expires]);

        $resetLink = "https://stmichaeltoppersacademy.co.ke/reset_password.php?token=$token";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'mail.stmichaeltoppersacademy.co.ke';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'kyalo@stmichaeltoppersacademy.co.ke';
            $mail->Password   = 'kyalo@stmichaeltoppersacademy.co.ke';
            $mail->SMTPSecure = 'ssl';
            $mail->Port       = 465;

            $mail->setFrom('kyalo@stmichaeltoppersacademy.co.ke', 'St. Michael Toppers Academy');
            $mail->addAddress($email, $user['name']);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "Hi {$user['name']},<br>Click <a href='$resetLink'>here</a> to reset your password. Link expires in 1 hour.";

            $mail->send();
            $msg = "<div class='alert alert-success'>A reset link has been sent to your email.</div>";
        } catch (Exception $e) {
            $msg = "<div class='alert alert-danger'>Mailer Error: {$mail->ErrorInfo}</div>";
        }
    } else {
        $msg = "<div class='alert alert-info'>If that email exists, a reset link has been sent.</div>";
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4>Forgot Password</h4>
                </div>
                <div class="card-body">
                    <?php echo $msg; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Enter Your Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Send Reset Link</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <a href="login.php">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
