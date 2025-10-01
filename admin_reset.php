<?php
include 'includes/db.php';

// Admin details
$admin_email = 'kyalo@stmichaeltoppers.co.ke';
$admin_name  = 'Kyalo';
$new_password = 'kyalo@stmichaeltoppers.co.ke';

// Hash password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Check if admin exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email=? AND role='admin' LIMIT 1");
$stmt->execute([$admin_email]);
$admin = $stmt->fetch();

if ($admin) {
    // Update password
    $stmt = $pdo->prepare("UPDATE users SET password=?, name=? WHERE email=? AND role='admin'");
    $stmt->execute([$hashed_password, $admin_name, $admin_email]);
    echo "Admin account updated successfully.<br>";
} else {
    // Insert new admin
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
    $stmt->execute([$admin_name, $admin_email, $hashed_password]);
    echo "Admin account created successfully.<br>";
}

echo "Email: $admin_email<br>";
echo "Password: $new_password<br>";
echo "<a href='login.php'>Login Now</a>";

// SECURITY: Delete this file after running it once
?>
