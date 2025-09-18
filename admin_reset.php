<?php
include 'includes/db.php';

// Change this to your admin email
$admin_email = 'admin@test.com';
$new_password = 'admin@test.com';

$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password=? WHERE email=? AND role='admin'");
$stmt->execute([$hashed_password, $admin_email]);

echo "Admin password reset successfully!<br>";
echo "Email: $admin_email<br>";
echo "New Password: $new_password<br>";
echo "<a href='login.php'>Login Now</a>";

// Delete this file after use for security!
?>