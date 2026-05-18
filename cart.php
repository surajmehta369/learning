<?php
include("conn.php");
session_name('STUDENT_SESSION');
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login/login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);

$count = 0;
if ($user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM baseline_User_Cart WHERE user_id=? AND payment_mode != 'stripe_paid' AND payment_mode != 'success'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'add') {
        $course_id = intval($_POST['course_id']);
        $course_title = $_POST['course_title'];
        $course_price = floatval($_POST['course_price']);
        $course_image = $_POST['course_image'];

        $stmt = $conn->prepare("INSERT INTO baseline_User_Cart (user_id, course_id, course_title, course_price, course_image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisds", $user_id, $course_id, $course_title, $course_price, $course_image);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }

        $stmt->close();
        exit;
    }

    if ($action === 'remove') {
        $cart_id = intval($_POST['cart_id']);
        $stmt = $conn->prepare("DELETE FROM baseline_User_Cart WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $cart_id, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'clear') {
        $stmt = $conn->prepare("DELETE FROM baseline_User_Cart WHERE user_id=? AND payment_mode != 'stripe_paid' AND payment_mode != 'success'");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }
}

$stmt = $conn->prepare("SELECT * FROM baseline_User_Cart WHERE user_id=? AND payment_mode != 'stripe_paid' AND payment_mode != 'success' ORDER BY added_on DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['course_price'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Baseline Learning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        :root {
            --primary-color: #5624d0;
            --primary-light: #f0ebff;
            --secondary-color: #1c1d1f;
            --accent-color: #ff6b6b;
            --success-color: #1e7b1e;
            --light-bg: #f7f9fa;
            --border-color: #d1d7dc;
            --text-dark: #1c1d1f;
            --text-gray: #6a6f73;
            --text-light: #fff;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.16);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #f8f9fa;
            color: var(--text-dark);
            line-height: 1.6;
        }

        .page-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
            border-radius: 0 0 20px 20px;
        }

        .page-title {
            font-weight: 800;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .cart-item {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .cart-item:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .cart-item-content {
            display: flex;
            align-items: center;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .cart-item-content {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        .course-image {
            width: 180px;
            height: 120px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 25px;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .course-image {
                width: 100%;
                height: 200px;
                margin-right: 0;
                margin-bottom: 20px;
            }
        }

        .course-details {
            flex-grow: 1;
        }

        .course-title {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .course-category {
            color: var(--primary-color);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .course-description {
            color: var(--text-gray);
            font-size: 0.95rem;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .course-price {
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--text-dark);
        }

        .course-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .remove-btn {
            background: transparent;
            border: 1px solid #ff4444;
            color: #ff4444;
            padding: 6px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .remove-btn:hover {
            background: #ff4444;
            color: white;
            transform: translateY(-2px);
        }

        .view-btn {
            background: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            padding: 6px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .view-btn:hover {
            background: var(--primary-light);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .summary-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            padding: 30px;
            position: sticky;
            top: 100px;
        }

        .summary-title {
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 25px;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-details {
            margin-bottom: 25px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .row-label {
            color: var(--text-gray);
            font-size: 0.95rem;
        }

        .row-value {
            font-weight: 600;
            color: var(--text-dark);
        }

        .total-row {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--text-dark);
            padding-top: 15px;
            border-top: 2px solid var(--border-color);
        }

        .total-row .row-value {
            color: var(--primary-color);
            font-size: 1.4rem;
        }

        .checkout-btn {
            background: linear-gradient(135deg, var(--primary-color), #7c4dff);
            color: white;
            border: none;
            padding: 16px;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 10px;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .checkout-btn:hover {
            background: linear-gradient(135deg, #4a1fb8, #6a3dff);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(86, 36, 208, 0.3);
        }

        .clear-btn {
            background: transparent;
            border: 1px solid #ff4444;
            color: #ff4444;
            padding: 14px;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .clear-btn:hover {
            background: #ff4444;
            color: white;
            transform: translateY(-2px);
        }

        .empty-cart {
            text-align: center;
            padding: 50px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            max-width: 800px;
            margin: 0 auto;
        }

        .empty-icon {
            font-size: 5rem;
            color: var(--border-color);
            margin-bottom: 30px;
        }

        .empty-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 15px;
        }

        .empty-subtitle {
            color: var(--text-gray);
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .browse-btn {
            background: linear-gradient(135deg, var(--primary-color), #7c4dff);
            color: white;
            border: none;
            padding: 15px 45px;
            font-weight: 600;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .browse-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(86, 36, 208, 0.3);
            color: white;
        }

        .browse-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(86, 36, 208, 0.3);
            color: white;
        }

        .guarantee-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: var(--primary-light);
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid var(--primary-color);
        }

        .guarantee-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .guarantee-text {
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        @media (max-width: 992px) {
            .summary-card {
                position: static;
                margin-top: 30px;
            }
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .cart-item-content {
                padding: 15px;
            }

            .summary-card {
                padding: 20px;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .cart-item {
            animation: fadeIn 0.5s ease forwards;
        }

        .cart-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <?php include("assets/header.php"); ?>

    <div class="page-header">
        <div class="container">
            <h1 class="page-title">Shopping Cart</h1>
            <p class="page-subtitle">Review your selected courses and proceed to checkout</p>
        </div>
    </div>

    <div class="container cart-container">
        <div class="row">

            <div class="col-lg-<?= !empty($cart_items) ? '8' : '12' ?>">
                <?php if (empty($cart_items)): ?>
                    <div class="empty-cart">
                        <div class="empty-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3 class="empty-title">Your cart is empty</h3>
                        <p class="empty-subtitle">
                            Looks like you haven't added any courses to your cart yet.
                            Browse our catalog and find the perfect courses to start your learning journey.
                        </p>
                        <a href="ourcourses.php" class="browse-btn">
                            <i class="fas fa-book-open"></i> Browse Courses
                        </a>
                    </div>
                <?php else: ?>
                    <div class="mb-4">
                        <h4 class="mb-3">Your Selected Courses (<?= count($cart_items) ?>)</h4>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <div class="cart-item-content">
                                    <img src="<?= $item['course_image'] ? '/' . htmlspecialchars($item['course_image']) : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80' ?>"
                                        alt="<?= htmlspecialchars($item['course_title']) ?>"
                                        class="course-image"
                                        onerror="this.src='https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';">

                                    <div class="course-details">
                                        <h5 class="course-title"><?= htmlspecialchars($item['course_title']) ?></h5>
                                        <div class="course-price mb-3">
                                            ₹<?= number_format($item['course_price'], 2) ?>
                                        </div>

                                        <div class="course-actions">
                                            <a href="viewmore.php?id=<?= $item['course_id'] ?>" class="view-btn">
                                                <i class="fas fa-eye"></i> View Course
                                            </a>
                                            <button class="remove-btn" data-id="<?= $item['id'] ?>">
                                                <i class="fas fa-trash-alt"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($cart_items)): ?>
                <div class="col-lg-4">
                    <div class="summary-card">
                        <h4 class="summary-title">
                            <i class="fas fa-receipt"></i> Order Summary
                        </h4>

                        <div class="summary-details">
                            <div class="summary-row total-row">
                                <span class="row-label">Total Amount</span>
                                <span class="row-value">₹<?= number_format($total, 2) ?></span>
                            </div>
                        </div>

                        <a href="checkout.php" class="checkout-btn">
                            <i class="fas fa-lock"></i> Proceed to Checkout
                        </a>

                        <button class="clear-btn" id="clearCart">
                            <i class="fas fa-trash"></i> Clear All Items
                        </button>

                        <div class="guarantee-badge">
                            <div class="guarantee-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="guarantee-text">
                                <strong>30-Day Money-Back Guarantee</strong><br>
                                Full refund if you're not satisfied
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include("assets/footer.php"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.remove-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const cartId = this.dataset.id;
                    const button = this;
                    const originalHTML = button.innerHTML;

                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
                    button.disabled = true;

                    fetch('cart.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'action=remove&cart_id=' + cartId
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showToast('Course removed from cart successfully!', 'success');

                                const currentCount = parseInt('<?= count($cart_items) ?>') || 0;
                                updateCartCount(currentCount - 1);

                                const cartItem = button.closest('.cart-item');
                                cartItem.style.opacity = '0';
                                cartItem.style.transform = 'translateX(-100%)';
                                cartItem.style.transition = 'all 0.3s ease';

                                setTimeout(() => {
                                    location.reload();
                                }, 300);
                            } else {
                                button.innerHTML = originalHTML;
                                button.disabled = false;
                                showToast('Error removing item: ' + data.error, 'error');
                            }
                        })
                        .catch(error => {
                            button.innerHTML = originalHTML;
                            button.disabled = false;
                            showToast('Network error: ' + error, 'error');
                        });
                });
            });

            const clearBtn = document.getElementById('clearCart');
            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    const button = this;
                    const originalHTML = button.innerHTML;

                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing...';
                    button.disabled = true;

                    fetch('cart.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'action=clear'
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showToast('Cart cleared successfully!', 'success');

                                updateCartCount(0);
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                button.innerHTML = originalHTML;
                                button.disabled = false;
                                showToast('Error clearing cart: ' + data.error, 'error');
                            }
                        })
                        .catch(error => {
                            button.innerHTML = originalHTML;
                            button.disabled = false;
                            showToast('Network error: ' + error, 'error');
                        });
                });
            }

            function showToast(message, type) {
                const toast = document.createElement('div');
                toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} cart-toast alert-dismissible fade show`;
                toast.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.remove();
                }, 3000);
            }

            document.querySelectorAll('.cart-item').forEach((item, index) => {
                item.style.animationDelay = `${index * 0.1}s`;
            });

            function updateCartCount(count) {
                const cartCountBadge = document.querySelector('.cart-count-badge');
                const cartCountById = document.getElementById('cartCount');

                if (cartCountBadge) {
                    cartCountBadge.textContent = count;
                    if (count === 0) {
                        cartCountBadge.style.display = 'none';
                    } else {
                        cartCountBadge.style.display = 'flex';
                    }
                }

                if (cartCountById) {
                    cartCountById.textContent = count;
                    if (count === 0) {
                        cartCountById.style.display = 'none';
                    } else {
                        cartCountById.style.display = 'flex';
                    }
                }
            }
        });
    </script>
</body>

</html>