<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('STUDENT_SESSION');
    session_start();
}

$base_url = "";
$current_page = "";
include("conn.php");

$userExists = false;
$userData   = null;
$cart_count = 0;
$unread_count = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);

    $stmtUser = $conn->prepare("SELECT `id`, `full_name`, `email`, `role` , `image` FROM `signup` WHERE `id` = ? LIMIT 1 ");
    $stmtUser->bind_param("i", $user_id);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();

    if ($resultUser && $resultUser->num_rows === 1) {
        $userExists = true;
        $userData = $resultUser->fetch_assoc();

        $stmtCart = $conn->prepare("
            SELECT COUNT(*) as cart_count 
            FROM baseline_User_Cart 
            WHERE user_id = ? AND payment_mode NOT IN ('stripe_paid', 'success')
        ");
        $stmtCart->bind_param("i", $user_id);
        $stmtCart->execute();
        $resultCart = $stmtCart->get_result();

        if ($resultCart && $resultCart->num_rows > 0) {
            $cartData = $resultCart->fetch_assoc();
            $cart_count = intval($cartData['cart_count']);
        }
        $stmtCart->close();

        $currentMonthStart = date('Y-m-01 00:00:00');

        $conn->query("DELETE FROM notifications WHERE created_at < '$currentMonthStart'");
        $conn->query("DELETE FROM weekly_updates WHERE created_at < '$currentMonthStart'");

        $viewQuery = $conn->query("SELECT last_viewed_at FROM notification_views WHERE user_id = $user_id");
        $lastViewed = ($viewQuery && $viewQuery->num_rows > 0) ? $viewQuery->fetch_assoc()['last_viewed_at'] : '1970-01-01 00:00:00';


        $res1 = $conn->query("SELECT COUNT(*) as total FROM notifications 
                             WHERE user_id = $user_id 
                             AND is_read = 0 
                             AND created_at >= '$currentMonthStart'");
        $count1 = ($res1) ? $res1->fetch_assoc()['total'] : 0;

        $res2 = $conn->query("SELECT COUNT(*) as total FROM weekly_updates 
                             WHERE created_at > '$lastViewed' 
                             AND created_at >= '$currentMonthStart'");
        $count2 = ($res2) ? $res2->fetch_assoc()['total'] : 0;

        $unread_count = $count1 + $count2;

        $_SESSION['cart_count'] = $cart_count;
    }
    $stmtUser->close();
}
?>
<style>
    .swal2-backdrop-show {
        backdrop-filter: blur(8px);
    }

    .notification-content p {
        line-height: 1.4;
        word-break: break-word;
    }

    #modalNotiMessage {
        font-size: 0.95rem;
        color: #444;
        line-height: 1.6;
    }

    .view-notification:hover {
        background-color: #f8f9fa !important;
    }

    .fab.fa-python,
    i[class*="fa-python"],
    span[class*="fa-python"] {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        width: 0 !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .category i,
    .category span {
        background: none !important;
        border: none !important;
    }

    .btn-weekly-quiz {
        color: #6f42c1 !important;
        border-color: #6f42c1 !important;
        background-color: transparent !important;
        font-weight: 500;
    }

    .btn-weekly-quiz:hover {
        color: #fff !important;
        background-color: #6f42c1 !important;
    }
</style>

<!DOCTYPE html>
<html lang="en">

<head>
    <base href="/learning/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Panel - Baseline E-learning</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <div class="custom-navbar-wrapper">
        <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
            <div class="container-fluid">
                <a class="navbar-brand d-flex align-items-center" href="index.php">
                    <img src="images/logo.png" alt="Logo" width="140" height="45" class="me-2">
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
                    aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarContent">
                    <?php if (!isset($hide_nav) || $hide_nav !== true): ?>
                        <ul class="navbar-nav <?= ($current_page === 'Purchased.php') ? '' : 'me-auto' ?> mb-2 mb-rg-0">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="coursesDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-graduation-cap me-1"></i> All Courses
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="coursesDropdown">
                                    <li><a class="dropdown-item" href="ourcourses.php">
                                            <i class="fas fa-list"></i> All Courses
                                        </a></li>
                                    <li><a class="dropdown-item" href="ourcourses.php?category=Web%20Development">
                                            <i class="fas fa-code"></i> Web Development
                                        </a></li>
                                    <li><a class="dropdown-item" href="ourcourses.php?category=Frameworks">
                                            <i class="fas fa-cubes"></i> Frameworks
                                        </a></li>
                                    <li><a class="dropdown-item" href="ourcourses.php?category=Apis">
                                            <i class="fas fa-plug"></i> APIs
                                        </a></li>
                                    <li><a class="dropdown-item" href="ourcourses.php?category=Python">
                                            <i class="fab fa-python"></i> Python
                                        </a></li>

                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <h6 class="dropdown-header text-muted">Coming Soon</h6>
                                    </li>

                                    <li><a class="dropdown-item coming-soon-link" href="coming_soon?course=Web%20Designing">
                                            <i class="fas fa-palette"></i> Web Designing
                                        </a></li>

                                    <li><a class="dropdown-item coming-soon-link" href="coming_soon?course=AI%20%26%20Machine%20Learning">
                                            <i class="fas fa-robot"></i> AI & Machine Learning
                                        </a></li>

                                    <li><a class="dropdown-item coming-soon-link" href="coming_soon?course=Cloud%20Computing">
                                            <i class="fas fa-cloud"></i> Cloud Computing
                                        </a></li>

                                    <li><a class="dropdown-item coming-soon-link" href="coming_soon?course=Cyber%20Security">
                                            <i class="fas fa-shield-alt"></i> Cyber Security
                                        </a></li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="e-books.php">
                                    <i class="fas fa-book me-1"></i> E-Books
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="aboutus.php">
                                    <i class="fas fa-info-circle me-1"></i> About Us
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="contactus.php">
                                    <i class="fas fa-envelope me-1"></i> Contact Us
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                    <div class="search-container position-relative <?= ($current_page === 'Purchased.php') ? 'ms-auto' : '' ?> me-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input class="form-control search-input" type="search"
                                placeholder="Search courses, categories, technologies..."
                                aria-label="Search" autocomplete="off">
                            <button class="btn btn-purple" type="button" onclick="performSearch()">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                        </div>
                        <div class="search-results position-absolute top-100 start-0 end-0 bg-white shadow-lg rounded mt-1 d-none"
                            style="z-index: 1050; max-height: 400px; overflow-y: auto;"></div>
                    </div>

                    <div class="d-flex align-items-center">
                        <div class="d-flex align-items-center">

                            <?php if ($userExists):
                                $total_display_count = $unread_count;
                            ?>
                                <div class="dropdown me-2">
                                    <a href="javascript:void(0)" id="notiBtn" class="btn position-relative" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-bell"></i>
                                        <span id="notiCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                            style="display: <?= $total_display_count > 0 ? 'flex' : 'none' ?>; font-size: 0.65rem;">
                                            <?= $total_display_count ?>
                                        </span>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3" style="width: 320px; max-height: 400px; overflow-y: auto; border-radius: 12px;">
                                        <li class="p-3 border-bottom d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0 fw-bold">Notifications</h6>
                                            <span class="badge bg-light text-dark"><?= $total_display_count ?> New</span>
                                        </li>
                                        <div id="notificationList">
                                            <?php
                                            $firstDay = date('Y-m-01 00:00:00');
                                            $sql = "(SELECT message as title, message as full_msg, created_at, is_read, 'general' as type 
         FROM notifications 
         WHERE user_id = $user_id AND created_at >= '$firstDay')
        UNION
        (SELECT title, message as full_msg, created_at, 0 as is_read, 'weekly' as type 
         FROM weekly_updates 
         WHERE created_at >= '$firstDay')
        ORDER BY created_at DESC LIMIT 10";

                                            $notis = $conn->query($sql);

                                            if ($notis && $notis->num_rows > 0):
                                                while ($n = $notis->fetch_assoc()):
                                                    $is_unread = ($n['type'] == 'weekly' || $n['is_read'] == 0);
                                                    $display_title = ($n['type'] == 'weekly') ? "Teacher: " . $n['title'] : $n['title'];
                                            ?><li>
                                                        <a href="javascript:void(0)"
                                                            class="view-notification noti-item <?= $is_unread ? 'bg-light' : '' ?>"
                                                            data-title="<?= htmlspecialchars($display_title) ?>"
                                                            data-msg="<?= htmlspecialchars($n['full_msg']) ?>">

                                                            <div class="noti-meta">
                                                                <?php if ($n['type'] == 'weekly'): ?>
                                                                    <span class="badge bg-info text-dark" style="font-size: 0.6rem;">Weekly Update</span>
                                                                <?php else: ?>
                                                                    <span></span> <?php endif; ?>
                                                                <small class="text-muted"><i class="far fa-clock me-1"></i><?= date('d M, h:i A', strtotime($n['created_at'])) ?></small>
                                                            </div>

                                                            <div class="noti-body">
                                                                <p class="mb-0 small text-dark fw-bold"><?= htmlspecialchars($display_title) ?></p>
                                                                <p class="mb-0 mt-1 text-muted" style="font-size: 0.75rem;">Click to read more...</p>
                                                            </div>
                                                        </a>
                                                    </li>
                                            <?php
                                                endwhile;
                                            else:
                                                echo "<li class='p-4 text-center text-muted'><i class='bi bi-bell-slash d-block fs-2 mb-2'></i>No notifications yet</li>";
                                            endif;
                                            ?>
                                        </div>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <button class="btn btn-outline-warning me-3" onclick="openLeaderboard()" title="Leaderboard">
                                <i class="fas fa-trophy me-1"></i> Leaderboard
                            </button>
                            <a href="javascript:void(0)" id="cartBtn" class="btn position-relative me-3" title="View Cart">
                                <i class="fas fa-shopping-cart"></i>
                                <span id="cartCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill cart-count-badge"
                                    style="display: <?= $cart_count > 0 ? 'flex' : 'none' ?>;">
                                    <?= $cart_count ?>
                                </span>
                            </a>



                            <?php if ($userExists): ?>
                                <div class="dropdown">
                                    <a class="nav-link dropdown-toggle d-flex align-items-center"
                                        href="#"
                                        id="userDropdown"
                                        role="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">

                                        <?php
                                        $upload_path = "uploads/profile_pics/";
                                        $server_image_path = __DIR__ . '/' . $upload_path . ($userData['image'] ?? '');
                                        $image_url = !empty($userData['image']) && file_exists($server_image_path)
                                            ? $upload_path . $userData['image']
                                            : 'https://ui-avatars.com/api/?name=' . urlencode($userData['full_name']) . '&background=6f42c1&color=fff';
                                        ?>
                                        <img
                                            src="<?= htmlspecialchars($image_url); ?>"
                                            alt="User"
                                            class="rounded-circle me-2"
                                            width="36"
                                            height="36"
                                            style="object-fit: cover;"
                                            onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($userData['full_name']) ?>&background=6f42c1&color=fff';">

                                        <span class="d-none d-md-inline">
                                            <?= htmlspecialchars($userData['full_name']); ?>
                                        </span>
                                    </a>

                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                        <?php if ($userData['role'] === 'admin'): ?>
                                            <li>
                                                <a class="dropdown-item" href="adminpage.php">
                                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                                </a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="logout.php">
                                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                                </a>
                                            </li>

                                        <?php elseif ($userData['role'] === 'teacher'): ?>
                                            <li>
                                                <a class="dropdown-item" href="teacherpage.php">
                                                    <i class="fas fa-chalkboard-teacher me-2"></i> Teacher Dashboard
                                                </a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="logout.php">
                                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                                </a>
                                            </li>

                                        <?php else: ?>
                                            <li>
                                                <a class="dropdown-item" href="student/profile.php">
                                                    <i class="fas fa-user-circle me-2"></i> Profile
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="ourcourses.php">
                                                    <i class="fas fa-video me-2"></i> All Courses
                                                </a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="logout.php">
                                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            <?php else: ?>

                                <a href="login/signup.php" class="btn btn-purple">
                                    <i class="fas fa-sign-in-alt me-2"></i> Login / Register
                                </a>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
        </nav>
    </div>

    <div class="floating-notification" id="floatingNotification">
        <i class="fas fa-check-circle me-2"></i>
        <span id="notificationText">Item added to cart!</span>
    </div>
    <div class="modal fade" id="notiModal" tabindex="-1" aria-labelledby="notiModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="notiModalLabel">Notification Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <h6 id="modalNotiTitle" class="fw-bold mb-3"></h6>
                    <div id="modalNotiMessage" class="text-dark" style="white-space: pre-wrap; line-height: 1.6;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="leaderboardModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div id="leaderboardContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Loading Leaderboard...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const userEmail = '<?= isset($_SESSION['user_email']) ? $_SESSION['user_email'] : "guest"; ?>';
        const cartKey = 'cart_' + userEmail;
        const cartCountEl = document.getElementById('cartCount');

        if (cartCountEl) {
            const dbCount = parseInt(cartCountEl.textContent) || 0;
            localStorage.setItem(cartKey, JSON.stringify(Array(dbCount).fill({
                id: 0
            })));

            updateCartBadge(dbCount);
        }

        function updateCartBadge(count) {
            if (cartCountEl) {
                cartCountEl.textContent = count;
                cartCountEl.style.display = count > 0 ? 'inline-flex' : 'none';
            }
        }

        window.addEventListener('cartUpdated', function(e) {
            const newCount = e.detail?.count || 0;
            updateCartBadge(newCount);

            if (e.detail?.showNotification) {
                showNotification('Item added to cart successfully!');
            }
        });

        function showNotification(message) {
            const notification = document.getElementById('floatingNotification');
            const text = document.getElementById('notificationText');

            text.textContent = message;
            notification.style.display = 'block';

            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            const searchResults = document.querySelector('.search-results');

            const searchData = [{
                    title: 'Web Development',
                    tags: ['HTML', 'CSS', 'JavaScript', 'Web Development', 'Web', 'Development'],
                    url: 'ourcourses.php?category=Web Development',
                    type: 'category',
                    icon: 'fas fa-code'
                },
                {
                    title: 'Web Designing',
                    tags: ['UI/UX', 'Figma', 'Photoshop', 'Web Design', 'Designing', 'Design'],
                    url: 'coming_soon.php?course=Web%20Designing',
                    type: 'category',
                    icon: 'fas fa-palette'
                },
                {
                    title: 'Python',
                    tags: ['Python', 'Django', 'Flask', 'Programming', 'Basics'],
                    url: 'ourcourses.php?category=Python',
                    type: 'category',
                    icon: 'fab fa-python'
                },
                {
                    title: 'Frameworks',
                    tags: ['React', 'Angular', 'Laravel', 'Frameworks', 'Framework'],
                    url: 'ourcourses.php?category=Frameworks',
                    type: 'category',
                    icon: 'fas fa-cubes'
                },
                {
                    title: 'AI & Machine Learning',
                    tags: ['AI', 'Machine Learning', 'ML', 'NLP', 'Natural Language Processing', 'Computer Vision', 'Artificial Intelligence'],
                    url: 'coming_soon.php?course=AI%20%26%20Machine%20Learning',
                    type: 'category',
                    icon: 'fas fa-robot'
                },
                {
                    title: 'Cloud Computing',
                    tags: ['AWS', 'Azure', 'Google Cloud', 'Cloud Computing', 'Cloud'],
                    url: 'coming_soon.php?course=Cloud%20Computing',
                    type: 'category',
                    icon: 'fas fa-cloud'
                },
                {
                    title: 'Cyber Security',
                    tags: ['Cyber Security', 'Ethical Hacking', 'Network Security', 'Cryptography', 'Security'],
                    url: 'coming_soon.php?course=Cyber%20Security',
                    type: 'category',
                    icon: 'fas fa-shield-alt'
                },
                {
                    title: 'API',
                    tags: ['API', 'APIs', 'Postman', 'Thunder Client', 'GraphQL', 'REST API', 'REST'],
                    url: 'ourcourses.php?category=Apis',
                    type: 'category',
                    icon: 'fas fa-plug'
                }
            ];

            function highlightText(text, query) {
                if (!query) return text;
                const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                return text.replace(regex, '<span class="search-highlight">$1</span>');
            }

            function performSearch(searchQuery = null) {
                const query = searchQuery || searchInput.value.trim();

                if (!query) {
                    searchResults.classList.add('d-none');
                    return;
                }

                const lowerQuery = query.toLowerCase();
                const results = searchData.filter(item => {
                    return item.title.toLowerCase().includes(lowerQuery) ||
                        item.tags.some(tag => tag.toLowerCase().includes(lowerQuery));
                });

                displayResults(results, query);
            }

            function displayResults(results, query) {
                if (results.length === 0) {
                    searchResults.innerHTML = `
                <div class="p-4 text-center">
                    <i class="fas fa-search mb-3" style="font-size: 2rem; color: #ccc;"></i>
                    <p class="text-muted mb-0">No results found for "${query}"</p>
                    <small class="text-muted">Try searching with different keywords</small>
                </div>`;
                    searchResults.classList.remove('d-none');
                    return;
                }

                searchResults.innerHTML = results.map(item => `
            <a href="${item.url}" class="search-result-item d-block text-decoration-none text-dark">
                <div class="d-flex align-items-start">
                    <div class="me-3 mt-1" style="color: #6f42c1;">
                        <i class="${item.icon}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${highlightText(item.title, query)}</h6>
                        <small class="text-muted d-block">
                            ${item.tags.slice(0, 3).map(tag => highlightText(tag, query)).join(', ')}
                            ${item.tags.length > 3 ? '...' : ''}
                        </small>
                    </div>
                    <span class="badge bg-purple ms-2 align-self-center">${item.type}</span>
                </div>
            </a>
        `).join('');

                searchResults.classList.remove('d-none');
            }

            searchInput.addEventListener('input', function() {
                performSearch();
            });

            searchInput.addEventListener('focus', function() {
                if (this.value.trim().length >= 1) {
                    performSearch();
                }
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.search-container')) {
                    searchResults.classList.add('d-none');
                }
            });

            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    searchResults.classList.add('d-none');
                    this.blur();
                }

                if (e.key === 'Enter') {
                    e.preventDefault();
                    performSearch();
                    const firstResult = searchResults.querySelector('.search-result-item');
                    if (firstResult) {
                        window.location.href = firstResult.href;
                    }
                }
            });

            searchResults.addEventListener('focusin', function(e) {
                if (e.target.classList.contains('search-result-item')) {
                    e.target.style.backgroundColor = 'rgba(111, 66, 193, 0.05)';
                }
            });

            searchResults.addEventListener('focusout', function(e) {
                if (e.target.classList.contains('search-result-item')) {
                    e.target.style.backgroundColor = '';
                }
            });
        });

        function performSearch() {
            const searchInput = document.querySelector('.search-input');
            const searchResults = document.querySelector('.search-results');
            const query = searchInput.value.trim();

            if (!query) {
                searchInput.focus();
                return;
            }

            const event = new Event('input');
            searchInput.dispatchEvent(event);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const cartBtn = document.getElementById('cartBtn');

            if (!cartBtn) return;

            cartBtn.addEventListener('click', function() {
                const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;

                if (!isLoggedIn) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Login Required',
                        text: 'Please login first to view your cart.',
                        showCancelButton: true,
                        confirmButtonText: 'Go to Login',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#6f42c1',
                        background: '#fff',
                        backdrop: 'rgba(111, 66, 193, 0.1)'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'login/signup.php';
                        }
                    });
                    return;
                }

                window.location.href = 'cart.php';
            });
        });

        $(document).on('click', '#notiBtn', function() {
            const badge = $('#notiCount');

            if (badge.is(':visible')) {
                $.ajax({
                    url: 'mark_notifications_read.php',
                    type: 'POST',
                    success: function() {
                        badge.fadeOut();
                    }
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-link');

            navLinks.forEach(link => {
                if (link.getAttribute('href') && link.getAttribute('href').includes(currentPage)) {
                    link.classList.add('active');
                }
            });
        });

        $(document).ready(function() {
            $(document).on('click', '.view-notification', function() {
                const title = $(this).data('title');
                let message = $(this).data('msg');


                message = message.replace(/\\n/g, '<br>').replace(/\n/g, '<br>');

                $('#modalNotiTitle').text(title);
                $('#modalNotiMessage').html(message);

                var myModal = new bootstrap.Modal(document.getElementById('notiModal'));
                myModal.show();
            });
        });



        function openLeaderboard() {
            const modalEl = document.getElementById('leaderboardModal');
            const contentContainer = document.getElementById('leaderboardContent');

            if (!modalEl) {
                console.error("Error: #leaderboardModal not found in the HTML.");
                return;
            }

            const leaderboardModal = bootstrap.Modal.getOrCreateInstance(modalEl);

            if (contentContainer) {
                contentContainer.innerHTML = '<tr><td colspan="3" class="text-center"><div class="spinner-border text-warning spinner-border-sm"></div> Loading...</td></tr>';
            }

            $("#leaderboardContent").load("leaderboard.php", function(response, status, xhr) {
                if (status === "error") {
                    contentContainer.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Failed to load leaderboard.</td></tr>';
                }
            });

            leaderboardModal.show();
        }
        document.addEventListener("click", function(e) {

            const link = e.target.closest(".coming-soon-link");

            if (!link) return;

            e.preventDefault();

            Swal.fire({
                icon: "info",
                title: "Coming Soon 🚀",
                text: "This course will be uploaded shortly. We will notify you once it becomes available!",
                confirmButtonText: "Got it!",
                confirmButtonColor: "#5624d0",

                backdrop: `
            rgba(0,0,0,0.4)
            backdrop-filter: blur(6px)
        `,

                showClass: {
                    popup: 'animate__animated animate__zoomIn'
                },
                hideClass: {
                    popup: 'animate__animated animate__zoomOut'
                }
            });

        });
    </script>