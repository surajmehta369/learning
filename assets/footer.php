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
</style>

<div class="back-to-top" id="backToTop">
    <i class="fas fa-chevron-up"></i>
</div>

<!--<div id="callBtn" class="call-btn" onclick="togglePopup()">
    <svg id="callIcon" xmlns="https://www.w3.org/2000/svg" height="32" width="32" fill="#fff" viewBox="0 0 24 24">
        <path d="M6.62 10.79a15.053 15.053 0 006.59 6.59l2.2-2.2a1 
             1 0 011.01-.24c1.12.37 2.33.57 
             3.58.57a1 1 0 011 1V20a1 1 
             0 01-1 1A17 17 0 013 4a1 1 
             0 011-1h3.5a1 1 0 011 1c0 
             1.25.2 2.46.57 3.58a1 1 
             0 01-.24 1.01l-2.2 2.2z" />
    </svg>
</div>-->

<!-- <div id="popup" class="popup">
    <div class="popup-content">
        <span class="close" onclick="togglePopup()">&times;</span>
        <div class="popup-header">
            <div>
                <h3>Talk to a counsellor</h3>
                <p>Have doubts? Our support team will be happy to assist you!</p>
            </div>
            <img src="https://cdn-icons-png.flaticon.com/512/2922/2922510.png" alt="counsellor" class="popup-img">
        </div>
        <a href="tel:0123456789" class="call-link">
            <svg xmlns="https://www.w3.org/2000/svg" height="22" width="22" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6.62 10.79a15.053 15.053 0 006.59 6.59l2.2-2.2a1 
                 1 0 011.01-.24c1.12.37 2.33.57 
                 3.58.57a1 1 0 011 1V20a1 1 
                 0 01-1 1A17 17 0 
                 013 4a1 1 0 011-1h3.5a1 1 0 
                 011 1c0 1.25.2 2.46.57 
                 3.58a1 1 0 01-.24 1.01l-2.2 2.2z" />
            </svg>
            <span>0123456789</span>
        </a>
        <p class="text-center mt-3 text-muted" style="font-size: 13px;">
            <i class="fas fa-clock me-1"></i> Available Mon-Sat, 9AM-6PM
        </p>
    </div>
</div> -->
<div id="gemini-chat-container" style="position: fixed; bottom: 30px; right: 30px; z-index: 9999; font-family: 'Poppins', sans-serif;">

    <div id="chat-tooltip" style="position: absolute; bottom: 80px; right: 10px; background: white; padding: 12px 20px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); width: 180px; font-size: 13px; color: #444; border: 1px solid #6f42c133; animation: bounce 2s infinite;">
        <span style="color: #6f42c1; font-weight: bold;">AI Online:</span> Ask me about our courses!
        <div style="position: absolute; bottom: -8px; right: 25px; width: 15px; height: 15px; background: white; transform: rotate(45deg); border-right: 1px solid #6f42c133; border-bottom: 1px solid #6f42c133;"></div>
    </div>

    <button id="chat-toggle" style="background: linear-gradient(135deg, #6f42c1, #a166ff); color: white; border: none; border-radius: 50%; width: 65px; height: 65px; cursor: pointer; box-shadow: 0 8px 25px rgba(111, 66, 193, 0.4); transition: all 0.3s ease; position: relative;">
        <i class="fas fa-robot" id="toggle-icon" style="font-size: 28px;"></i>
        <span style="position: absolute; top: 5px; right: 5px; width: 12px; height: 12px; background: #2ecc71; border: 2px solid white; border-radius: 50%;"></span>
    </button>

    <div id="chat-box" style="display: none; width: 380px; height: 550px; background: white; border-radius: 24px; box-shadow: 0 15px 50px rgba(0,0,0,0.15); flex-direction: column; position: absolute; bottom: 90px; right: 0; overflow: hidden; border: 1px solid #f0f0f0;">
        <div style="background: linear-gradient(90deg, #6f42c1, #8e44ad); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: rgba(255,255,255,0.2); padding: 8px; border-radius: 12px;">
                    <i class="fas fa-robot" style="font-size: 20px;"></i>
                </div>
                <div>
                    <div style="font-weight: 600; font-size: 16px;">Baseline AI Support</div>
                    <div style="font-size: 11px; opacity: 0.8;">● Always Online</div>
                </div>
            </div>
            <i class="fas fa-times" id="close-chat" style="cursor: pointer; font-size: 18px; padding: 5px;"></i>
        </div>

        <div id="chat-content" style="flex: 1; overflow-y: auto; padding: 20px; background: #f8f9fa; display: flex; flex-direction: column; gap: 15px; scroll-behavior: smooth;">
            <div style="align-self: flex-start; background: white; color: #444; padding: 12px 16px; border-radius: 0 18px 18px 18px; max-width: 85%; font-size: 14px; line-height: 1.5; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                👋 Hello! I'm your learning assistant. I can help you find <b>Web Development</b> or <b>Python</b> courses. What are you looking to learn today?
            </div>
        </div>

        <div style="padding: 20px; background: white; display: flex; align-items: center; gap: 10px; border-top: 1px solid #eee;">
            <input type="text" id="chat-input" placeholder="Type your message..." style="flex: 1; border: 1px solid #e0e0e0; padding: 12px 18px; border-radius: 30px; outline: none; font-size: 14px; transition: border 0.3s;">
            <button id="send-btn" style="background: #6f42c1; color: white; border: none; width: 45px; height: 45px; border-radius: 50%; cursor: pointer; transition: transform 0.2s;">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<style>
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

<footer class="bg-light pt-5 pb-0 mt-5 border-top">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-5">
                <div class="d-flex align-items-center mb-3">
                    <div class="footer-logo me-3">
                        <i class="fas fa-sliders-h"></i>
                    </div>
                    <span class="footer-brand">Baseline Skills</span>
                </div>
                <p class="text-muted mb-4" style="line-height: 1.7;">We understand that every student has unique needs and abilities, that's why our curriculum is designed to adapt to your needs and help you grow!</p>

                <div class="mb-4">
                    <span class="fw-bold text-dark d-block mb-3">Let's get social:</span>
                    <div class="social-icons">
                        <a href="#" style="background:#1877F2">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a href="#" style="background:#E4405F">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <a href="#" style="background:#FF0000">
                            <i class="fa-brands fa-youtube"></i>
                        </a>
                        <a href="#" style="background:#1DA1F2">
                            <i class="fa-brands fa-twitter"></i>
                        </a>
                        <a href="#" style="background:#0088CC">
                            <i class="fa-brands fa-telegram"></i>
                        </a>
                        <a href="#" style="background:#25D366">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                    </div>

                </div>
            </div>

            <div class="col-6 col-md-2 mb-5">
                <h6 class="fw-bold">Company</h6>
                <ul class="list-unstyled">
                    <li><a href="aboutus" class="text-muted">
                            <i class="fas fa-info-circle me-1"></i> About Us
                        </a></li>
                    <li><a href="contactus" class="text-muted">
                            <i class="fas fa-phone me-1"></i> Contact Us
                        </a></li>
                    <!-- <li><a href="coming_soon" class="text-muted">
                            <i class="fas fa-briefcase me-1"></i> Careers
                        </a></li> -->
                    <li><a href="privacypolicy" class="text-muted">
                            <i class="fas fa-file-alt me-1"></i> Privacy Policy
                        </a></li>
                    <li><a href="termsofservices" class="text-muted">
                            <i class="fas fa-file-contract me-1"></i> Terms of Service
                        </a></li>
                </ul>
            </div>

            <div class="col-6 col-md-2 mb-5">
                <h6 class="fw-bold">Our Centres</h6>
                <ul class="list-unstyled">
                    <li>
                        <a href="https://www.google.com/maps/place/Baseline+IT+Development/@30.7110345,76.7051747,17z/data=!4m14!1m7!3m6!1s0x390fee9fe6a743ff:0x384c4fd813517643!2sBaseline+IT+Development!8m2!3d30.7110345!4d76.7051747!16s%2Fg%2F1pp2tjw9b!3m5!1s0x390fee9fe6a743ff:0x384c4fd813517643!8m2!3d30.7110345!4d76.7051747!16s%2Fg%2F1pp2tjw9b?entry=ttu&g_ep=EgoyMDI1MDkyNC4wIKXMDSoASAFQAw%3D%3D"
                            class="map-link text-muted">
                            <i class="fas fa-map-marker-alt me-2" style="color: #5e47cd;"></i>
                            <div>
                                <strong>Mohali (Punjab)</strong>
                                <small class="d-block text-muted">Visit our center</small>
                            </div>
                        </a>
                    </li>
                    <li class="mt-3">
                        <a href="coming_soon" class="text-muted">
                            <i class="fas fa-plus-circle me-1"></i> More Centers Coming Soon
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-6 col-md-2 mb-5">
                <h6 class="fw-bold">Popular Courses</h6>
                <ul class="list-unstyled">
                    <li><a href="ourcourses?category=Web%20Development" class="text-muted">Web Development</a></li>
                    <li><a href="ourcourses?category=Frameworks" class="text-muted">React JS</a></li>
                    <li><a href="ourcourses?category=Python" class="text-muted">Python</a></li>
                    <li><a href="ourcourses?category=Apis" class="text-muted">APIs Development</a></li>
                    <li><a href="coming_soon?course=AI%20%26%20Machine%20Learning" class="text-muted">AI & ML</a></li>
                </ul>
            </div>

            <div class="col-6 col-md-2 mb-5">
                <h6 class="fw-bold">Connect With Us</h6>
                <ul class="list-unstyled">
                    <li><a href="mailto:support@baselineskills.com" class="text-muted">
                            <i class="fas fa-envelope me-1"></i> Email Us
                        </a></li>
                    <!--<li><a href="javascript:void(0)" onclick="togglePopup()" class="text-muted">
                            <i class="fas fa-headset me-1"></i> Talk To A Counselor
                        </a></li> -->

                    <li><a href="FAQ" class="text-muted">
                            <i class="fas fa-question-circle me-1"></i> FAQ
                        </a></li>
                    <li><a href="e-books.php" class="text-muted">
                            <i class="fas fa-book me-1"></i> E-Books
                        </a></li>
                    <!-- <li><a href="coming_soon" class="text-muted">
                            <i class="fas fa-newspaper me-1"></i> Blog
                        </a></li> -->
                </ul>

                <!-- <div class="mt-4 pt-3 border-top">
                    <h6 class="fw-bold mb-3">Download App</h6>
                    <div class="d-flex gap-2">
                        <a href="coming_soon" class="btn btn-dark btn-sm">
                            <i class="fab fa-apple me-1"></i> App Store
                        </a>
                        <a href="coming_soon" class="btn btn-dark btn-sm">
                            <i class="fab fa-google-play me-1"></i> Play Store
                        </a>
                    </div>
                </div> -->
            </div>
        </div>
    </div>

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

<script>
    $(document).ready(function() {
        $('#chat-toggle, #close-chat').click(function(e) {
            e.preventDefault();
            const chatBox = $('#chat-box');

            if (chatBox.is(':visible')) {
                chatBox.fadeOut(200);
            } else {
                chatBox.css('display', 'flex').hide().fadeIn(200);
                $('#chat-tooltip').fadeOut();
                $('#chat-content').scrollTop($('#chat-content')[0].scrollHeight);
            }
        });

        function appendChatMessage(role, text) {
            let align = (role === 'user') ? 'flex-end' : 'flex-start';
            let bg = (role === 'user') ? '#6f42c1' : 'white';
            let color = (role === 'user') ? 'white' : '#444';
            let radius = (role === 'user') ? '15px 15px 0 15px' : '0 18px 18px 18px';
            let shadow = (role === 'user') ? 'none' : '0 2px 5px rgba(0,0,0,0.05)';

            let html = `
            <div style="align-self: ${align}; background: ${bg}; color: ${color}; padding: 12px 16px; border-radius: ${radius}; max-width: 85%; font-size: 14px; line-height: 1.5; box-shadow: ${shadow}; margin-bottom: 10px;">
                ${text}
            </div>`;

            $('#chat-content').append(html);
            $('#chat-content').scrollTop($('#chat-content')[0].scrollHeight);
        }

        function sendMessage() {
            let msg = $('#chat-input').val().trim();
            if (!msg) return;

            appendChatMessage('user', msg);
            $('#chat-input').val('');

            const typingId = 'typing-' + Date.now();
            $('#chat-content').append(`<div id="${typingId}" style="align-self: flex-start; background: #f0f0f0; color: #777; padding: 10px 14px; border-radius: 15px 15px 15px 0; margin-bottom: 10px; font-size: 12px;">AI is thinking...</div>`);
            $('#chat-content').scrollTop($('#chat-content')[0].scrollHeight);

            $.ajax({
                url: "chat_process.php",
                method: "POST",
                data: {
                    message: msg
                },
                dataType: "json",
                success: function(response) {
                    $(`#${typingId}`).remove();

                    if (response.reply) {
                        appendChatMessage('bot', response.reply);
                    } else if (response.error) {
                        appendChatMessage('bot', response.error);
                    }
                },
                error: function(xhr) {
                    $(`#${typingId}`).remove();
                    appendChatMessage('bot', "Server error: " + xhr.status);
                }
            });
        }

        $('#send-btn').click(sendMessage);
        $('#chat-input').on('keypress', function(e) {
            if (e.which == 13) {
                e.preventDefault();
                sendMessage();
            }
        });
    });
</script>
</body>

</html>