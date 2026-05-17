<?php
header('Content-Type: application/json');

$apiKey = 'AIzaSyDYjL7AfJKTYZNaNcZb4XkrL4kDKmJ-wLA';
$url = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || !isset($data['text'])) {
    echo json_encode(['error' => 'Invalid input']);
    exit();
}

$prompt = "You are a helpful pharmacy assistant for AS Pharmacy. Help users with medicine queries. User asked: " . $data['text'];

$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['error' => 'API Error', 'details' => $response]);
} else {
    echo $response;
}
?>
