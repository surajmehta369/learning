<?php
session_name('STUDENT_SESSION');
session_start();

include('assets/header.php');
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/styles.css">
<link rel="icon" type="image/png" href="images/favicon.png">

<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css" />
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css" />

<style>
    .category-bg {
        position: absolute;
        right: -40px;
        top: 45px;
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: #f9f3ff;
        z-index: 0;
    }

    .trending-badge {
        position: absolute;
        top: 2px;
        right: 15px;
        background: #ff4757;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .testimonial-card {
        margin: 10px;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 8px;
        text-align: center;
        height: auto;
    }

    .author-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        margin-bottom: 10px;
    }

    .slick-prev:before,
    .slick-next:before {
        color: black !important;
    }

    .slick-slide {
        height: auto !important;
    }

    button.slick-prev.slick-arrow {
        display: none !important;
    }

    button.slick-next.slick-arrow {
        display: none !important;
    }
</style>


<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content fade-in-up">
                <span class="hero-badge">🎯 Trusted by 10,000+ Learners</span>
                <h1 class="typing-text">
                    <span id="typed-text"></span>
                    <span class="typing-cursor"></span>
                </h1>
                <p class="lead mb-4">Master in-demand skills with industry experts. Join India's fastest growing e-learning platform with live sessions, hands-on projects, and 24/7 support.</p>
                <a href="ourcourses.php" class="btn btn-light btn-lg me-3">Browse Courses</a>
                <a href="#" class="btn btn-outline-light btn-lg">Free Trial</a>
            </div>
            <div class="col-lg-6">
            </div>
        </div>
    </div>
</section>

<?php
$features = [
    [
        "title" => "Saved Video Classes",
        "desc"  => "Watch anytime, anywhere at your convenience",
        "icon"  => "https://img.icons8.com/color/48/000000/video-call.png",
        "alt"   => "Live Classes"
    ],
    [
        "title" => "E-Handwritten Notes",
        "desc"  => "Well-structured notes for quick revision",
        "icon"  => "https://img.icons8.com/fluency/48/task.png",
        "alt"   => "Tests & Notes"
    ],
    [
        "title" => "24×7 Doubt Support",
        "desc"  => "Get instant answers from mentors anytime",
        "icon"  => "https://img.icons8.com/fluency/48/ask-question.png",
        "alt"   => "Doubt Solving"
    ],
    [
        "title" => "Real-World Projects",
        "desc"  => "Gain hands-on experience with industry-focused projects",
        "icon"  => "https://img.icons8.com/fluency/48/prize.png",
        "alt"   => "Offline Centres"
    ]
];
?>
<div class="features-container">
    <?php foreach ($features as $feature): ?>
        <div class="feature-card">
            <img src="<?php echo $feature['icon']; ?>" alt="<?php echo $feature['alt']; ?>">
            <h3><?php echo $feature['title']; ?></h3>
            <p><?php echo $feature['desc']; ?></p>
        </div>
    <?php endforeach; ?>
</div>
<?php
$reasons = [
    [
        "title" => "Live Interactive Sessions",
        "desc"  => "Learn directly from industry experts with real-time Q&A sessions and live coding.",
        "icon"  => "fas fa-chalkboard-teacher"
    ],
    [
        "title" => "Career Support",
        "desc"  => "Get resume building, interview prep, and job placement assistance with our career services.",
        "icon"  => "fas fa-briefcase"
    ],
    [
        "title" => "Industry Recognized Certificates",
        "desc"  => "Receive certificates that add value to your resume and are recognized by top companies.",
        "icon"  => "fas fa-certificate"
    ]
];
?>
<section class="container py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold">Why Choose Baseline E-Learning?</h2>
        <p class="text-muted">Experience the difference with our unique learning approach</p>
    </div>

    <div class="row g-4">
        <?php foreach ($reasons as $reason): ?>
            <div class="col-md-4">
                <div class="text-center p-4 h-100">
                    <div class="feature-icon mx-auto">
                        <i class="<?php echo $reason['icon']; ?>"></i>
                    </div>
                    <h4 class="fw-bold mb-3"><?php echo $reason['title']; ?></h4>
                    <p><?php echo $reason['desc']; ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<div class="container py-5">
    <section class="skills-section">
        <h2 class="text-center fw-bold mb-3">LEARN Skill That Matters</h2>
        <p class="text-center text-muted mb-5">Explore trending IT skill categories</p>

        <div class="skills-tags-left">
            <span>Data Science & Analytics</span>
            <span>Digital Marketing With AI</span>
            <span>Programming Courses</span>
            <span>Product Management</span>
        </div>

        <div class="skills-tags-right">
            <span>Software Development</span>
            <span>Banking & Finance</span>
            <span>Cybersecurity Courses</span>
        </div>
    </section>

    <?php
    $categories = [
        [
            "title" => "Web Development",
            "tags"  => ["HTML", "CSS", "JavaScript"],
            "link"  => "ourcourses.php?category=Web Development",
            "icon"  => "https://lottie.host/2b543818-346f-4279-bc75-83ff991a9842/dCmdbKo5Bu.lottie",
            "type"  => "lottie",
            "trending" => true,
            "bg"    => ""
        ],
        [
            "title" => "JavaScript",
            "tags"  => ["JavaScript"],
            "link"  => "ourcourses.php?category=javascript",
            "icon"  => "https://lottie.host/2b543818-346f-4279-bc75-83ff991a9842/dCmdbKo5Bu.lottie",
            "type"  => "lottie",
            "trending" => false,
            "bg"    => ""
        ],
        [
            "title" => "Web Designing",
            "tags"  => ["UI/UX", "Figma", "Photoshop"],
            "link"  => "#",
            "icon"  => "https://lottie.host/ccc998a8-617d-4513-a961-911c7bcb4f3e/ymtK97oq8T.lottie",
            "type"  => "lottie",
            "trending" => false,
            "bg"    => "#fff4e6",
            "soon"  => true
        ],
        [
            "title" => "Python",
            "tags"  => ["Basics", "Django", "Flask"],
            "link"  => "ourcourses.php?category=Python",
            "icon"  => "🐍",
            "type"  => "emoji",
            "trending" => true,
            "bg"    => "#e8f7ff"
        ],
        [
            "title" => "Frameworks",
            "tags"  => ["React", "Angular", "Laravel"],
            "link"  => "ourcourses.php?category=Frameworks",
            "icon"  => "https://lottie.host/5d71caac-102b-4631-a87e-809050ad5049/eECiTN5O9O.lottie",
            "type"  => "lottie",
            "trending" => false,
            "bg"    => "#f0f8f5"
        ],
        [
            "title" => "AI & Machine Learning",
            "tags"  => ["ML", "NLP", "Computer Vision"],
            "link"  => "#",
            "icon"  => "https://lottie.host/f226329e-9ea8-422c-b619-191ef3845500/T3kLpOk7o6.lottie",
            "type"  => "lottie",
            "trending" => true,
            "bg"    => "#fff0f6",
            "soon"  => true
        ],
        [
            "title" => "Cloud Computing",
            "tags"  => ["AWS", "Azure", "Google Cloud"],
            "link"  => "#",
            "icon"  => "https://lottie.host/f941c0e3-dfdb-4722-8092-b84d5d736997/4kyo4nDa4W.lottie",
            "type"  => "lottie",
            "trending" => false,
            "bg"    => "#e6f7ff",
            "soon"  => true
        ],
        [
            "title" => "Cyber Security",
            "tags"  => ["Ethical Hacking", "Network", "Cryptography"],
            "link"  => "#",
            "icon"  => "https://lottie.host/3ebc69c5-7f20-4dc0-8cc5-b68ef54403da/85ewYP4FlF.lottie",
            "type"  => "lottie",
            "trending" => false,
            "bg"    => "#f9fbe7",
            "soon"  => true
        ],
        [
            "title" => "API's",
            "tags"  => ["Postman", "Thunder Client", "GraphQL", "REST API"],
            "link"  => "ourcourses.php?category=Apis",
            "icon"  => "https://lottie.host/edf84915-efb5-44b0-8636-2519c93416ec/rPcDiXWQxR.lottie",
            "type"  => "lottie",
            "trending" => false,
            "bg"    => "#e0f7fa"
        ]
    ];
    ?>

    <div class="row g-4">
        <?php foreach ($categories as $cat): ?>
            <div class="col-md-4">
                <div class="category-card">
                    <?php if ($cat['trending']): ?>
                        <span class="trending-badge">🔥 Trending</span>
                    <?php endif; ?>

                    <div class="category-bg" style="background: <?php echo $cat['bg'] ?: 'transparent'; ?>;"></div>

                    <h5><?php echo $cat['title']; ?></h5>

                    <div class="category-tags">
                        <?php foreach ($cat['tags'] as $tag): ?>
                            <span><?php echo $tag; ?></span>
                        <?php endforeach; ?>
                    </div>

                    <?php
                    $btnClass = isset($cat['soon']) && $cat['soon'] ? 'explore-btn coming-soon-btn' : 'explore-btn';
                    ?>
                    <a href="<?php echo $cat['link']; ?>" class="<?php echo $btnClass; ?>">
                        Explore More <span>→</span>
                    </a>

                    <div class="category-icon">
                        <?php if ($cat['type'] === 'lottie'): ?>
                            <dotlottie-wc src="<?php echo $cat['icon']; ?>" style="width: 150px; height: 150px" autoplay loop></dotlottie-wc>
                        <?php else: ?>
                            <span style="font-size: 3rem;"><?php echo $cat['icon']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$testimonials = [
    [
        "name" => "Rahul Sharma",
        "role" => "Full Stack Developer",
        "img"  => "https://randomuser.me/api/portraits/men/32.jpg",
        "text" => "The Web Development course changed my career. Got a job offer before even completing the course!",
        "rating" => 5
    ],
    [
        "name" => "Priya Patel",
        "role" => "Frontend Developer",
        "img"  => "https://randomuser.me/api/portraits/women/44.jpg",
        "text" => "The hands-on projects and mentor support helped me build a strong portfolio. Highly recommended!",
        "rating" => 4.5
    ],
    [
        "name" => "Amit Kumar",
        "role" => "Python Developer",
        "img"  => "https://randomuser.me/api/portraits/men/65.jpg",
        "text" => "24/7 doubt support is a game changer. Always got help when stuck. Perfect for working professionals.",
        "rating" => 5
    ],
    [
        "name" => "Neha Verma",
        "role" => "UI/UX Designer",
        "img"  => "https://randomuser.me/api/portraits/women/68.jpg",
        "text" => "The design modules were very practical. I was able to redesign my portfolio and land freelance clients quickly.",
        "rating" => 4.5
    ],
    [
        "name" => "Sandeep Singh",
        "role" => "Backend Developer",
        "img"  => "https://randomuser.me/api/portraits/men/41.jpg",
        "text" => "Loved the structured curriculum and real-world projects. It helped me switch from a non-tech background into IT.",
        "rating" => 5
    ],
    [
        "name" => "Kavya Nair",
        "role" => "Software Engineer",
        "img"  => "https://randomuser.me/api/portraits/women/12.jpg",
        "text" => "The mentorship and interview preparation sessions gave me confidence. Cleared 3 interviews in a month!",
        "rating" => 4.8
    ],
    [
        "name" => "Rohit Mehta",
        "role" => "DevOps Engineer",
        "img"  => "https://randomuser.me/api/portraits/men/29.jpg",
        "text" => "Great learning experience with real deployment practices. The course content is updated and industry-relevant.",
        "rating" => 4.7
    ]
];
?>
<section class="container py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold">What Our Students Say</h2>
    </div>

    <div class="testimonial-slider">
        <?php foreach ($testimonials as $t): ?>
            <div>
                <div class="testimonial-card shadow-sm">
                    <div class="testimonial-rating mb-2">
                        <?php for ($i = 1; $i <= 5; $i++) echo '<i class="fas fa-star text-warning"></i>'; ?>
                    </div>
                    <p class="small">"<?= $t['text'] ?>"</p>
                    <div class="mt-3">
                        <img src="<?= $t['img'] ?>" alt="Student" class="author-avatar mx-auto">
                        <h6 class="mb-0"><?= $t['name'] ?></h6>
                        <small class="text-muted"><?= $t['role'] ?></small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<div class="container py-5">
    <div class="row immersive-section g-0 align-items-center">
        <div class="col-md-6 immersive-text">
            <h2>Immersive Learning Experience</h2>
            <p>Step into hands-on learning that equips you with in-demand skills. Our immersive approach ensures you're fully prepared for the real world and job-ready from day one.</p>
        </div>
        <div class="col-md-6 immersive-video">
            <video muted loop playsinline>
                <source src="images/sessions.mp4" type="video/mp4">
                Your browser does not support HTML5 video.
            </video>
        </div>
    </div>
</div>

<?php include('assets/footer.php'); ?>
<script src="assets/js/script.js"></script>

<script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const typingText = document.getElementById('typed-text');
    const phrases = [
        "Transform Your Career",
        "Learn From Industry Experts",
        "Master In-Demand Skills",
        "Build Real-World Projects"
    ];
    let phraseIndex = 0;
    let charIndex = 0;
    let isDeleting = false;
    let isEnd = false;

    function typeEffect() {
        const currentPhrase = phrases[phraseIndex];

        if (!isDeleting && charIndex < currentPhrase.length) {
            typingText.textContent = currentPhrase.substring(0, charIndex + 1);
            charIndex++;
            setTimeout(typeEffect, 100);
        } else if (isDeleting && charIndex > 0) {
            typingText.textContent = currentPhrase.substring(0, charIndex - 1);
            charIndex--;
            setTimeout(typeEffect, 50);
        } else {
            isDeleting = !isDeleting;
            if (!isDeleting) {
                phraseIndex = (phraseIndex + 1) % phrases.length;
            }
            setTimeout(typeEffect, isDeleting ? 1500 : 500);
        }
    }

    function animateCounters() {
        const counters = document.querySelectorAll('.stat-number');
        const speed = 200;

        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-count'));
            const count = +counter.innerText;
            const increment = target / speed;

            if (count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(() => animateCounters(), 1);
            } else {
                counter.innerText = target;
            }
        });
    }

    const observerOptions = {
        threshold: 0.3,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                if (entry.target.classList.contains('stats-section')) {
                    animateCounters();
                }
                entry.target.classList.add('fade-in-up');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.hero-content, .stat-item, .testimonial-card, .feature-icon').forEach(el => {
        observer.observe(el);
    });

    const video = document.querySelector('.immersive-video video');
    const videoObserver = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                video.play();
            } else {
                video.pause();
            }
        });
    }, {
        threshold: 0.5
    });

    videoObserver.observe(document.querySelector('.immersive-section'));

    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    window.addEventListener('load', () => {
        typeEffect();
    });
    document.addEventListener("DOMContentLoaded", function() {

        const comingSoonButtons = document.querySelectorAll(".coming-soon-btn");

        comingSoonButtons.forEach(function(button) {

            button.addEventListener("click", function(e) {

                e.preventDefault();

                Swal.fire({
                    icon: "info",
                    title: "Coming Soon 🚀",
                    text: "This course will be uploaded shortly. We will notify you once it becomes available!",
                    confirmButtonText: "Got it!",
                    confirmButtonColor: "#5624d0"
                });

            });

        });

    });
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>

<script>
    $(document).ready(function() {
        $('.testimonial-slider').slick({
            infinite: true,
            slidesToShow: 4,
            slidesToScroll: 1,
            autoplay: true,
            autoplaySpeed: 2000,
            arrows: true,
            responsive: [{
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 600,
                    settings: {
                        slidesToShow: 1
                    }
                }
            ]
        });
    });
</script>