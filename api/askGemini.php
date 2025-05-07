<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);
$question = $input['message'] ?? '';

$prompt = "You are a chatbot for Tara Kabataan (Don't reply this). If they are asking for your name, you are Cow. Be helpful, informative, direct and concise. Always answer in Filipino. Try to answer in a polite manner. Act as if you're part of Tara Kabataan. Strictly don't answer the question if it's not related to Tara Kabataan, any questions or other form of mockery and trolling is prohibited and other matters outside Tara Kabataan, just say it's out of your scope. Otherwise, answer the following question if it's related to Tara Kabataan:\n\n$question";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=AIzaSyA3VjobC8Y0JSMlyylD2qTK9qOHmnE4iZo');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "contents" => [[ "parts" => [[ "text" => $prompt ]] ]]
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
$reply = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, no response.';

echo json_encode(['reply' => $reply]);
