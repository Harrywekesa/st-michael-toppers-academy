<!-- admin/application_details.php -->
<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

include '../includes/db.php';

$application_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM student_applications WHERE id = ?");
$stmt->execute([$application_id]);
$application = $stmt->fetch();

if (!$application) {
    header('Location: applications.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve_application'])) {
        $reporting_date = $_POST['reporting_date'];
        $additional_requirements = $_POST['additional_requirements'];
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // 1. Approve the application
            $stmt = $pdo->prepare("UPDATE student_applications SET 
                status='Approved', 
                approval_date=NOW(), 
                reporting_date=?, 
                additional_requirements=? 
                WHERE id=?");
            $stmt->execute([$reporting_date, $additional_requirements, $application_id]);
            
            // 2. Check if parent account already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'parent'");
            $stmt->execute([$application['parent_email']]);
            $existing_parent = $stmt->fetch();
            
            $parent_id = null;
            
            if ($existing_parent) {
                // Parent account exists, use existing account
                $parent_id = $existing_parent['id'];
            } else {
                // Create new parent account
                $parent_name = $application['parent_name'];
                $parent_email = $application['parent_email'];
                
                // Use email as default password
                $default_password = $parent_email;
                $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'parent')");
                $stmt->execute([$parent_name, $parent_email, $hashed_password]);
                $parent_id = $pdo->lastInsertId();
                
                // Send welcome email with credentials
                sendParentWelcomeEmail($application, $default_password);
            }
            
            // 3. Create student record
            $stmt = $pdo->prepare("INSERT INTO students 
                (name, dob, gender, admission_no, class_id, parent_id, status) 
                VALUES (?, ?, ?, ?, NULL, ?, 'active')");
            $stmt->execute([
                $application['student_name'],
                $application['date_of_birth'],
                $application['gender'],
                generateAdmissionNumber(),
                $parent_id
            ]);
            $student_id = $pdo->lastInsertId();
            
            // 4. Update application with student_id
            $stmt = $pdo->prepare("UPDATE student_applications SET student_id = ? WHERE id = ?");
            $stmt->execute([$student_id, $application_id]);
            
            // Commit transaction
            $pdo->commit();
            
            $success = "Application approved successfully! Parent account created and student record added.";
            
            // Refresh application data
            $stmt = $pdo->prepare("SELECT * FROM student_applications WHERE id = ?");
            $stmt->execute([$application_id]);
            $application = $stmt->fetch();
            
        } catch(PDOException $e) {
            // Rollback transaction on error
            $pdo->rollback();
            $error = "Error approving application: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['reject_application'])) {
        $rejection_reason = $_POST['rejection_reason'];
        
        try {
            $stmt = $pdo->prepare("UPDATE student_applications SET 
                status='Rejected', 
                approval_date=NOW(),
                additional_requirements=? 
                WHERE id=?");
            $stmt->execute([$rejection_reason, $application_id]);
            
            $success = "Application rejected!";
            // Refresh application data
            $stmt = $pdo->prepare("SELECT * FROM student_applications WHERE id = ?");
            $stmt->execute([$application_id]);
            $application = $stmt->fetch();
        } catch(PDOException $e) {
            $error = "Error rejecting application: " . $e->getMessage();
        }
    }
}

// Function to generate admission number
function generateAdmissionNumber() {
    return 'ADM' . date('Y') . rand(1000, 9999);
}

// Function to send parent welcome email
function sendParentWelcomeEmail($application, $password) {
    // In a real implementation, you would use PHPMailer or similar
    // For now, we'll simulate the email sending
    
    $to = $application['parent_email'];
    $subject = "Welcome to St. Michael Toppers Academy - Parent Portal Access";
    
    $message = "
    <html>
    <head>
        <title>Welcome to St. Michael Toppers Academy</title>
    </head>
    <body>
        <h2>St. Michael Toppers Academy</h2>
        <p>Dear " . htmlspecialchars($application['parent_name']) . ",</p>
        
        <p>Welcome to St. Michael Toppers Academy! We're excited to have " . htmlspecialchars($application['student_name']) . " join our school community.</p>
        
        <p>Your application has been approved, and we've created a parent portal account for you to track your child's progress.</p>
        
        <h3>Your Login Credentials:</h3>
        <ul>
            <li><strong>Email:</strong> " . htmlspecialchars($application['parent_email']) . "</li>
            <li><strong>Default Password:</strong> " . htmlspecialchars($password) . "</li>
        </ul>
        
        <p><strong style='color: red;'>IMPORTANT:</strong> Please change your password after your first login for security.</p>
        
        <h3>Access the Parent Portal:</h3>
        <p>Visit: <a href=\"http://localhost/st-michael-toppers/login.php\">http://localhost/st-michael-toppers/login.php</a></p>
        
        <h3>Reporting Details:</h3>
        <ul>
            <li><strong>Date:</strong> " . date('F j, Y', strtotime($application['reporting_date'] ?? date('Y-m-d', strtotime('+7 days')))) . "</li>
            <li><strong>Time:</strong> 8:00 AM</li>
            <li><strong>Venue:</strong> School Main Office</li>
        </ul>
        
        <h3>Required Documents:</h3>
        <ul>
            <li>Original Birth Certificate</li>
            <li>Previous School Leaving Certificate (if applicable)</li>
            <li>Medical Certificate</li>
            <li>2 Passport-sized Photos</li>
            <li>Parent/Guardian Identification</li>
            " . (isset($application['additional_requirements']) ? "<li>" . nl2br(htmlspecialchars($application['additional_requirements'])) . "</li>" : "") . "
        </ul>
        
        <p>If you have any questions, please contact our admissions office at +254 700 123 456 or admissions@stmichaeltoppers.ac.ke</p>
        
        <p>Best regards,<br>
        St. Michael Toppers Academy Admissions Team</p>
    </body>
    </html>
    ";
    
    // Simulate email sending
    // In real implementation: mail($to, $subject, $message, $headers);
    return true;
}
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Application Details</h1>
                <a href="applications.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Applications
                </a>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Student Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Student Name:</strong> <?php echo htmlspecialchars($application['student_name']); ?></p>
                                    <p><strong>Date of Birth:</strong> <?php echo date('M j, Y', strtotime($application['date_of_birth'])); ?></p>
                                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($application['gender']); ?></p>
                                    <p><strong>Grade Applying For:</strong> <?php echo htmlspecialchars($application['grade_applying_for']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Current School:</strong> <?php echo htmlspecialchars($application['current_school'] ?? 'N/A'); ?></p>
                                    <p><strong>Home Address:</strong> <?php echo htmlspecialchars($application['home_address']); ?></p>
                                    <p><strong>Application Date:</strong> <?php echo date('M j, Y g:i A', strtotime($application['created_at'])); ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge bg-<?php 
                                            echo $application['status'] == 'Pending' ? 'warning' : 
                                                ($application['status'] == 'Approved' ? 'success' : 'danger'); ?>">
                                            <?php echo htmlspecialchars($application['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Parent/Guardian Information</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Parent Name:</strong> <?php echo htmlspecialchars($application['parent_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($application['parent_email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($application['parent_phone']); ?></p>
                            <p><strong>ID Number:</strong> <?php echo htmlspecialchars($application['parent_id_number'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                    
                    <?php if($application['status'] != 'Pending'): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Admission Decision</h5>
                        </div>
                        <div class="card-body">
                            <?php if($application['status'] == 'Approved'): ?>
                                <p><strong>Approval Date:</strong> <?php echo date('M j, Y g:i A', strtotime($application['approval_date'])); ?></p>
                                <p><strong>Reporting Date:</strong> <?php echo date('M j, Y', strtotime($application['reporting_date'])); ?></p>
                                <p><strong>Additional Requirements:</strong> <?php echo nl2br(htmlspecialchars($application['additional_requirements'])); ?></p>
                                <div class="alert alert-success">
                                    <h5><i class="fas fa-check-circle"></i> Application Approved</h5>
                                    <p>Parent account has been created and login credentials sent via email.</p>
                                    <p>Student record has been created. Please assign to a class.</p>
                                </div>
                            <?php else: ?>
                                <p><strong>Rejection Date:</strong> <?php echo date('M j, Y g:i A', strtotime($application['approval_date'])); ?></p>
                                <p><strong>Reason:</strong> <?php echo nl2br(htmlspecialchars($application['additional_requirements'])); ?></p>
                                <div class="alert alert-danger">
                                    <h5><i class="fas fa-times-circle"></i> Application Rejected</h5>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Uploaded Documents</h5>
                        </div>
                        <div class="card-body">
                            <?php if($application['passport_photo']): ?>
                                <div class="mb-3">
                                    <label class="form-label">Passport Photo</label>
                                    <div>
                                        <a href="../<?php echo htmlspecialchars($application['passport_photo']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-image"></i> View Photo
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($application['birth_certificate']): ?>
                                <div class="mb-3">
                                    <label class="form-label">Birth Certificate</label>
                                    <div>
                                        <a href="../<?php echo htmlspecialchars($application['birth_certificate']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-file-pdf"></i> View Document
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($application['parent_id_copy']): ?>
                                <div class="mb-3">
                                    <label class="form-label">Parent ID Copy</label>
                                    <div>
                                        <a href="../<?php echo htmlspecialchars($application['parent_id_copy']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-id-card"></i> View ID
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if($application['status'] == 'Pending'): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Process Application</h5>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#approveApplicationModal">
                                <i class="fas fa-check"></i> Approve Application
                            </button>
                            <button class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectApplicationModal">
                                <i class="fas fa-times"></i> Reject Application
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Approve Application Modal -->
<div class="modal fade" id="approveApplicationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="approve_application" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Reporting Date *</label>
                        <input type="date" class="form-control" name="reporting_date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Additional Requirements/Instructions</label>
                        <textarea class="form-control" name="additional_requirements" rows="4" placeholder="Enter any additional requirements or instructions for the parent...">Please bring the following additional documents:
- Medical insurance card
- Vaccination records
- Any special needs documentation (if applicable)</textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <p class="mb-0"><i class="fas fa-info-circle"></i> This will automatically:
                        <ul>
                            <li>Create a parent account if one doesn't exist</li>
                            <li>Send login credentials to the parent (using email as password)</li>
                            <li>Create a student record</li>
                            <li>Map the student to the parent</li>
                        </ul>
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Application Modal -->
<div class="modal fade" id="rejectApplicationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="reject_application" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason *</label>
                        <textarea class="form-control" name="rejection_reason" rows="4" placeholder="Enter the reason for rejection..." required>After reviewing the application, we found that:

1. The required documents were incomplete
2. The student does not meet the age requirements for the selected grade

Please ensure all required documents are complete and the student meets age requirements before re-applying.</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>