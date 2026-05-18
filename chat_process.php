<?php
session_name('STUDENT_SESSION');
session_start();
include("conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Please login first']);
    exit;
}

if (!isset($_POST['message']) || trim($_POST['message']) === '') {
    echo json_encode(['error' => 'Message cannot be empty']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user_message = trim($_POST['message']);
$api_key = "AIzaSyDy6bgvWziMS86apmiaWkIPOOwS_4wHC1E";

function cleanText($text) {
    return trim(strip_tags($text));
}

$company_info = "
Company Name: Baseline Learning
Address: 1st Floor, F-33, Phase-8, Industrial Area, Sector 73, Sahibzada Ajit Singh Nagar, Punjab 160071
Contact Number: +91 9646106743
Admin Email: admin@baselinelearning.com
";

$user_info = "Information not available.";
$stmt = $conn->prepare("SELECT full_name, email FROM signup WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $user_info = "
Logged-in Student:
Name: " . cleanText($row['full_name']) . "
Email: " . cleanText($row['email']) . "
";
}
$stmt->close();

$purchased_courses = "";
$purchased_course_ids = [];

$stmt = $conn->prepare("
    SELECT course_id, course_title 
    FROM baseline_User_Cart 
    WHERE user_id = ? AND payment_mode = 'success'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $purchased_courses .= "- " . cleanText($row['course_title']) . "\n";
    $purchased_course_ids[] = $row['course_id'];
}
$stmt->close();

if ($purchased_courses == "") {
    $purchased_courses = "No purchased courses found.";
}

$baseline_course_details = "";
$course_videos_data = "";
$progress_data = "";

foreach ($purchased_course_ids as $cid) {

    $stmt = $conn->prepare("SELECT name, description, price, category FROM baseline_courses WHERE id = ?");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($course = $res->fetch_assoc()) {
        $baseline_course_details .= "- " . cleanText($course['name']) .
            " | Category: " . cleanText($course['category']) .
            " | Price: ₹" . $course['price'] . "\n";
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT title, description FROM course_videos WHERE course_id = ?");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($video = $res->fetch_assoc()) {
        $course_videos_data .= "- " . cleanText($video['title']) .
            " | " . cleanText($video['description']) . "\n";
    }
    $stmt->close();

    $total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM course_videos WHERE course_id = ?");
    $total_stmt->bind_param("i", $cid);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result()->fetch_assoc();
    $total_videos = $total_result['total'];
    $total_stmt->close();

    $completed_stmt = $conn->prepare("SELECT COUNT(*) as completed FROM video_progress WHERE user_id = ? AND course_id = ?");
    $completed_stmt->bind_param("ii", $user_id, $cid);
    $completed_stmt->execute();
    $completed_result = $completed_stmt->get_result()->fetch_assoc();
    $completed_videos = $completed_result['completed'];
    $completed_stmt->close();

    $progress_data .= "- Course ID $cid: $completed_videos / $total_videos videos completed\n";
}

if ($baseline_course_details == "") {
    $baseline_course_details = "No course details found.";
}

if ($course_videos_data == "") {
    $course_videos_data = "No videos found.";
}

if ($progress_data == "") {
    $progress_data = "No video progress found.";
}

$all_courses_data = "";
$res = $conn->query("SELECT name, description, price, category FROM baseline_courses");

while ($row = $res->fetch_assoc()) {
    $all_courses_data .= "- " . cleanText($row['name']) .
        " | Category: " . cleanText($row['category']) .
        " | Price: ₹" . $row['price'] . "\n";
}

if ($all_courses_data == "") {
    $all_courses_data = "No courses available.";
}

$quiz_data = "";
$stmt = $conn->prepare("SELECT quiz_id, score, total_questions, percentage FROM quiz_results WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $quiz_data .= "- Quiz ID: " . $row['quiz_id'] .
                  " | Score: " . $row['score'] . "/" . $row['total_questions'] .
                  " | " . $row['percentage'] . "%\n";
}
$stmt->close();

if ($quiz_data == "") {
    $quiz_data = "No quiz results found.";
}

$rating_data = "";
$r_res = $conn->query("
    SELECT c.name, ROUND(AVG(r.rating),1) as avg_rating
    FROM baseline_courses c
    LEFT JOIN course_ratings r ON c.id = r.course_id
    GROUP BY c.id
");

while ($row = $r_res->fetch_assoc()) {
    $rating_data .= "- " . cleanText($row['name']) .
                    " | Rating: " . ($row['avg_rating'] ?? "No ratings") . "\n";
}

if ($rating_data == "") {
    $rating_data = "No ratings available.";
}

$final_prompt = "
You are the official conversational assistant for Baseline Learning.

[DATA SECTIONS]
Company Info: $company_info
Student Info: $user_info
Available Courses: $all_courses_data
Purchased Courses: $purchased_courses
Course Details: $baseline_course_details
Videos: $course_videos_data
Quizzes: $quiz_data
Progress: $progress_data
Ratings: $rating_data

[STRICT INSTRUCTIONS]
1. TONE: Be friendly, professional, and conversational. Do not use phrases like 'Information not available.' 

2. UNRELATED TOPICS (Cricket, Shoes, Politics, etc.):
   If a user asks about anything outside of Baseline Learning, reply: 
   'I am specifically designed to assist with learning and courses on the Baseline Learning platform. For topics like that, it would be best to check a dedicated website or search engine.'

3. RANDOM TEXT (jvddnfsdn, dfgdf, etc.):
   If the message is gibberish or random letters, reply:
   'I'm sorry, I couldn't quite understand that. Could you please rephrase your question or let me know how I can help you with your studies?'

4. SYNONYMS & INTENT:
   - Treat 'lectures', 'videos', 'classes', and 'training' as synonyms for 'Courses'.
   - If a user asks 'I want to know more about [Course Name]', DO NOT just show their purchase status. Look at the 'Available Courses' and 'Course Videos' data to explain what the course covers.

5. MISSING DATA (e.g., .NET):
   If a user asks for a course we don't have, say:
   'We currently don't offer a course on [Topic] at Baseline Learning, but we have some great alternatives in [Category] that you might find useful!'

6. ACADEMIC DOUBTS:
   If they ask a coding question (e.g., 'How to write a loop in Python?'), say:
   'That's a great technical question! To get the best help, please post this in our Doubts section where our mentors can provide a detailed explanation.'

Student: $user_message
Assistant:
";
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=" . $api_key;

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $final_prompt]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.3,
        "maxOutputTokens" => 300,
        "topP" => 0.9
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
    exit;
}

curl_close($ch);

$result = json_decode($response, true);

if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode(['error' => 'AI Error', 'response' => $result]);
    exit;
}

$bot_reply = trim($result['candidates'][0]['content']['parts'][0]['text']);

$stmt = $conn->prepare("INSERT INTO chat_sessions (user_id, message, role) VALUES (?, ?, ?)");

$role_user = "user";
$stmt->bind_param("iss", $user_id, $user_message, $role_user);
$stmt->execute();

$role_model = "model";
$stmt->bind_param("iss", $user_id, $bot_reply, $role_model);
$stmt->execute();

$stmt->close();

echo json_encode(['reply' => $bot_reply]);