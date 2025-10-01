<?php 
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

// Function to send SMS (placeholder - would integrate with actual SMS API)
function sendSMS($phone, $message) {
    // In a real implementation, this would connect to an SMS gateway API
    // For example: Twilio, AfricasTalking, etc.
    return true; // Simulate success
}

// Function to send email (placeholder - would integrate with actual email service)
function sendEmail($to, $subject, $message) {
    // In a real implementation, this would use PHPMailer or similar
    // with SMTP settings from system settings
    return true; // Simulate success
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['send_notification'])) {
        $recipient_type = $_POST['recipient_type'];
        $message = $_POST['message'];
        $send_sms = isset($_POST['send_sms']);
        $send_email = isset($_POST['send_email']);
        
        $recipients = [];
        
        // Get recipients based on type
        switch($recipient_type) {
            case 'all_parents':
                $stmt = $pdo->query("SELECT DISTINCT u.email, s.parent_id FROM students s JOIN users u ON s.parent_id = u.id WHERE s.status = 'active' AND u.email IS NOT NULL");
                $recipients = $stmt->fetchAll();
                break;
                
            case 'class_parents':
                $class_id = $_POST['class_id'];
                $stmt = $pdo->prepare("SELECT DISTINCT u.email, s.parent_id FROM students s JOIN users u ON s.parent_id = u.id WHERE s.class_id = ? AND s.status = 'active' AND u.email IS NOT NULL");
                $stmt->execute([$class_id]);
                $recipients = $stmt->fetchAll();
                break;
                
            case 'all_teachers':
                $stmt = $pdo->query("SELECT email, id FROM users WHERE role = 'teacher' AND email IS NOT NULL");
                $recipients = $stmt->fetchAll();
                break;
                
            case 'all_staff':
                $stmt = $pdo->query("SELECT email, id FROM users WHERE role IN ('teacher', 'accountant') AND email IS NOT NULL");
                $recipients = $stmt->fetchAll();
                break;
        }
        
        $success_count = 0;
        $error_count = 0;
        
        foreach($recipients as $recipient) {
            $sent = false;
            
            if ($send_email && !empty($recipient['email'])) {
                if (sendEmail($recipient['email'], 'School Notification', $message)) {
                    $sent = true;
                }
            }
            
            if ($send_sms) {
                // Would need phone numbers in real implementation
                // For now, we'll just simulate
                $sent = true;
            }
            
            if ($sent) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        $success = "Notification sent to $success_count recipients" . ($error_count > 0 ? " ($error_count failed)" : "");
    }
}

// Fetch classes for dropdown
$classes = $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll();
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Notifications</h1>
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
                            <h5>Send Notification</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="send_notification" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label">Recipient Type</label>
                                    <select class="form-control" name="recipient_type" id="recipient_type" required>
                                        <option value="">Select Recipient Type</option>
                                        <option value="all_parents">All Parents</option>
                                        <option value="class_parents">Parents in Specific Class</option>
                                        <option value="all_teachers">All Teachers</option>
                                        <option value="all_staff">All Staff</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3" id="class_selection" style="display: none;">
                                    <label class="form-label">Select Class</label>
                                    <select class="form-control" name="class_id">
                                        <option value="">Select Class</option>
                                        <?php foreach($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>">
                                                <?php echo htmlspecialchars($class['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Notification Channels</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="send_email" id="send_email" checked>
                                        <label class="form-check-label" for="send_email">
                                            Send via Email
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="send_sms" id="send_sms">
                                        <label class="form-check-label" for="send_sms">
                                            Send via SMS
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Message</label>
                                    <textarea class="form-control" name="message" rows="5" placeholder="Enter your notification message..." required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Send Notification</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Notification Templates</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Exam Schedule</label>
                                <button class="btn btn-outline-primary w-100" onclick="loadTemplate('exam_schedule')">
                                    <i class="fas fa-calendar-alt"></i> Load Template
                                </button>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Parent-Teacher Meeting</label>
                                <button class="btn btn-outline-primary w-100" onclick="loadTemplate('parent_meeting')">
                                    <i class="fas fa-users"></i> Load Template
                                </button>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Fee Payment Reminder</label>
                                <button class="btn btn-outline-primary w-100" onclick="loadTemplate('fee_reminder')">
                                    <i class="fas fa-money-bill-wave"></i> Load Template
                                </button>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">School Event</label>
                                <button class="btn btn-outline-primary w-100" onclick="loadTemplate('school_event')">
                                    <i class="fas fa-calendar-check"></i> Load Template
                                </button>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Emergency Alert</label>
                                <button class="btn btn-outline-danger w-100" onclick="loadTemplate('emergency')">
                                    <i class="fas fa-exclamation-triangle"></i> Load Template
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Notifications</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Recent notification history will appear here.</p>
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <span>Parent-Teacher Meeting Reminder</span>
                                        <small class="text-muted">2 hours ago</small>
                                    </div>
                                    <small class="text-muted">Sent to 125 parents</small>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <span>Exam Schedule Update</span>
                                        <small class="text-muted">1 day ago</small>
                                    </div>
                                    <small class="text-muted">Sent to 8 teachers</small>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <span>Fee Payment Reminder</span>
                                        <small class="text-muted">3 days ago</small>
                                    </div>
                                    <small class="text-muted">Sent to 45 parents</small>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Show/hide class selection based on recipient type
document.getElementById('recipient_type').addEventListener('change', function() {
    const classSelection = document.getElementById('class_selection');
    if (this.value === 'class_parents') {
        classSelection.style.display = 'block';
    } else {
        classSelection.style.display = 'none';
    }
});

// Load notification templates
function loadTemplate(template) {
    let message = '';
    
    switch(template) {
        case 'exam_schedule':
            message = "Dear Parents,\n\nThis is to inform you of the upcoming examination schedule for your children. Please ensure they are well prepared and arrive at school on time.\n\nExamination Dates: [Insert Dates]\nSubjects: [Insert Subjects]\n\nThank you for your continued support.\n\nBest regards,\nAdministration";
            break;
        case 'parent_meeting':
            message = "Dear Parents,\n\nYou are cordially invited to attend our Parent-Teacher Meeting scheduled as follows:\n\nDate: [Insert Date]\nTime: [Insert Time]\nVenue: [Insert Venue]\n\nPlease confirm your attendance by [Insert Date].\n\nWe look forward to seeing you.\n\nBest regards,\nAdministration";
            break;
        case 'fee_reminder':
            message = "Dear Parents,\n\nThis is a friendly reminder that the school fees for [Term] [Year] are due soon. Please ensure payment is made by [Due Date] to avoid any inconvenience.\n\nFor payment inquiries, please contact the accounts office.\n\nThank you.\n\nBest regards,\nAccounts Department";
            break;
        case 'school_event':
            message = "Dear Parents and Students,\n\nWe are excited to invite you to our upcoming [Event Name] scheduled for [Date] at [Time] in [Venue].\n\nThis event promises to be educational and fun for all participants. Please encourage your children to participate.\n\nFor more information, please contact [Contact Person].\n\nBest regards,\nAdministration";
            break;
        case 'emergency':
            message = "URGENT NOTICE\n\nDear Parents,\n\n[Insert emergency information here].\n\nPlease [Insert required action].\n\nWe will provide updates as they become available.\n\nStay safe.\n\nAdministration";
            break;
    }
    
    document.querySelector('textarea[name="message"]').value = message;
}
</script>

<?php include 'includes/admin_footer.php'; ?>