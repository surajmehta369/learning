<?php
header('Content-Type: application/json');

$apiKey = "AIzaSyBBVMp-rP1NPAcKd2NPhvsbtan9On1rzwU"; 

$input = json_decode(file_get_contents('php://input'), true);
$rawTitle = $input['title'] ?? 'General Knowledge';
$numQuestions = (stripos($rawTitle, 'Final Quiz') !== false) ? 15 : 5;
$videoTitle = preg_replace('/\[.*?\]\s*/', '', $rawTitle);

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=" . $apiKey;
$prompt = "Generate exactly $numQuestions multiple-choice questions about '$videoTitle'. Return ONLY a JSON array. Format: [{\"question\":\"text\",\"a\":\"opt\",\"b\":\"opt\",\"c\":\"opt\",\"d\":\"opt\",\"correct\":\"a\"}]";

$data = ["contents" => [["parts" => [["text" => $prompt]]]]];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $aiText = $result['candidates'][0]['content']['parts'][0]['text'];
    
    $aiText = str_replace(['```json', '```'], '', $aiText);
    $start = strpos($aiText, '[');
    $end = strrpos($aiText, ']');
    
    if ($start !== false && $end !== false) {
        echo substr($aiText, $start, ($end - $start) + 1);
    } else {
        echo json_encode(["error" => "Format Error", "debug" => $aiText]);
    }
} else {
    echo json_encode([
        "error" => "API Refused Again",
        "message" => $result['error']['message'] ?? "Unknown Error",
        "full_response" => $result 
    ]);
}
?>
