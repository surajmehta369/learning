<?php

session_name('STUDENT_SESSION');
session_start();

date_default_timezone_set('Asia/Kolkata');

include("conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        'success' => false,
        'reply' => 'Please login first 😊'
    ]);

    exit;
}

if (!isset($_POST['message']) || trim($_POST['message']) == '') {

    echo json_encode([
        'success' => false,
        'reply' => 'Please type a message 😊'
    ]);

    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user_message = trim($_POST['message']);
$message = strtolower(trim($user_message));

$api_keys = [

    "AIzaSyAbOMM1c_SrrtADX749TOR2THB0UZD_7xk",
    "AIzaSyBlQaO0sJAP9sK8Q2cx4-F5b8MTMRszG5c"

];

function cleanText($text){

    return trim(strip_tags($text));
}

function reply($text){

    echo json_encode([
        'success' => true,
        'reply' => nl2br($text)
    ]);

    exit;
}

function divider(){

    return "\n━━━━━━━━━━━━━━\n\n";
}

function saveChat($conn,$user_id,$message,$role){

    $stmt = $conn->prepare("
        INSERT INTO chat_sessions(user_id,message,role)
        VALUES(?,?,?)
    ");

    $stmt->bind_param(
        "iss",
        $user_id,
        $message,
        $role
    );

    $stmt->execute();
    $stmt->close();
}

function aiReply($prompt,$api_keys){

    foreach($api_keys as $key){

        $url =
        "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=".$key;

        $data = [

            "contents" => [
                [
                    "parts" => [
                        [
                            "text" => $prompt
                        ]
                    ]
                ]
            ],

            "generationConfig" => [

                "temperature" => 0.4,
                "maxOutputTokens" => 200,
                "topP" => 0.9
            ]
        ];

        $ch = curl_init($url);

        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_TIMEOUT,15);

        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            json_encode($data)
        );

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            ['Content-Type: application/json']
        );

        $response = curl_exec($ch);

        curl_close($ch);

        $result = json_decode($response,true);

        if(
            isset(
                $result['candidates'][0]['content']['parts'][0]['text']
            )
        ){

            return trim(
                $result['candidates'][0]['content']['parts'][0]['text']
            );
        }
    }

    return false;
}

$stmt = $conn->prepare("
    SELECT full_name,email
    FROM signup
    WHERE id = ?
");

$stmt->bind_param("i",$user_id);
$stmt->execute();

$result = $stmt->get_result();

$user_name = "Student";

if($row = $result->fetch_assoc()){

    $user_name = cleanText($row['full_name']);
}

$stmt->close();

$hour = date('H');

if($hour < 12){

    $greeting = "Good morning";
}
elseif($hour < 17){

    $greeting = "Good afternoon";
}
else{

    $greeting = "Good evening";
}

$company_info = "

🏢 BASELINE LEARNING

━━━━━━━━━━━━━━

📍 Address:
1st Floor, F-33, Phase-8,
Industrial Area,
Sector 73,
Sahibzada Ajit Singh Nagar,
Punjab 160071

📧 Email:
admin@baselinelearning.com

📞 Contact:
+91 9646106743

";

if(

    $message == 'hi' ||
    $message == 'hello' ||
    $message == 'hey' ||
    $message == 'hii' ||
    $message == 'hy' ||
    $message == 'hello buddy'

){

    $replyText =

        "$greeting $user_name 👋" .

        "\n\n🚀 Welcome back to Baseline Learning!" .

        "\n\n📚 How can I help you today?";

    saveChat($conn,$user_id,$user_message,'user');
    saveChat($conn,$user_id,$replyText,'model');

    reply($replyText);
}

if(

    preg_match('/^[a-z]{1,20}$/',$message)
    &&
    strlen($message) > 7

){

    $replyText =

        "😅 I couldn't understand that." .

        "\n\n📚 Please ask something related to Baseline Learning.";

    saveChat($conn,$user_id,$user_message,'user');
    saveChat($conn,$user_id,$replyText,'model');

    reply($replyText);
}

if(

    strpos($message,'thank') !== false

){

    $replyText =

        "😊 You're welcome $user_name!" .

        "\n\n🚀 Happy learning with Baseline Learning.";

    reply($replyText);
}

if(

    strpos($message,'office') !== false ||
    strpos($message,'address') !== false ||
    strpos($message,'location') !== false ||
    strpos($message,'contact') !== false ||
    strpos($message,'email') !== false

){

    reply($company_info);
}

if(

    strpos($message,'what is my name') !== false

){

    reply(

        "😊 Your name is $user_name."

    );
}

if(

    strpos($message,'how many courses') !== false ||
    strpos($message,'total courses') !== false

){

    $result = $conn->query("
        SELECT COUNT(*) as total
        FROM baseline_courses
    ");

    $row = $result->fetch_assoc();

    reply(

        "📚 TOTAL COURSES" .

        divider() .

        "🚀 We currently offer " .
        $row['total'] .
        " courses on Baseline Learning."
    );
}

if(

    strpos($message,'how many videos') !== false ||
    strpos($message,'total videos') !== false

){

    $result = $conn->query("
        SELECT COUNT(*) as total
        FROM course_videos
    ");

    $row = $result->fetch_assoc();

    reply(

        "📺 TOTAL VIDEOS" .

        divider() .

        "🚀 We currently have " .
        $row['total'] .
        " learning videos on Baseline Learning."
    );
}

if(

    strpos($message,'students') !== false

){

    $result = $conn->query("
        SELECT COUNT(*) as total
        FROM signup
    ");

    $row = $result->fetch_assoc();

    reply(

        "👨‍🎓 TOTAL STUDENTS" .

        divider() .

        "🚀 Currently " .
        $row['total'] .
        " students are learning on Baseline Learning."
    );
}

if(

    strpos($message,'quiz score') !== false ||
    strpos($message,'my score') !== false

){

    $stmt = $conn->prepare("
        SELECT
        AVG(percentage) as avg_score,
        COUNT(*) as total_quizzes
        FROM quiz_results
        WHERE user_id = ?
    ");

    $stmt->bind_param("i",$user_id);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    $avg = round($result['avg_score'],1);

    reply(

        "📝 QUIZ PERFORMANCE" .

        divider() .

        "🎯 Average Score: $avg%" .

        "\n\n🏆 Total Quizzes Attempted: " .
        $result['total_quizzes']
    );
}

if(

    strpos($message,'progress') !== false

){

    $totalVideos = 0;
    $completedVideos = 0;

    $stmt = $conn->prepare("
        SELECT course_id
        FROM baseline_User_Cart
        WHERE user_id = ?
        AND payment_mode='success'
    ");

    $stmt->bind_param("i",$user_id);
    $stmt->execute();

    $courses = $stmt->get_result();

    while($course = $courses->fetch_assoc()){

        $course_id = $course['course_id'];

        $v = $conn->query("
            SELECT COUNT(*) as total
            FROM course_videos
            WHERE course_id = $course_id
        ");

        $vrow = $v->fetch_assoc();

        $totalVideos += $vrow['total'];

        $p = $conn->query("
            SELECT COUNT(*) as completed
            FROM video_progress
            WHERE user_id = $user_id
            AND course_id = $course_id
        ");

        $prow = $p->fetch_assoc();

        $completedVideos += $prow['completed'];
    }

    $percentage = 0;

    if($totalVideos > 0){

        $percentage =
        round(($completedVideos / $totalVideos) * 100);
    }

    reply(

        "📈 YOUR LEARNING PROGRESS" .

        divider() .

        "🔥 Overall Progress: $percentage%" .

        "\n\n✅ Completed Videos: $completedVideos" .

        "\n\n📺 Total Videos: $totalVideos"
    );
}

if(

    strpos($message,'spent') !== false ||
    strpos($message,'money') !== false

){

    $stmt = $conn->prepare("
        SELECT SUM(course_price) as total
        FROM baseline_User_Cart
        WHERE user_id = ?
        AND payment_mode='success'
    ");

    $stmt->bind_param("i",$user_id);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    $spent = $result['total'];

    if(!$spent){

        $spent = 0;
    }

    reply(

        "💰 YOUR LEARNING INVESTMENT" .

        divider() .

        "💳 Total Amount Spent: ₹" .
        number_format($spent,2)
    );
}

if(

    strpos($message,'best courses') !== false ||
    strpos($message,'top courses') !== false ||
    strpos($message,'recommend') !== false

){

    $query = "

        SELECT
        baseline_courses.name,
        baseline_courses.category,
        AVG(course_ratings.rating) as rating

        FROM baseline_courses

        LEFT JOIN course_ratings
        ON baseline_courses.id = course_ratings.course_id

        GROUP BY baseline_courses.id

        ORDER BY rating DESC

        LIMIT 5

    ";

    $result = $conn->query($query);

    $replyText =

        "🔥 TOP COURSES" .

        divider();

    while($row = $result->fetch_assoc()){

        $replyText .=

            "📘 " .
            cleanText($row['name']) .

            "\n📂 Category: " .
            cleanText($row['category']) .

            "\n⭐ Rating: " .
            round($row['rating'],1) .

            "\n\n━━━━━━━━━━━━━━\n\n";
    }

    reply($replyText);
}

if(

    strpos($message,'courses') !== false ||
    strpos($message,'what courses') !== false ||
    strpos($message,'show courses') !== false

){

    $result = $conn->query("
        SELECT name,category,price
        FROM baseline_courses
        ORDER BY created_at DESC
    ");

    $replyText =

        "📚 AVAILABLE COURSES" .

        divider();

    while($row = $result->fetch_assoc()){

        $replyText .=

            "📘 " .
            cleanText($row['name']) .

            "\n📂 Category: " .
            cleanText($row['category']) .

            "\n💰 Price: ₹" .
            cleanText($row['price']) .

            "\n\n━━━━━━━━━━━━━━\n\n";
    }

    reply($replyText);
}

$courses = [];

$result = $conn->query("
    SELECT *
    FROM baseline_courses
");

while($row = $result->fetch_assoc()){

    $courses[] = $row;
}

$foundCourse = false;

foreach($courses as $course){

    $courseName =
    strtolower($course['name']);

    if(
        strpos($message,$courseName) !== false
    ){

        $foundCourse = true;

        $videoResult = $conn->query("
            SELECT COUNT(*) as total
            FROM course_videos
            WHERE course_id = ".$course['id']
        );

        $videoRow = $videoResult->fetch_assoc();

        $ratingResult = $conn->query("
            SELECT AVG(rating) as avg_rating
            FROM course_ratings
            WHERE course_id = ".$course['id']
        );

        $ratingRow = $ratingResult->fetch_assoc();

        $replyText =

            "📘 " .
            cleanText($course['name']) .

            divider() .

            "📂 Category: " .
            cleanText($course['category']) .

            "\n\n💰 Price: ₹" .
            cleanText($course['price']) .

            "\n\n📺 Videos: " .
            $videoRow['total'] .

            "\n\n⭐ Rating: " .
            round($ratingRow['avg_rating'],1) .

            "\n\n📖 " .
            cleanText($course['description']);

        reply($replyText);
    }
}

if(

    strpos($message,'learn') !== false
    &&
    !$foundCourse

){

    reply(

        "📚 Currently we do not offer that course on Baseline Learning." .

        "\n\n😊 Ask me about our available courses."
    );
}

$badWords = [

    'gali',
    'mc',
    'bc',
    'gu khaayega'

];

foreach($badWords as $word){

    if(
        strpos($message,$word) !== false
    ){

        reply(

            "😊 Let's keep the conversation respectful." .

            "\n\n📚 I'm here to help you with learning and courses."
        );
    }
}

$outsideTopics = [

    'cricket',
    'football',
    'obama',
    'politics',
    'ipl',
    'weather',
    'bitcoin'

];

foreach($outsideTopics as $topic){

    if(
        strpos($message,$topic) !== false
    ){

        reply(

            "🌍 I'm specifically designed to help with Baseline Learning courses and educational guidance 📚"
        );
    }
}

$codingKeywords = [

    'python',
    'javascript',
    'java',
    'php',
    'html',
    'css',
    'react',
    'node'

];

$technicalQuestion = false;

foreach($codingKeywords as $keyword){

    if(
        strpos($message,$keyword) !== false
    ){

        $technicalQuestion = true;
    }
}

if($technicalQuestion){

    $prompt = "

You are the educational assistant of Baseline Learning.

Explain in a short and beginner-friendly way.

Use emojis.

Keep response under 120 words.

Student Question:
$user_message

    ";

    $botReply = aiReply($prompt,$api_keys);

    if(!$botReply){

        $botReply =

        "☕ I'm taking a quick study break right now 😊";
    }

    saveChat($conn,$user_id,$user_message,'user');
    saveChat($conn,$user_id,$botReply,'model');

    reply($botReply);
}

$defaultReplies = [

    "😊 I'm here to help you with courses, quizzes, progress, and learning on Baseline Learning.",

    "📚 Ask me about courses, progress, quizzes, videos, or recommendations.",

    "🚀 You can ask me about our platform, courses, learning progress, and more."

];

$finalReply =
$defaultReplies[array_rand($defaultReplies)];

saveChat($conn,$user_id,$user_message,'user');
saveChat($conn,$user_id,$finalReply,'model');

reply($finalReply);

?>