<!-- pages/contact.php -->
<?php 
include '../includes/header.php';

// Process contact form
$message_sent = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);
    
    // In a real application, you would save this to database or send email
    // For now, we'll just set a success flag
    $message_sent = true;
}
?>

<div class="container my-5">
    <h1 class="text-center mb-5">Contact Us</h1>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="card-title text-primary">Get In Touch</h3>
                    <p>We'd love to hear from you! Whether you have questions about admissions, academics, or anything else, our team is ready to assist you.</p>
                    
                    <div class="contact-info">
                        <div class="mb-3">
                            <h5><i class="fas fa-map-marker-alt text-primary"></i> Address</h5>
                            <p>Kipsongo<br>Kitale, Kenya</p>
                        </div>
                        
                        <div class="mb-3">
                            <h5><i class="fas fa-phone text-primary"></i> Phone</h5>
                            <p>+254 725 881 439<br>+254 725 881 439</p>
                        </div>
                        
                        <div class="mb-3">
                            <h5><i class="fas fa-envelope text-primary"></i> Email</h5>
                            <p>info@stmichaeltoppers.ac.ke<br>admissions@stmichaeltoppers.ac.ke</p>
                        </div>
                        
                        <div class="mb-3">
                            <h5><i class="fas fa-clock text-primary"></i> Office Hours</h5>
                            <p>Monday - Friday: 8:00 AM - 4:00 PM<br>Saturday: 9:00 AM - 12:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title text-primary">Send Us a Message</h3>
                    
                    <?php if($message_sent): ?>
                        <div class="alert alert-success">
                            Thank you for your message! We'll get back to you soon.
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Your Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title text-center text-primary mb-4">Our Location</h3>
                    <!-- Google Map Embed -->
                    <div class="ratio ratio-21x9">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.815743449509!2d36.81953431475393!3d-1.287447699063317!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f10d47b0a5a7d%3A0x2c7c3d2d0d0d0d0d!2sNairobi%2C%20Kenya!5e0!3m2!1sen!2ske!4v1650000000000!5m2!1sen!2ske" 
                                allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>