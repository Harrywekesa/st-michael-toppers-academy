<!-- admin/settings.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Create system_settings table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_name VARCHAR(255),
        school_motto VARCHAR(255),
        school_address TEXT,
        school_phone VARCHAR(50),
        school_email VARCHAR(100),
        academic_year_start DATE,
        academic_year_end DATE,
        term_1_start DATE,
        term_1_end DATE,
        term_2_start DATE,
        term_2_end DATE,
        term_3_start DATE,
        term_3_end DATE,
        max_login_attempts INT DEFAULT 5,
        session_timeout INT DEFAULT 1800,
        enable_sms_notifications TINYINT(1) DEFAULT 0,
        enable_email_notifications TINYINT(1) DEFAULT 1,
        sms_api_key VARCHAR(255),
        email_smtp_host VARCHAR(100),
        email_smtp_port INT DEFAULT 587,
        email_username VARCHAR(100),
        email_password VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch(PDOException $e) {
    $error = "Error creating settings table: " . $e->getMessage();
}

// Fetch current settings
$stmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
$settings = $stmt->fetch();

// If no settings exist, create default ones
if (!$settings) {
    $default_settings = [
        'school_name' => 'St. Michael Toppers Academy',
        'school_motto' => 'Excellence in Education since 2011',
        'school_address' => '123 Education Street, Nairobi, Kenya',
        'school_phone' => '+254 700 123 456',
        'school_email' => 'info@stmichaeltoppers.ac.ke',
        'academic_year_start' => date('Y') . '-01-01',
        'academic_year_end' => date('Y') . '-12-31',
        'term_1_start' => date('Y') . '-01-01',
        'term_1_end' => date('Y') . '-04-30',
        'term_2_start' => date('Y') . '-05-01',
        'term_2_end' => date('Y') . '-08-31',
        'term_3_start' => date('Y') . '-09-01',
        'term_3_end' => date('Y') . '-12-31',
        'max_login_attempts' => 5,
        'session_timeout' => 1800, // 30 minutes
        'enable_sms_notifications' => 0,
        'enable_email_notifications' => 1,
        'sms_api_key' => '',
        'email_smtp_host' => 'smtp.gmail.com',
        'email_smtp_port' => 587,
        'email_username' => '',
        'email_password' => ''
    ];
    
    // Insert default settings
    $columns = implode(', ', array_keys($default_settings));
    $placeholders = ':' . implode(', :', array_keys($default_settings));
    $stmt = $pdo->prepare("INSERT INTO system_settings ($columns) VALUES ($placeholders)");
    $stmt->execute($default_settings);
    
    $stmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
    $settings = $stmt->fetch();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_general'])) {
        try {
            $stmt = $pdo->prepare("UPDATE system_settings SET 
                school_name=?, school_motto=?, school_address=?, 
                school_phone=?, school_email=? WHERE id=1");
            $stmt->execute([
                $_POST['school_name'],
                $_POST['school_motto'],
                $_POST['school_address'],
                $_POST['school_phone'],
                $_POST['school_email']
            ]);
            $success = "General settings updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating settings: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_academic'])) {
        try {
            $stmt = $pdo->prepare("UPDATE system_settings SET 
                academic_year_start=?, academic_year_end=?,
                term_1_start=?, term_1_end=?,
                term_2_start=?, term_2_end=?,
                term_3_start=?, term_3_end=? WHERE id=1");
            $stmt->execute([
                $_POST['academic_year_start'],
                $_POST['academic_year_end'],
                $_POST['term_1_start'],
                $_POST['term_1_end'],
                $_POST['term_2_start'],
                $_POST['term_2_end'],
                $_POST['term_3_start'],
                $_POST['term_3_end']
            ]);
            $success = "Academic settings updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating settings: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_security'])) {
        try {
            $stmt = $pdo->prepare("UPDATE system_settings SET 
                max_login_attempts=?, session_timeout=? WHERE id=1");
            $stmt->execute([
                $_POST['max_login_attempts'],
                $_POST['session_timeout']
            ]);
            $success = "Security settings updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating settings: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_notifications'])) {
        try {
            $stmt = $pdo->prepare("UPDATE system_settings SET 
                enable_sms_notifications=?, enable_email_notifications=?,
                sms_api_key=?, email_smtp_host=?, email_smtp_port=?,
                email_username=?, email_password=? WHERE id=1");
            $stmt->execute([
                isset($_POST['enable_sms_notifications']) ? 1 : 0,
                isset($_POST['enable_email_notifications']) ? 1 : 0,
                $_POST['sms_api_key'],
                $_POST['email_smtp_host'],
                $_POST['email_smtp_port'],
                $_POST['email_username'],
                $_POST['email_password']
            ]);
            $success = "Notification settings updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating settings: " . $e->getMessage();
        }
    }
    
    // Refresh settings after update
    $stmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
    $settings = $stmt->fetch();
}
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">System Settings</h1>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-12">
                    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                <i class="fas fa-school"></i> General
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="academic-tab" data-bs-toggle="tab" data-bs-target="#academic" type="button" role="tab">
                                <i class="fas fa-calendar-alt"></i> Academic
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                                <i class="fas fa-shield-alt"></i> Security
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                                <i class="fas fa-bell"></i> Notifications
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="settingsTabContent">
                        <!-- General Settings -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5>General Information</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="update_general" value="1">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">School Name</label>
                                            <input type="text" class="form-control" name="school_name" value="<?php echo htmlspecialchars($settings['school_name'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">School Motto</label>
                                            <input type="text" class="form-control" name="school_motto" value="<?php echo htmlspecialchars($settings['school_motto'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">School Address</label>
                                            <textarea class="form-control" name="school_address" rows="3" required><?php echo htmlspecialchars($settings['school_address'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Phone Number</label>
                                                    <input type="text" class="form-control" name="school_phone" value="<?php echo htmlspecialchars($settings['school_phone'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Email Address</label>
                                                    <input type="email" class="form-control" name="school_email" value="<?php echo htmlspecialchars($settings['school_email'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Update General Settings</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Academic Settings -->
                        <div class="tab-pane fade" id="academic" role="tabpanel">
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5>Academic Calendar</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="update_academic" value="1">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Academic Year Start</label>
                                                    <input type="date" class="form-control" name="academic_year_start" value="<?php echo htmlspecialchars($settings['academic_year_start'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Academic Year End</label>
                                                    <input type="date" class="form-control" name="academic_year_end" value="<?php echo htmlspecialchars($settings['academic_year_end'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <h6>Term 1</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Start Date</label>
                                                    <input type="date" class="form-control" name="term_1_start" value="<?php echo htmlspecialchars($settings['term_1_start'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">End Date</label>
                                                    <input type="date" class="form-control" name="term_1_end" value="<?php echo htmlspecialchars($settings['term_1_end'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <h6>Term 2</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Start Date</label>
                                                    <input type="date" class="form-control" name="term_2_start" value="<?php echo htmlspecialchars($settings['term_2_start'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">End Date</label>
                                                    <input type="date" class="form-control" name="term_2_end" value="<?php echo htmlspecialchars($settings['term_2_end'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <h6>Term 3</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Start Date</label>
                                                    <input type="date" class="form-control" name="term_3_start" value="<?php echo htmlspecialchars($settings['term_3_start'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">End Date</label>
                                                    <input type="date" class="form-control" name="term_3_end" value="<?php echo htmlspecialchars($settings['term_3_end'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Update Academic Settings</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Security Settings -->
                        <div class="tab-pane fade" id="security" role="tabpanel">
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5>Security Configuration</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="update_security" value="1">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Maximum Login Attempts</label>
                                            <input type="number" class="form-control" name="max_login_attempts" value="<?php echo htmlspecialchars($settings['max_login_attempts'] ?? 5); ?>" min="1" max="20" required>
                                            <div class="form-text">Number of failed login attempts before account lockout</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Session Timeout (seconds)</label>
                                            <input type="number" class="form-control" name="session_timeout" value="<?php echo htmlspecialchars($settings['session_timeout'] ?? 1800); ?>" min="300" max="86400" required>
                                            <div class="form-text">Time before automatic logout (300 = 5 minutes, 1800 = 30 minutes)</div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Update Security Settings</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notification Settings -->
                        <div class="tab-pane fade" id="notifications" role="tabpanel">
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5>Notification Configuration</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="update_notifications" value="1">
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="enable_sms_notifications" id="enable_sms" <?php echo ($settings['enable_sms_notifications'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="enable_sms">
                                                Enable SMS Notifications
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="enable_email_notifications" id="enable_email" <?php echo ($settings['enable_email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="enable_email">
                                                Enable Email Notifications
                                            </label>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">SMS API Key</label>
                                            <input type="text" class="form-control" name="sms_api_key" value="<?php echo htmlspecialchars($settings['sms_api_key'] ?? ''); ?>">
                                            <div class="form-text">API key for SMS gateway service</div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Email SMTP Host</label>
                                                    <input type="text" class="form-control" name="email_smtp_host" value="<?php echo htmlspecialchars($settings['email_smtp_host'] ?? 'smtp.gmail.com'); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">SMTP Port</label>
                                                    <input type="number" class="form-control" name="email_smtp_port" value="<?php echo htmlspecialchars($settings['email_smtp_port'] ?? 587); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Email Username</label>
                                                    <input type="email" class="form-control" name="email_username" value="<?php echo htmlspecialchars($settings['email_username'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Email Password</label>
                                                    <input type="password" class="form-control" name="email_password" value="<?php echo htmlspecialchars($settings['email_password'] ?? ''); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Update Notification Settings</button>
                                    </form>
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