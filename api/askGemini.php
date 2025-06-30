<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);
$question = $input['message'] ?? '';
$sessionId = $input['session_id'] ?? session_id(); // Client should send session_id

$mysqli = new mysqli("localhost", "root", "", "tk_webapp");

if ($mysqli->connect_error) {
    echo json_encode(["reply" => "May problema sa koneksyon ng database."]);
    exit;
}

// Create conversation_history table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS conversation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    user_message TEXT NOT NULL,
    bot_response TEXT NOT NULL,
    context_data JSON,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(session_id),
    INDEX(timestamp)
)";
$mysqli->query($createTable);

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

// Function to get conversation history
function getConversationHistory($sessionId, $mysqli, $limit = 5) {
    $stmt = $mysqli->prepare("SELECT user_message, bot_response, context_data FROM conversation_history WHERE session_id = ? ORDER BY timestamp DESC LIMIT ?");
    $stmt->bind_param("si", $sessionId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = [
            'user' => $row['user_message'],
            'bot' => $row['bot_response'],
            'context' => json_decode($row['context_data'], true)
        ];
    }
    
    return array_reverse($history); // Reverse to get chronological order
}

// Function to save conversation
function saveConversation($sessionId, $userMessage, $botResponse, $contextData, $mysqli) {
    $stmt = $mysqli->prepare("INSERT INTO conversation_history (session_id, user_message, bot_response, context_data) VALUES (?, ?, ?, ?)");
    $contextJson = json_encode($contextData);
    $stmt->bind_param("ssss", $sessionId, $userMessage, $botResponse, $contextJson);
    $stmt->execute();
}

// Function to clean old conversations (optional - run periodically)
function cleanOldConversations($mysqli, $daysOld = 7) {
    $stmt = $mysqli->prepare("DELETE FROM conversation_history WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->bind_param("i", $daysOld);
    $stmt->execute();
}

// Function to extract entities from conversation context
function extractEntitiesFromContext($history) {
    $entities = [];
    
    foreach ($history as $turn) {
        $context = $turn['context'] ?? [];
        
        // Extract mentioned people
        if (isset($context['mentioned_people'])) {
            foreach ($context['mentioned_people'] as $person) {
                $entities['people'][$person['name']] = $person;
            }
        }
        
        // Extract mentioned topics
        if (isset($context['topics'])) {
            $entities['topics'] = array_merge($entities['topics'] ?? [], $context['topics']);
        }
    }
    
    return $entities;
}

// Function to resolve pronouns and references
function resolveReferences($question, $entities) {
    $resolvedQuestion = $question;
    
    // Handle pronouns referring to people
    if (preg_match('/\b(siya|he|she|his|her|niya|kaniya)\b/i', $question) && isset($entities['people'])) {
        $lastPerson = end($entities['people']);
        if ($lastPerson) {
            $resolvedQuestion .= " [CONTEXT: Referring to " . $lastPerson['name'] . "]";
        }
    }
    
    // Handle "that person", "yung tao", etc.
    if (preg_match('/\b(that person|yung tao|yung babae|yung lalaki)\b/i', $question) && isset($entities['people'])) {
        $lastPerson = end($entities['people']);
        if ($lastPerson) {
            $resolvedQuestion .= " [CONTEXT: Referring to " . $lastPerson['name'] . "]";
        }
    }
    
    return $resolvedQuestion;
}

// Function to normalize text for fuzzy matching
function normalizeText($text) {
    $text = strtolower($text);
    $text = preg_replace('/(.)\1+/', '$1', $text);
    $text = str_replace(['ph', 'f'], 'p', $text);
    return $text;
}

// Function to calculate similarity between two strings
function fuzzyMatch($needle, $haystack, $threshold = 0.7) {
    $needleNorm = normalizeText($needle);
    $haystackNorm = normalizeText($haystack);
    
    if (strpos($haystackNorm, $needleNorm) !== false) {
        return true;
    }
    
    $similarity = 0;
    similar_text($needleNorm, $haystackNorm, $similarity);
    
    if ($similarity >= ($threshold * 100)) {
        return true;
    }
    
    if (strlen($needleNorm) <= 10) {
        $distance = levenshtein($needleNorm, $haystackNorm);
        $maxLen = max(strlen($needleNorm), strlen($haystackNorm));
        if ($maxLen > 0 && (1 - $distance / $maxLen) >= $threshold) {
            return true;
        }
    }
    
    return false;
}

// Improved function to check if question needs database lookup
function needsDatabaseLookup($question) {
    $specificKeywords = [
        // Existing patterns
        'sino si', 'who is', 'kilala mo si', 'may kilala ka', 'sinong',
        'mission', 'vision', 'misyon', 'bisyon', 'tungkol sa tara kabataan', 'about tara kabataan',
        'miyembro', 'members', 'officers', 'presidente', 'secretary', 'treasurer', 'miyembros',
        'contact', 'email', 'facebook', 'instagram', 'address', 'telepono', 'phone', 'contacts',
        'core values', 'advocacy', 'advocacies', 'background', 'history', 'kasaysayan',
        'events', 'kaganapan', 'blog', 'activities', 'aktibidad', 'event', 'blogs',
        'ilang miyembro', 'how many members', 'sino ang', 'who are the', 'sino sina',
        'ano ang mission', 'ano ang vision', 'saan located', 'where is located', 'nasaan',
        'tara kabataan', 'tarakabataan', 'tk org', 'org', 'organization', 'organisasyon',
        // Context-dependent queries
        'ano ang role', 'what is role', 'anong position', 'what position',
        'saan siya', 'where is he', 'where is she', 'ano ginagawa',
        
        // NEW: Enhanced patterns for position-based queries
        'sino ang president', 'sino ang vice president', 'sino ang secretary', 'sino ang treasurer',
        'sino ang developer', 'sino ang designer', 'sino ang manager', 'sino ang leader',
        'sino ang head', 'sino ang chief', 'sino ang director', 'sino ang coordinator',
        'sino ang officer', 'sino ang member', 'sino ang founder', 'sino ang creator',
        'sino ang nag', 'sino ang gumawa', 'sino ang nagde', 'sino ang nanguna',
        'sino yung president', 'sino yung developer', 'sino yung designer',
        'sino po ang', 'sino ba ang', 'sino naman ang'
    ];
    
    $questionLower = strtolower($question);
    
    // First check: Direct keyword matching
    foreach ($specificKeywords as $keyword) {
        if (strpos($questionLower, $keyword) !== false) {
            return true;
        }
    }
    
    // Second check: Pattern-based matching for "sino ang [position] ng [organization]"
    if (preg_match('/sino\s+ang\s+\w+\s+(ng|sa)\s+(tara\s+kabataan|tk|org)/i', $questionLower)) {
        return true;
    }
    
    // Third check: Pattern for "sino ang [any word]" when combined with organization context
    if (preg_match('/sino\s+ang\s+\w+/i', $questionLower) && 
        (strpos($questionLower, 'tara kabataan') !== false || 
         strpos($questionLower, 'tk') !== false ||
         strpos($questionLower, 'org') !== false)) {
        return true;
    }
    
    // Fourth check: Common role/position words that should trigger database lookup
    $roleKeywords = [
        'president', 'presidente', 'vice president', 'secretary', 'sekretaryo',
        'treasurer', 'ingat-yaman', 'developer', 'programmer', 'designer',
        'manager', 'leader', 'head', 'chief', 'director', 'coordinator',
        'officer', 'opisyal', 'founder', 'creator', 'gumawa', 'nagde'
    ];
    
    foreach ($roleKeywords as $role) {
        if (strpos($questionLower, $role) !== false) {
            return true;
        }
    }
    
    // Fifth check: Fuzzy matching for typos (keep existing logic)
    foreach ($specificKeywords as $keyword) {
        if (fuzzyMatch($keyword, $questionLower, 0.75)) {
            return true;
        }
    }
    
    return false;
}

// Enhanced SQL prompt for better position-based queries
function generateSQLQuery($question, $schema, $contextualQuestion = '') {
    $queryToAnalyze = $contextualQuestion ?: $question;
    
    $sqlPrompt = <<<EOD
You are a SQL-specialist assistant for the Tara Kabataan webapp.
Based on the schema below, translate the user's question into a single, safe SELECT SQL statement.

SPECIAL INSTRUCTIONS FOR PERSON/ROLE QUERIES:
• For "sino si [name]" queries: SELECT m.member_name, r.role_name, r.role_description FROM members m JOIN roles r ON m.role_id = r.role_id WHERE m.member_name LIKE '%[name]%'
• For "sino ang [position]" queries: SELECT m.member_name, r.role_name, r.role_description FROM members m JOIN roles r ON m.role_id = r.role_id WHERE r.role_name LIKE '%[position]%'
• For "sino ang president" type queries, extract the position word (president, developer, secretary, etc.) and search in role_name
• Handle common position variations:
  - president/presidente → 'president'
  - secretary/sekretaryo → 'secretary' 
  - developer/programmer → 'developer'
  - treasurer/ingat-yaman → 'treasurer'
• Use LIKE '%term%' for flexible matching
• Join members and roles tables for complete information

GENERAL RULES:
• Only use tables and columns from the schema
• Only output the SQL query (no explanation, no quotes, no markdown)
• Use proper JOIN syntax when accessing data from multiple tables
• Handle typos and misspellings with LIKE '%term%'
• For multiple results, don't use LIMIT unless specifically asked for one result
• Return "NO_QUERY" if the question is not database-related

Schema:
$schema

User question: $queryToAnalyze

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
    if (!preg_match('/^\s*SELECT\s+/i', $sqlQuery)) {
        return null;
    }
    
    if (substr_count($sqlQuery, ';') > 1 || preg_match('/;\s*\w/i', $sqlQuery)) {
        return null;
    }
    
    $result = $mysqli->query($sqlQuery);
    if ($result && $result->num_rows > 0) {
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    return null;
}

// Function to format database results for RAG context
function formatDatabaseResults($rows, $question) {
    if (!$rows || empty($rows)) return "";
    
    $context = "RELEVANT DATABASE INFORMATION:\n";
    
    foreach ($rows as $index => $row) {
        $context .= "Record " . ($index + 1) . ": ";
        $formatted = [];
        foreach ($row as $key => $value) {
            if ($value !== null && $value !== '') {
                $formatted[] = ucfirst(str_replace('_', ' ', $key)) . ": " . $value;
            }
        }
        $context .= implode(", ", $formatted) . "\n";
    }
    
    return $context;
}

// Function to extract context from database results
function extractContextFromResults($rows, $question) {
    $context = [];
    
    if (!$rows) return $context;
    
    // Extract people mentioned
    foreach ($rows as $row) {
        if (isset($row['member_name'])) {
            $context['mentioned_people'][] = [
                'name' => $row['member_name'],
                'role' => $row['role_name'] ?? '',
                'description' => $row['role_description'] ?? ''
            ];
        }
    }
    
    // Extract topics
    if (preg_match('/mission/i', $question)) {
        $context['topics'][] = 'mission';
    }
    if (preg_match('/vision/i', $question)) {
        $context['topics'][] = 'vision';
    }
    
    return $context;
}

// MAIN PROCESSING STARTS HERE
// Get conversation history
$conversationHistory = getConversationHistory($sessionId, $mysqli);
$entities = extractEntitiesFromContext($conversationHistory);

// Resolve references in the current question
$resolvedQuestion = resolveReferences($question, $entities);

// RAG Process: Retrieve relevant information
$databaseContext = "";
$hasRelevantData = false;
$currentContext = [];

if (needsDatabaseLookup($question)) {
    // Generate SQL query dynamically
    $generatedSQL = generateSQLQuery($question, $schema, $resolvedQuestion);
    
    if ($generatedSQL && $generatedSQL !== "NO_QUERY") {
        // Execute the generated query
        $queryResults = executeSQLQuery($mysqli, $generatedSQL);
        
        if ($queryResults) {
            $databaseContext = formatDatabaseResults($queryResults, $question);
            $hasRelevantData = true;
            $currentContext = extractContextFromResults($queryResults, $question);
        }
    }
}

// Build conversation context for the prompt
$conversationContext = "";
if (!empty($conversationHistory)) {
    $conversationContext = "RECENT CONVERSATION HISTORY:\n";
    foreach (array_slice($conversationHistory, -3) as $turn) { // Last 3 turns
        $conversationContext .= "User: " . $turn['user'] . "\n";
        $conversationContext .= "You: " . $turn['bot'] . "\n\n";
    }
}

// Compose RAG prompt with conversation memory
$prompt = <<<EOD
You are Baby Baka, the friendly chatbot for Tara Kabataan organization. Follow these guidelines:

CONVERSATION RULES:
1. Always respond in Filipino
2. Be conversational, helpful, and friendly
3. Handle greetings, casual chat, and general questions naturally
4. Show personality and warmth in your responses
5. Remember the conversation context and refer to previous topics when relevant

FACTUAL INFORMATION RULES:
6. When asked about SPECIFIC facts about Tara Kabataan (people, contact info, mission, etc.), ONLY use the database information provided below
7. If database information is provided, use it to give accurate answers
8. If NO database information is provided for factual queries, say "Hindi ko makita sa aming records ang impormasyon na yan. Pwede mo bang i-clarify o magbigay ng mas specific na tanong?"
9. NEVER make up or guess factual information about Tara Kabataan
10. For person queries (sino si...), include their position/role if available in the data

CONVERSATION MEMORY:
11. Use the conversation history to understand context and references
12. When someone asks "ano ang role niya?" after mentioning a person, know who they're referring to
13. Connect current questions to previous topics discussed

SCOPE:
14. Stay focused on Tara Kabataan topics
15. For completely unrelated topics, politely redirect: "Pag-usapan natin ang tungkol sa Tara Kabataan. May tanong ka ba tungkol sa amin?"

$conversationContext
EOD;

if ($hasRelevantData) {
    $prompt .= "\n$databaseContext\n";
} else if (needsDatabaseLookup($question)) {
    $prompt .= "\nNOTE: No relevant database information found for this factual query.\n";
}

$prompt .= "\nCurrent User Question: $question\nBaby Baka:";

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

// Save conversation to history
saveConversation($sessionId, $question, $reply, $currentContext, $mysqli);

// Optional: Clean old conversations periodically (uncomment if needed)
// if (rand(1, 100) == 1) { // 1% chance to clean on each request
//     cleanOldConversations($mysqli);
// }

$mysqli->close();

echo json_encode([
    'reply' => $reply,
    'session_id' => $sessionId
]);
?>