<!-- student_application.php -->
<?php 
session_start();
include 'includes/db.php';

$success = '';
$error = '';

// Create applications table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS student_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_name VARCHAR(100) NOT NULL,
        date_of_birth DATE NOT NULL,
        gender ENUM('Male', 'Female') NOT NULL,
        grade_applying_for VARCHAR(50) NOT NULL,
        parent_name VARCHAR(100) NOT NULL,
        parent_email VARCHAR(100) NOT NULL,
        parent_phone VARCHAR(20) NOT NULL,
        parent_id_number VARCHAR(20),
        home_address TEXT,
        current_school VARCHAR(100),
        passport_photo VARCHAR(255),
        birth_certificate VARCHAR(255),
        parent_id_copy VARCHAR(255),
        status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
        approval_date DATETIME NULL,
        reporting_date DATE NULL,
        additional_requirements TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch(PDOException $e) {
    // Continue silently
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $student_name = $_POST['student_name'];
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $grade_applying_for = $_POST['grade_applying_for'];
    $parent_name = $_POST['parent_name'];
    $parent_email = $_POST['parent_email'];
    $parent_phone = $_POST['parent_phone'];
    $parent_id_number = $_POST['parent_id_number'];
    $home_address = $_POST['home_address'];
    $current_school = $_POST['current_school'];
    
    // Handle file uploads
    $upload_dir = 'uploads/applications/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $passport_photo = '';
    $birth_certificate = '';
    $parent_id_copy = '';
    
    try {
        // Upload passport photo
        if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] == 0) {
            $file_name = time() . '_passport_' . $_FILES['passport_photo']['name'];
            $file_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['passport_photo']['tmp_name'], $file_path)) {
                $passport_photo = $file_path;
            }
        }
        
        // Upload birth certificate
        if (isset($_FILES['birth_certificate']) && $_FILES['birth_certificate']['error'] == 0) {
            $file_name = time() . '_birth_' . $_FILES['birth_certificate']['name'];
            $file_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['birth_certificate']['tmp_name'], $file_path)) {
                $birth_certificate = $file_path;
            }
        }
        
        // Upload parent ID copy
        if (isset($_FILES['parent_id_copy']) && $_FILES['parent_id_copy']['error'] == 0) {
            $file_name = time() . '_id_' . $_FILES['parent_id_copy']['name'];
            $file_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['parent_id_copy']['tmp_name'], $file_path)) {
                $parent_id_copy = $file_path;
            }
        }
        
        // Insert application into database
        $stmt = $pdo->prepare("INSERT INTO student_applications 
            (student_name, date_of_birth, gender, grade_applying_for, parent_name, 
             parent_email, parent_phone, parent_id_number, home_address, current_school,
             passport_photo, birth_certificate, parent_id_copy) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $student_name, $date_of_birth, $gender, $grade_applying_for, $parent_name,
            $parent_email, $parent_phone, $parent_id_number, $home_address, $current_school,
            $passport_photo, $birth_certificate, $parent_id_copy
        ]);
        
        $success = "Application submitted successfully! You will receive an email notification once your application is reviewed.";
        
    } catch(PDOException $e) {
        $error = "Error submitting application: " . $e->getMessage();
    } catch(Exception $e) {
        $error = "Error uploading files: " . $e->getMessage();
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header bg-primary text-white text-center">
                    <h2>Student Application Form</h2>
                    <p class="mb-0">St. Michael Toppers Academy</p>
                </div>
                <div class="card-body">
                    <?php if($success): ?>
                        <div class="alert alert-success">
                            <h4>Application Submitted Successfully!</h4>
                            <p><?php echo $success; ?></p>
                            <p class="mb-0">Application Reference Number: APP-<?php echo date('Y'); ?>-<?php echo $pdo->lastInsertId(); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if(!$success): ?>
                    <form method="POST" enctype="multipart/form-data">
                        <h4 class="border-bottom pb-2">Student Information</h4>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Student Full Name *</label>
                                <input type="text" class="form-control" name="student_name" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Date of Birth *</label>
                                <input type="date" class="form-control" name="date_of_birth" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Gender *</label>
                                <select class="form-control" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Grade Applying For *</label>
                                <select class="form-control" name="grade_applying_for" required>
                                    <option value="">Select Grade</option>
                                    <option value="PP1">Pre-Primary 1 (PP1)</option>
                                    <option value="PP2">Pre-Primary 2 (PP2)</option>
                                    <option value="Grade 1">Grade 1</option>
                                    <option value="Grade 2">Grade 2</option>
                                    <option value="Grade 3">Grade 3</option>
                                    <option value="Grade 4">Grade 4</option>
                                    <option value="Grade 5">Grade 5</option>
                                    <option value="Grade 6">Grade 6</option>
                                    <option value="Grade 7">Grade 7</option>
                                    <option value="Grade 8">Grade 8</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Current School (if any)</label>
                                <input type="text" class="form-control" name="current_school">
                            </div>
                        </div>
                        
                        <h4 class="border-bottom pb-2 mt-4">Parent/Guardian Information</h4>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Parent/Guardian Full Name *</label>
                                <input type="text" class="form-control" name="parent_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Parent/Guardian Email *</label>
                                <input type="email" class="form-control" name="parent_email" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Parent/Guardian Phone *</label>
                                <input type="tel" class="form-control" name="parent_phone" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Parent ID Number</label>
                                <input type="text" class="form-control" name="parent_id_number">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Home Address *</label>
                            <textarea class="form-control" name="home_address" rows="3" required></textarea>
                        </div>
                        
                        <h4 class="border-bottom pb-2 mt-4">Required Documents</h4>
                        <p class="text-muted">Please upload clear scanned copies or photos of the following documents:</p>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Passport Photo *</label>
                                <input type="file" class="form-control" name="passport_photo" accept="image/*" required>
                                <div class="form-text">Recent passport-sized photo</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Birth Certificate *</label>
                                <input type="file" class="form-control" name="birth_certificate" accept="image/*,application/pdf" required>
                                <div class="form-text">Scanned copy of birth certificate</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Parent ID Copy</label>
                                <input type="file" class="form-control" name="parent_id_copy" accept="image/*,application/pdf">
                                <div class="form-text">Scanned copy of parent's ID</div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <h5>Application Process</h5>
                            <ol>
                                <li>Submit this application form with all required documents</li>
                                <li>Our admissions team will review your application</li>
                                <li>You will receive an email notification of the decision</li>
                                <li>If approved, you will receive reporting date and requirements</li>
                            </ol>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the terms and conditions and confirm that all information provided is accurate
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Submit Application</button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>