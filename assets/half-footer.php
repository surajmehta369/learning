<style>
    .call-btn {
        position: fixed;
        right: 32px;
        bottom: 32px;
        background: linear-gradient(135deg, #5e47cd, #8a63d2);
        border-radius: 50%;
        box-shadow: 0 10px 30px rgba(94, 71, 205, 0.4);
        width: 70px;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: 3px solid white;
        animation: pulse 2s infinite;
    }

    .call-btn:hover {
        transform: scale(1.15) rotate(10deg);
        box-shadow: 0 15px 40px rgba(94, 71, 205, 0.6);
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 10px 30px rgba(94, 71, 205, 0.4);
        }

        50% {
            box-shadow: 0 10px 40px rgba(94, 71, 205, 0.7);
        }

        100% {
            box-shadow: 0 10px 30px rgba(94, 71, 205, 0.4);
        }
    }

    .popup {
        display: none;
        position: fixed;
        bottom: 120px;
        right: 32px;
        z-index: 1100;
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        padding: 30px;
        min-width: 340px;
        max-width: 380px;
        font-family: 'Poppins', Arial, sans-serif;
        animation: slideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: 1px solid rgba(94, 71, 205, 0.1);
        overflow: hidden;
    }

    .popup::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #5e47cd, #8a63d2);
        border-radius: 20px 20px 0 0;
    }

    @keyframes slideUp {
        from {
            transform: translateY(30px) scale(0.9);
            opacity: 0;
        }

        to {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }

    .popup-content {
        position: relative;
    }

    .close {
        position: absolute;
        right: 0;
        top: -10px;
        font-size: 24px;
        cursor: pointer;
        color: #666;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .close:hover {
        background: rgba(94, 71, 205, 0.1);
        color: #5e47cd;
        transform: rotate(90deg);
    }

    .popup-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid rgba(94, 71, 205, 0.1);
    }

    .popup-header h3 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #333;
        background: linear-gradient(90deg, #5e47cd, #8a63d2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .popup-header p {
        margin: 8px 0 0;
        font-size: 14px;
        color: #666;
        line-height: 1.5;
    }

    .popup-img {
        width: 60px;
        height: 60px;
        filter: drop-shadow(0 4px 8px rgba(94, 71, 205, 0.2));
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    /* Call link - Enhanced */
    .call-link {
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        font-weight: 600;
        text-decoration: none;
        background: linear-gradient(135deg, #5e47cd, #8a63d2);
        border-radius: 12px;
        padding: 15px 25px;
        transition: all 0.3s ease;
        box-shadow: 0 6px 20px rgba(94, 71, 205, 0.3);
        position: relative;
        overflow: hidden;
    }

    .call-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: 0.5s;
    }

    .call-link:hover::before {
        left: 100%;
    }

    .call-link svg {
        margin-right: 12px;
        transition: transform 0.3s ease;
    }

    .call-link:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(94, 71, 205, 0.4);
    }

    .call-link:hover svg {
        transform: scale(1.2) rotate(-5deg);
    }

    /* Enhanced Footer Styles */
    body,
    footer {
        font-family: 'Poppins', 'Montserrat', Arial, sans-serif;
        color: #222;
    }

    footer {
        background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        position: relative;
        overflow: hidden;
    }

    footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #5e47cd, #8a63d2);
    }

    footer h6,
    .footer-heading {
        font-weight: 700;
        font-size: 1.2rem;
        margin-bottom: 1.5rem;
        color: #222;
        position: relative;
        display: inline-block;
    }

    footer h6::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -8px;
        width: 40px;
        height: 3px;
        background: linear-gradient(90deg, #5e47cd, #8a63d2);
        border-radius: 3px;
    }

    footer a {
        color: #555;
        text-decoration: none;
        letter-spacing: 0.02em;
        transition: all 0.3s ease;
        padding: 5px 0;
        display: inline-flex;
        align-items: center;
    }

    footer a:hover {
        color: #5e47cd;
        transform: translateX(8px);
    }

    footer a i {
        width: 20px;
        margin-right: 10px;
        font-size: 1rem;
        transition: transform 0.3s ease;
    }

    footer a:hover i {
        transform: scale(1.2);
    }

    /* Logo Section Enhancement */
    .footer-logo {
        background: linear-gradient(135deg, #5e47cd, #8a63d2);
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        box-shadow: 0 8px 20px rgba(94, 71, 205, 0.3);
    }

    .footer-logo i {
        color: white;
        font-size: 24px;
    }

    .footer-brand {
        font-weight: 700;
        font-size: 1.8rem;
        background: linear-gradient(90deg, #5e47cd, #8a63d2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 15px;
        display: block;
    }

    /* Social Icons Enhancement */
    .social-icons {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }

    .social-icons a {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        overflow: hidden;
    }

    .social-icons a i {
        position: relative;
        z-index: 1;
    }

    .social-icons a:hover {
        transform: translateY(-5px) rotate(10deg);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }

    .social-icons a:hover::before {
        transform: scale(1.1);
    }

    /* List Items Enhancement */
    .list-unstyled li {
        margin-bottom: 12px;
        padding-left: 5px;
        border-left: 3px solid transparent;
        transition: all 0.3s ease;
    }

    .list-unstyled li:hover {
        border-left-color: #5e47cd;
        padding-left: 12px;
    }

    /* Map Link Special */
    .map-link {
        background: linear-gradient(135deg, #5e47cd15, #8a63d215);
        padding: 12px 20px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        border: 1px solid rgba(94, 71, 205, 0.2);
        transition: all 0.3s ease;
    }

    .map-link:hover {
        background: linear-gradient(135deg, #5e47cd25, #8a63d225);
        transform: translateY(-3px);
        border-color: #5e47cd;
        box-shadow: 0 8px 20px rgba(94, 71, 205, 0.15);
    }

    /* Copyright Area Enhancement */
    .copyright-area {
        background: linear-gradient(90deg, #222, #333);
        color: white;
        padding: 25px 0;
        position: relative;
        overflow: hidden;
    }

    .copyright-area::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #5e47cd, #8a63d2);
    }

    .copyright-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .copyright-links {
        display: flex;
        gap: 25px;
    }

    .copyright-links a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: all 0.3s ease;
        position: relative;
        padding: 5px 0;
    }

    .copyright-links a:hover {
        color: white;
    }

    .copyright-links a::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background: #5e47cd;
        transition: width 0.3s ease;
    }

    .copyright-links a:hover::after {
        width: 100%;
    }

    @media (max-width: 768px) {
        .call-btn {
            width: 60px;
            height: 60px;
            right: 20px;
            bottom: 20px;
        }

        .popup {
            min-width: 300px;
            right: 20px;
            bottom: 100px;
            left: 20px;
            margin: 0 auto;
        }

        .copyright-content {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }

        .copyright-links {
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
        }
    }

    @media (max-width: 480px) {
        .popup {
            min-width: 280px;
            padding: 20px;
        }

        .footer-logo {
            width: 40px;
            height: 40px;
        }

        .footer-logo i {
            font-size: 20px;
        }
    }

    .back-to-top {
        position: fixed;
        bottom: 110px;
        right: 32px;
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #5e47cd, #8a63d2);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        opacity: 0;
        visibility: hidden;
        z-index: 999;
        box-shadow: 0 6px 20px rgba(94, 71, 205, 0.3);
    }

    .back-to-top.show {
        opacity: 1;
        visibility: visible;
    }

    .back-to-top:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(94, 71, 205, 0.4);
    }

    @keyframes bounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    #chat-input:focus {
        border-color: #6f42c1 !important;
    }

    #chat-toggle:hover {
        transform: scale(1.1);
    }
</style>


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<footer>
    <div class="copyright-area">
        <div class="container">
            <div class="copyright-content">
                <div class="copyright-text">
                    &copy; 2025 Baseline Skills. All rights reserved.
                </div>
                <div class="copyright-links">
                    <a href="privacypolicy">Privacy Policy</a>
                    <a href="termsofservices">Terms of Service</a>
                    <!-- <a href="coming_soon">Cookie Policy</a>
                    <a href="coming_soon">Sitemap</a> -->
                </div>
            </div>
        </div>
    </div>
</footer>