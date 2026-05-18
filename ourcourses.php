<?php
include("conn.php");
session_name('STUDENT_SESSION');
session_start();


$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$where = [];

if ($category) {
    $where[] = "category = '$category'";
}

if ($search) {
    $where[] = "(name LIKE '%$search%' OR description LIKE '%$search%' OR category LIKE '%$search%')";
}

$where_sql = "";
if (!empty($where)) {
    $where_sql = "WHERE " . implode(" AND ", $where);
}

$courses = $conn->query("SELECT * FROM baseline_courses $where_sql ORDER BY created_at ASC");

$coursesByCategory = [];
if ($courses->num_rows > 0) {
    while ($row = $courses->fetch_assoc()) {
        $coursesByCategory[$row['category']][] = $row;
    }
}


$totalCourses = $courses->num_rows;
$totalCategories = count($coursesByCategory);

include("assets/header.php");
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $category ? htmlspecialchars($category) . " Courses" : "Browse All Courses" ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        body {
            padding-top: 75px !important;
        }

        header,
        .navbar {
            position: fixed !important;
            top: 0;
            width: 100%;
            z-index: 1000 !important;
            background: #ffffff !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .dropdown-menu {
            z-index: 9999 !important;
            position: absolute !important;
        }

        .navbar-nav .nav-item {
            margin-right: 15px;
        }

        .courses-top-nav {
            position: sticky !important;
            top: 70px;
            z-index: 900 !important;
            background: #f7f9fa !important;
        }

        .courses-hero,
        .courses-grid,
        .course-card {
            position: relative;
            z-index: 1 !important;
        }


        .courses-page-inner {
            min-height: 100vh;
            background: #ffffff;
        }

        .courses-top-nav {
            background: #f7f9fa;
            padding: 15px 0;
            border-bottom: 1px solid #dee2e6;
            position: relative;
            z-index: 10;
        }

        .nav-tabs-custom {
            display: flex;
            gap: 15px;
            overflow-x: auto;
            padding-bottom: 10px;
            scrollbar-width: thin;
        }

        .nav-tabs-custom::-webkit-scrollbar {
            height: 4px;
        }

        .nav-tabs-custom::-webkit-scrollbar-thumb {
            background: #dee2e6;
            border-radius: 10px;
        }


        .courses-hero {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            padding: 60px 0;
            border-radius: 20px;
            margin-bottom: 40px;
            color: white;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .hero-bg-pattern {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            opacity: 0.1;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><circle cx="50" cy="50" r="30" fill="white"/></svg>');
        }

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .hero-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto 25px;
        }

        .search-bar-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .search-bar {
            display: flex;
            gap: 10px;
            background: white;
            padding: 10px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .search-bar input {
            flex: 1;
            border: none;
            padding: 12px 20px;
            font-size: 1rem;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .search-bar input:focus {
            outline: none;
            background: white;
            box-shadow: 0 0 0 2px rgba(86, 36, 208, 0.2);
        }

        .search-bar button {
            background: #5624d0;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .search-bar button:hover {
            background: #4a1fb8;
        }

        .stats-section {
            margin: 40px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #eaeaea;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #5624d0;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .category-section {
            margin: 50px 0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin: 0;
        }

        .view-all {
            color: #5624d0;
            text-decoration: none;
            font-weight: 500;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .course-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .course-card-link .view-btn {
            pointer-events: none;
        }

        .course-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid #eee;
            animation: fadeIn 0.6s ease-out forwards;
            opacity: 0;
        }

        .course-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .course-image {
            height: 180px;
            overflow: hidden;
            position: relative;
        }

        .course-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .course-card:hover .course-image img {
            transform: scale(1.05);
        }

        .bestseller-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: #000;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .course-content {
            padding: 20px;
        }

        .course-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .course-category {
            display: inline-block;
            background: #f0f2f5;
            color: #666;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-bottom: 15px;
        }

        .course-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .price {
            font-size: 1.5rem;
            font-weight: 800;
            color: #333;
        }

        .price-strike {
            font-size: 0.9rem;
            color: #999;
            text-decoration: line-through;
            margin-left: 5px;
        }

        .view-btn {
            background: #5624d0;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .view-btn:hover {
            background: #4a1fb8;
            color: white;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: #f8f9fa;
            border-radius: 15px;
            margin: 40px 0;
        }

        .no-results-icon {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 20px;
        }

        .no-results h3 {
            color: #666;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }

        .no-results p {
            color: #888;
            max-width: 500px;
            margin: 0 auto 25px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .course-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .course-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .course-card:nth-child(3) {
            animation-delay: 0.3s;
        }

        .course-card:nth-child(4) {
            animation-delay: 0.4s;
        }
    </style>
</head>

<div class="courses-page-inner">

    <div class="container">
        <div class="courses-main-content">

            <div class="courses-hero">
                <div class="hero-bg-pattern"></div>
                <div class="container">
                    <div class="hero-content">
                        <h1 class="hero-title">Browse All Courses</h1>
                        <p class="hero-subtitle">Expand your knowledge with our carefully curated selection of courses from industry experts</p>

                        <div class="search-bar-container">
                            <form method="get" class="search-bar">
                                <input type="text" placeholder="Search courses by name, description, or category.." name="search" value="<?= htmlspecialchars($search) ?>" id="searchInput">
                                <?php if ($category): ?>
                                    <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                                <?php endif; ?>
                                <button type="submit"><i class="fas fa-search me-2"></i>Search</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($category) && empty($search)): ?>
                <div class="stats-section">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?= $totalCourses ?></div>
                            <div class="stat-label">Total Courses</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= $totalCategories ?></div>
                            <div class="stat-label">Categories</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">50+</div>
                            <div class="stat-label">Expert Instructors</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">4.8</div>
                            <div class="stat-label">Average Rating</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div id="coursesContainer">
                <?php if (!empty($coursesByCategory)): ?>
                    <?php foreach ($coursesByCategory as $catName => $courses): ?>
                        <div class="category-section">
                            <div class="section-header">
                                <h2 class="section-title"><?= htmlspecialchars($catName) ?></h2>
                                <?php if (count($courses) > 4): ?>
                                    <a href="?category=<?= urlencode($catName) ?>" class="view-all">
                                        View All <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="courses-grid">
                                <?php foreach ($courses as $course): ?>
                                    <a href="viewmore.php?id=<?= $course['id'] ?>" class="course-card-link">
                                        <div class="course-card">
                                            <div class="course-image">
                                                <img src="<?= $course['image'] ? htmlspecialchars($course['image']) : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80' ?>" alt="<?= htmlspecialchars($course['name']) ?>">
                                                <div class="bestseller-badge">BESTSELLER</div>
                                            </div>
                                            <div class="course-content">
                                                <h3 class="course-name"><?= htmlspecialchars($course['name']) ?></h3>
                                                <div class="course-category"><?= htmlspecialchars($course['category']) ?></div>
                                                <div class="course-price">
                                                    <div>
                                                        <span class="price">₹<?= number_format($course['price'], 2) ?></span>
                                                        <?php if ($course['price'] > 999): ?>
                                                            <span class="price-strike">₹<?= number_format($course['price'] * 1.5, 2) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="view-btn">View Details</span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-results">
                        <div class="no-results-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h3>No courses found</h3>
                        <p>Try adjusting your search or check back later for new courses.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

</div>
<script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php include("assets/footer.php"); ?>