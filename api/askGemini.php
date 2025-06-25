<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);
$question = $input['message'] ?? '';

$mysqli = new mysqli("localhost", "root", "", "tk_webapp");

if ($mysqli->connect_error) {
    echo json_encode(["reply" => "May problema sa koneksyon ng database."]);
    exit;
}

// Build schema description
$schema = <<<EOT
Tables:
- members(member_id, member_name, member_image, role_id)
- roles(role_id, role_name, role_description)
- aboutus(aboutus_id, background, overview, core_kapwa, core_kalinangan, core_kaginhawaan, mission, vision, council, adv_kalusugan, adv_kalikasan, adv_karunungan, adv_kultura, adv_kasarian, contact_no, about_email, facebook, instagram, address)
- chatbot_faqs(faq_id, question, answer)
- blogs(blog_id, blog_image, blog_category, blog_title, blog_author_id, created_at, updated_at, blog_content, blog_status)
- events(event_id, event_image, event_category, event_title, event_date, event_start_time, event_end_time, event_venue, event_content, event_speakers, event_going, event_status, created_at, updated_at)
EOT;

// Function to generate SQL query using Gemini
function generateSQLQuery($question, $schema) {
    $sqlPrompt = <<<EOD
You are a SQL-specialist assistant for the Tara Kabataan webapp.
Based on the schema below, translate the user's question into a single, safe SELECT SQL statement.
• Only use these tables and columns from the schema
• Only output the SQL query (no explanation, no quotes, no markdown)
• Use proper JOIN syntax when accessing data from multiple tables
• Always use LIMIT 1 for single-value queries
• Make sure to fetch similar data to the question e.g. "partnership" and "partnerships" or any similar typo or spelling could still be searched.
• For role-based / position-based / person-based / who or sino queries, JOIN members and roles tables
• Return "NO_QUERY" if the question is not database-related or cannot be answered with the schema

Schema:
$schema

User question: $question

SQL Query:
EOD;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=AIzaSyBo08LiApEK8pPWo8I2NPpF2Usevh9Kw4Y');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "contents" => [[ "parts" => [[ "text" => $sqlPrompt ]] ]]
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    $sqlQuery = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
    
    return trim($sqlQuery);
}

// Function to execute SQL query safely
function executeSQLQuery($mysqli, $sqlQuery) {
    // Basic SQL injection prevention - only allow SELECT statements
    if (!preg_match('/^\s*SELECT\s+/i', $sqlQuery)) {
        return null;
    }
    
    // Prevent multiple statements
    if (substr_count($sqlQuery, ';') > 1 || preg_match('/;\s*\w/i', $sqlQuery)) {
        return null;
    }
    
    // Execute query
    $result = $mysqli->query($sqlQuery);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row;
    }
    
    return null;
}

// Function to format database results
function formatDatabaseResult($row, $question) {
    if (!$row) return "";
    
    $values = array_values($row);
    $keys = array_keys($row);
    
    // Create a readable format
    $formatted = [];
    for ($i = 0; $i < count($keys); $i++) {
        $formatted[] = ucfirst(str_replace('_', ' ', $keys[$i])) . ": " . $values[$i];
    }
    
    return implode(", ", $formatted);
}

$dataSnippet = "";

// Generate SQL query dynamically
$generatedSQL = generateSQLQuery($question, $schema);

if ($generatedSQL && $generatedSQL !== "NO_QUERY") {
    // Execute the generated query
    $queryResult = executeSQLQuery($mysqli, $generatedSQL);
    
    if ($queryResult) {
        $dataSnippet = formatDatabaseResult($queryResult, $question);
    }
}

$mysqli->close();

// Compose prompt with context for the chatbot response
$prompt = "You are a chatbot for Tara Kabataan (Don't reply this). If only they are asking for your name, you are Baby Baka. Be helpful, informative, direct and concise. Always answer in Filipino. Try to answer in a polite manner. Strictly don't answer the question if it's not related to Tara Kabataan, any questions or other form of mockery and trolling is prohibited and other matters outside Tara Kabataan, just say it's out of your scope.";

if (!empty($dataSnippet)) {
    $prompt .= "\n\nNarito ang impormasyon mula sa database: $dataSnippet";
}
$prompt .= "\n\nTanong ng user: $question";

// Generate final response
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=AIzaSyBo08LiApEK8pPWo8I2NPpF2Usevh9Kw4Y');
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
?>