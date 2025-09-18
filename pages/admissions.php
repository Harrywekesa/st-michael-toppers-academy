<!-- pages/admissions.php -->
<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <h1 class="text-center mb-5">Admissions Inquiry</h1>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title text-primary">Admission Process</h3>
                    <ol>
                        <li>Fill out the admissions inquiry form</li>
                        <li>Our admissions team will contact you</li>
                        <li>Schedule a school tour (optional)</li>
                        <li>Complete required documentation</li>
                        <li>Submit required fees</li>
                        <li>Receive admission confirmation</li>
                    </ol>
                    
                    <h5>Required Documents</h5>
                    <ul>
                        <li>Birth Certificate</li>
                        <li>Previous School Records (if applicable)</li>
                        <li>Medical Certificate</li>
                        <li>Passport-sized Photos (2 copies)</li>
                        <li>Parent/Guardian Identification</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>Admissions Inquiry Form</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Parent/Guardian Name</label>
                            <input type="text" class="form-control" name="parent_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Student Name</label>
                            <input type="text" class="form-control" name="student_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Student Date of Birth</label>
                            <input type="date" class="form-control" name="student_dob" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Grade Applying For</label>
                            <select class="form-control" name="grade" required>
                                <option value="">Select Grade</option>
                                <option value="pp1">Pre-Primary 1 (PP1)</option>
                                <option value="pp2">Pre-Primary 2 (PP2)</option>
                                <option value="grade1">Grade 1</option>
                                <option value="grade2">Grade 2</option>
                                <option value="grade3">Grade 3</option>
                                <option value="grade4">Grade 4</option>
                                <option value="grade5">Grade 5</option>
                                <option value="grade6">Grade 6</option>
                                <option value="grade7">Grade 7</option>
                                <option value="grade8">Grade 8</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Message (Optional)</label>
                            <textarea class="form-control" name="message" rows="3"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Submit Inquiry</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-md-12">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h3 class="card-title">Admissions Office</h3>
                    <p class="card-text">
                        <i class="fas fa-phone"></i> +254 725881439<br>
                        <i class="fas fa-envelope"></i> admissions@stmichaeltoppers.ac.ke<br>
                        <i class="fas fa-clock"></i> Monday - Friday: 8:00 AM - 4:00 PM
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>