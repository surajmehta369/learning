<?php
session_name('STUDENT_SESSION');
session_start();

// 1. Capture the score and course from URL
// Ensure your link looks like: generate_certificate.php?score=100&course=Full-Stack%20Web%20Development
$score = isset($_GET['score']) ? $_GET['score'] : 0;
$courseName = isset($_GET['course']) ? urldecode($_GET['course']) : "Mastering Web Development";
$userName = $_SESSION['user_name'] ?? "Learner"; 

if ($score < 75) {
    echo "Invalid access. You need 75% to view this certificate.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - <?php echo htmlspecialchars($userName); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; padding-top: 50px; }
        
        .certificate-wrapper {
            width: 900px;
            margin: 0 auto;
            background: white;
            padding: 50px;
            border: 20px solid #1a237e; 
            outline: 5px solid #c5a059; 
            outline-offset: -15px;
            text-align: center;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        /* Top Right Logo styling */
        .top-right-logo {
            position: absolute;
            top: 30px;
            right: 40px;
            width: 180px; /* Adjusted size as per your "short one" request */
            z-index: 10;
        }

        .cert-title { font-family: 'Georgia', serif; font-size: 55px; color: #1a237e; margin-bottom: 20px; margin-top: 40px; }
        .cert-name { font-size: 40px; border-bottom: 2px solid #333; display: inline-block; padding: 0 40px; margin: 20px 0; font-weight: bold;}
        .score-badge { color: #1a5a96; font-weight: bold; font-size: 24px; }
        
        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none; }
            .certificate-wrapper { box-shadow: none; border: 20px solid #1a237e !important; }
        }
    </style>
</head>
<body>

    <div class="certificate-wrapper">
        <img src="https://baseavangers.topscripts.in/gagan/sms/baselinelearning/login/signin_logo.png" class="top-right-logo" alt="Baseline IT Development">

        <div class="cert-title">CERTIFICATE</div>
        <p class="lead">OF COMPLETION</p>
        
        <p class="mt-4">This is to certify that</p>
        <div class="cert-name"><?php echo htmlspecialchars($userName); ?></div>
        
        <p class="mt-3">has successfully mastered the requirements for</p>
        <h3 class="text-uppercase" style="color: #c5a059; font-weight: bold;">
            <?php echo htmlspecialchars($courseName); ?>
        </h3>
        
        <p class="mt-4 score-badge">Score: <?php echo $score; ?>%</p>
        
        <div class="d-flex justify-content-between mt-5 px-5">
            <div class="text-start">
                <hr>
                <p><strong>Date:</strong> <?php echo date('d M, Y'); ?></p>
            </div>
            <div class="text-end">
                <hr>
                <p><strong>Certificate ID:</strong> <?php echo strtoupper(uniqid('BC-')); ?></p>
            </div>
        </div>
    </div>

    <div class="text-center mt-5 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-lg px-5 mx-2">
            Save as PDF
        </button>
        <a href="add_quiz.php" class="btn btn-outline-secondary btn-lg">Return to Course</a>
    </div>

</body>
</html>