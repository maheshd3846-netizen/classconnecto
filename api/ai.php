<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Load config from environment or fallback to config.php
$api_key = getenv('GEMINI_API_KEY');
if (!$api_key && file_exists('../config.php')) {
    $config = require '../config.php';
    $api_key = $config['gemini_api_key'] ?? '';
}

if(empty($api_key) || $api_key === 'YOUR_GEMINI_API_KEY_HERE') {
     echo json_encode(['error' => 'API Key not configured. Please add your Gemini API Key in config.php']);
     exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $query = $data['query'] ?? '';
    $type = $data['type'] ?? 'doubt'; // 'doubt' or 'summary'
    
    if (empty($query)) {
        echo json_encode(['error' => 'Empty query']);
        exit;
    }

    // Construct the prompt based on the request type
    $prompt = "";
    if ($type === 'doubt') {
         $prompt = "You are a helpful AI tutor for an engineering student. They are asking: \"$query\". \nProvide a concise answer with 1) a definition, 2) a simple explanation, and 3) bullet points summarizing the core concepts for an exam. Format your response using basic HTML tags (like <b>, <ul>, <li>, <br>) so it renders correctly on a web page. Do NOT use markdown like **bold**, use HTML <b>bold</b>.";
    } else if ($type === 'summary') {
         $prompt = "You are an AI assistant helping a student revise. Generate a short, 3-bullet point highly summarized 'Quick Revision' for the following topic/unit: \"$query\". Format the response using basic HTML list tags (<ul>, <li>). Do not include markdown.";
    }

    // Prepare Gemini API request
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $api_key;
    
    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "maxOutputTokens" => 800
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $result['candidates'][0]['content']['parts'][0]['text'];
            
            // Log this action in student_activity for the Digital Twin
            require_once '../db/connection.php';
            try {
                 $stmt = $pdo->prepare("INSERT INTO student_activity (user_id, subject_id, activity_type, activity_value) VALUES (:uid, (SELECT id FROM subjects LIMIT 1), 'ask_doubt', 1)");
                 $stmt->execute(['uid' => $_SESSION['user_id']]);
            } catch(Exception $e) {
                 // optionally handle error, but don't break the response
            }

            echo json_encode(['success' => true, 'response' => $text]);
        } else {
            echo json_encode(['error' => 'Unexpected AI response format']);
        }
    } else {
        echo json_encode(['error' => 'Failed to connect to AI server. HTTP Code: ' . $httpCode]);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
