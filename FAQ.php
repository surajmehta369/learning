<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>FAQ | Baseline E-Learning</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f5f7fb;
            color: #333;
            line-height: 1.7;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        h1 {
            color: #4f46e5;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
        }

        .faq-item {
            border-bottom: 1px solid #e5e7eb;
            padding: 15px 0;
        }

        .faq-question {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
            color: #111827;
        }

        .faq-question span {
            font-size: 20px;
            transition: transform 0.3s ease;
        }

        .faq-answer {
            display: none;
            margin-top: 10px;
            color: #4b5563;
        }

        .faq-item.active .faq-answer {
            display: block;
        }

        .faq-item.active .faq-question span {
            transform: rotate(45deg);
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #6b7280;
        }

        @media (max-width: 600px) {
            .container {
                padding: 25px;
                margin: 20px;
            }
        }

        .back-btn-wrapper {
            text-align: center;
            margin-top: 40px;
        }

        .back-btn {
            display: inline-block;
            text-decoration: none;
            background: #4f46e5;
            color: #fff;
            padding: 14px 24px;
            border-radius: 50px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.2);
        }

        .back-btn:hover {
            background: #4338ca;
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(79, 70, 229, 0.3);
        }
    </style>
</head>

<body>

    <div class="container">
        <h1>Frequently Asked Questions</h1>
        <p class="subtitle">Find answers to common questions about Baseline E-Learning</p>

        <div class="faq-item">
            <div class="faq-question">
                How do I purchase a course?
                <span>+</span>
            </div>
            <div class="faq-answer">
                You can purchase a course by selecting it and completing payment through our secure checkout powered by Stripe.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Will I get lifetime access to the course?
                <span>+</span>
            </div>
            <div class="faq-answer">
                Yes, once purchased, you can access your course anytime through your account.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                What payment methods are accepted?
                <span>+</span>
            </div>
            <div class="faq-answer">
                We accept payments through Stripe, including credit cards, debit cards, and other supported methods.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Can I share my course access with others?
                <span>+</span>
            </div>
            <div class="faq-answer">
                No. Course access is strictly personal and non-transferable.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Do you provide refunds?
                <span>+</span>
            </div>
            <div class="faq-answer">
                Refunds are subject to our Refund Policy. Please review it before purchasing.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                How can I contact support?
                <span>+</span>
            </div>
            <div class="faq-answer">
                You can contact our support team via email at <strong>support@yourdomain.com</strong>.
            </div>
        </div>

        <div class="back-btn-wrapper">
            <a href="index" class="back-btn">
                ← Back to Cart
            </a>
        </div>

        <div class="footer">
            © 2026 Baseline E-Learning. All rights reserved.
        </div>
    </div>

    <script>
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const item = question.parentElement;
                item.classList.toggle('active');
            });
        });
    </script>

</body>

</html>