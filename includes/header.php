<!-- includes/header.php -->
<?php
// Simple base URL detection
function baseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . "://" . $host . "/st-michael-toppers";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Michael Toppers Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="<?php echo baseUrl(); ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #001f4d;">
        <div class="container">
            <a class="navbar-brand" href="<?php echo baseUrl(); ?>/index.php">
                <img src="<?php echo baseUrl(); ?>/assets/images/logo.PNG" alt="Logo" height="40">
                St. Michael Toppers Academy
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo baseUrl(); ?>/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo baseUrl(); ?>/pages/about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo baseUrl(); ?>/pages/academics.php">Academics</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo baseUrl(); ?>/pages/cocurricular.php">Co-Curricular</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo baseUrl(); ?>/pages/contact.php">Contact Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-warning text-dark ms-2" href="<?php echo baseUrl(); ?>/login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>