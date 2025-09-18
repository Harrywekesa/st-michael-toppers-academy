<!-- index.php -->
<?php include 'includes/header.php'; ?>

<!-- Hero Carousel -->
<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="assets/images/school1.jpg" class="d-block w-100" alt="School Image 1" style="height: 500px; object-fit: cover;">
            <div class="carousel-caption d-none d-md-block">
                <h1>Welcome to St. Michael Toppers Academy</h1>
                <p class="lead">Nurturing Young Minds For A Brighter Future</p>
                <a href="student_application.php" class="btn btn-warning btn-lg">Apply Now</a>
                <p class="mt-3">Where every child's potential is unlocked</p>
            </div>
        </div>
        <div class="carousel-item">
            <img src="assets/images/school2.jpg" class="d-block w-100" alt="School Image 2" style="height: 500px; object-fit: cover;">
            <div class="carousel-caption d-none d-md-block">
                <h1>Excellence in Education</h1>
                <p class="lead">Since 2011</p>
                <a href="student_application.php" class="btn btn-warning btn-lg">Enroll Today</a>
            </div>
        </div>
        <div class="carousel-item">
            <img src="assets/images/school3.jpg" class="d-block w-100" alt="School Image 3" style="height: 500px; object-fit: cover;">
            <div class="carousel-caption d-none d-md-block">
                <h1>Quality Learning Environment</h1>
                <p class="lead">Holistic Development for Every Child</p>
                <a href="student_application.php" class="btn btn-warning btn-lg">Join Us</a>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

<!-- Features Section -->
<div class="container my-5">
    <div class="row text-center">
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-primary">
                <div class="card-body">
                    <i class="fas fa-graduation-cap fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Quality Education</h5>
                    <p class="card-text">We provide excellent academic programs tailored to each student's needs.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-success">
                <div class="card-body">
                    <i class="fas fa-users fa-3x text-success mb-3"></i>
                    <h5 class="card-title">Experienced Teachers</h5>
                    <p class="card-text">Our dedicated faculty ensures every child reaches their full potential.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-warning">
                <div class="card-body">
                    <i class="fas fa-book-open fa-3x text-warning mb-3"></i>
                    <h5 class="card-title">Holistic Development</h5>
                    <p class="card-text">Beyond academics, we nurture creativity, sports, and character building.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- About Preview -->
<div class="bg-light py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2>About Our School</h2>
                <p>St. Michael Toppers Academy has been providing quality education since 2011. Our mission is to nurture young minds and prepare them for a brighter future through holistic education.</p>
                <a href="pages/about.php" class="btn btn-primary">Learn More</a>
            </div>
            <div class="col-md-6">
                <img src="assets/images/logo.PNG" alt="About School" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>