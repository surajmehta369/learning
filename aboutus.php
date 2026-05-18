<?php
session_name('STUDENT_SESSION');
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>about us</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">

    <?php include("assets/header.php"); ?>

</head>

<body>

    <div class="container py-5">
        <h3 class="text-center fw-bold mb-4 fs-2">About Us</h3>

        <div class="row align-items-center">
            <div class="col-md-6 text-center mb-4 mb-md-0">
                <dotlottie-wc
                    src="https://lottie.host/2b543818-346f-4279-bc75-83ff991a9842/dCmdbKo5Bu.lottie"
                    style="width: 100%; max-width: 300px; height: 300px;"
                    autoplay
                    loop>
                </dotlottie-wc>
            </div>
            <div class="col-md-6">
                <p class="text-muted mb-0 fs-5" style="line-height: 1.8;">
                    At Baseline Skills, we empower learners to thrive in today’s fast-paced digital world. Our industry-relevant courses cover software development, modern frameworks, artificial intelligence, SEO, project management, digital marketing, UI/UX design, and more. Whether you’re a professional aiming to upskill or a student looking to kickstart your career, our programs help you master the tools, technologies, and strategies needed to excel and unlock your full potential.
                </p>

            </div>
        </div>
    </div>


    <div class="container py-5">
        <h2 class="text-center fw-bold mb-5">Our Mission</h2>
        <div class="row immersive-section g-4 align-items-center justify-content-center">
            <div class="col-lg-6 text-center text-lg-start immersive-text">
                <h3 class="fw-bold mb-4">Immersive Learning Experience</h3>
                <p class="lead text-muted">
                    Baseline skills is the result of a continual effort to exponentially increase the employability of every Indian, irrespective of their socioeconomic status. With accessibility and affordability as the foundation of high-quality, industry-relevant courses, Baseline E-learning empowers professionals and students alike to jumpstart their careers or enhance existing skills with future-driven upgrades that help them realize their full potential.
                </p>
            </div>
            <div class="col-lg-6 text-center immersive-video">
                <img src="images/mission.svg" alt="mission" class="img-fluid" style="max-height: 400px;">
            </div>
        </div>
    </div>


    <div class="container py-5">
        <h2 class="text-center fw-bold mb-5">Our Services</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="service-box"> <img src="https://img.icons8.com/color/96/cheap-2.png" alt="Affordable">
                    <div>
                        <h5 class="fw-bold">Affordable online courses</h5>
                        <p class="text-muted mb-0">Affordable online courses along with learning communities.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="service-box"> <img src="https://img.icons8.com/color/96/teacher.png" alt="Mentors">
                    <div>
                        <h5 class="fw-bold">Best in Class/Industry Mentors</h5>
                        <p class="text-muted mb-0">Mentors are Youtubers, digital entrepreneurs and content creators.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="service-box"> <img src="https://img.icons8.com/color/96/certificate.png" alt="Experience Portal">
                    <div>
                        <h5 class="fw-bold">Experience Portal</h5>
                        <p class="text-muted mb-0">A revolutionary self-paced experience portal.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="service-box"> <img src="https://img.icons8.com/color/96/graduation-cap.png" alt="On Demand">
                    <div>
                        <h5 class="fw-bold">On-Demand Courses</h5>
                        <p class="text-muted mb-0">Provide on-demand courses across technologies like data science, machine learning, and AI.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include("assets/footer.php"); ?>

    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>


</html>