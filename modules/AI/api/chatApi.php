<?php
/**
 * SFAS — AI Chat API
 * File: modules/AI/api/chatApi.php
 *
 * Calls Anthropic Claude API with a smart farming system prompt.
 * FREE to use — you only pay for what you use via Anthropic API key.
 * Get a free key at: https://console.anthropic.com
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__,3).'/config/paths.php';
require_once dirname(__DIR__,3).'/config/config.php';
require_once dirname(__DIR__,3).'/helpers/AuthMiddleware.php';

// ── Auth required ──────────────────────────────────────────
$auth = new AuthMiddleware();
$user = $auth->requireAuth(['ai.use']);

// ── Input ──────────────────────────────────────────────────
$raw     = file_get_contents('php://input');
$input   = json_decode($raw, true) ?: [];
$message = trim($input['message'] ?? '');
$history = $input['history'] ?? [];  // [{role, content}]

if (!$message) {
    echo json_encode(['success'=>false,'message'=>'Message is required']);
    exit;
}

// ── Sanitize history ────────────────────────────────────────
$cleanHistory = [];
foreach ($history as $h) {
    if (in_array($h['role']??'', ['user','assistant']) && !empty($h['content'])) {
        $cleanHistory[] = [
            'role'    => $h['role'],
            'content' => mb_substr(trim($h['content']), 0, 2000)
        ];
    }
}
// Keep last 8 exchanges max (16 messages)
$cleanHistory = array_slice($cleanHistory, -16);

// ── System prompt (farming expert for Rwanda) ────────────────
$systemPrompt = <<<PROMPT
You are an expert agricultural advisor for the Smart Farming Advisory System (SFAS) serving farmers in Nyagatare District, Rwanda, and across all Rwandan provinces.

Your expertise covers:
- Crop management: Maize (Ibigori), Beans (Ibishyimbo), Irish Potato (Ibirayi), Cassava (Imyumbati), Sorghum (Amasaka), Groundnut, Sweet Potato, Tomato, Onion, and other crops grown in Rwanda
- Rwanda's two main growing seasons: Season A (September–February) and Season B (March–August)
- Pest and disease identification and integrated pest management (IPM)
- Soil health, pH management, and fertilizer recommendations (DAP, Urea, NPK, compost)
- Irrigation and water management
- Post-harvest handling and storage (PICS bags, hermetic storage)
- Market prices and selling strategies
- Climate-smart agriculture for Rwanda's variable rainfall

IMPORTANT RULES:
1. Always give practical, actionable advice specific to Rwanda's context
2. Use local measurements and units (RWF for prices, kg/ha for yields, mm for rainfall)
3. Mention local product brands when relevant (e.g., fertilizers available in Rwanda)
4. When discussing chemicals/pesticides, mention specific active ingredients and rates
5. Keep responses clear and organized — use bullet points and clear sections when helpful
6. If you don't know something specific to Rwanda, say so and suggest consulting MINAGRI or local extension officers
7. You can respond in English, French, or Kinyarwanda depending on what language the farmer uses
8. Be encouraging and supportive — many farmers face difficult conditions

Current context: Nyagatare District (Eastern Province), Rwanda. Season B 2026 is underway.
PROMPT;

// ── Build messages array for API ──────────────────────────
$messages = $cleanHistory;
$messages[] = ['role' => 'user', 'content' => $message];

// ── Call Anthropic Claude API ─────────────────────────────
$apiKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : ($_ENV['ANTHROPIC_API_KEY'] ?? '');

if (empty($apiKey)) {
    // Fallback: return a helpful canned response if no API key configured
    $fallbackReplies = [
        "Thank you for your question! To enable the AI assistant, please add your Anthropic API key to the .env file as ANTHROPIC_API_KEY. You can get a free key at https://console.anthropic.com\n\nIn the meantime, please check the Advisory Tips section for farming guidance.",
    ];
    echo json_encode(['success'=>true,'reply'=>$fallbackReplies[0]]);
    exit;
}

$payload = [
    'model'      => 'claude-sonnet-4-6',
    'max_tokens' => 1000,
    'system'     => $systemPrompt,
    'messages'   => $messages,
];

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: '.$apiKey,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    error_log('SFAS AI cURL error: '.$curlErr);
    echo json_encode(['success'=>false,'message'=>'Could not reach AI service. Check your internet connection.']);
    exit;
}

$data = json_decode($response, true);

if ($httpCode !== 200) {
    $errMsg = $data['error']['message'] ?? 'AI service error ('.$httpCode.')';
    error_log('SFAS AI API error: '.$errMsg);
    echo json_encode(['success'=>false,'message'=>$errMsg]);
    exit;
}

$reply = $data['content'][0]['text'] ?? '';
if (!$reply) {
    echo json_encode(['success'=>false,'message'=>'No response from AI. Please try again.']);
    exit;
}

// ── Optionally log to DB ───────────────────────────────────
try {
    require_once dirname(__DIR__,3).'/config/database.php';
    $db = Database::getConnection();
    $sid = session_id() ?: bin2hex(random_bytes(16));
    $stmt = $db->prepare("INSERT INTO ai_chat_logs (user_id,session_id,role,message) VALUES (:u,:s,:r,:m)");
    $stmt->execute([':u'=>$user->user_id,':s'=>$sid,':r'=>'user',':m'=>mb_substr($message,0,2000)]);
    $stmt->execute([':u'=>$user->user_id,':s'=>$sid,':r'=>'assistant',':m'=>mb_substr($reply,0,2000)]);
} catch (Exception $e) {
    error_log('SFAS chat log error: '.$e->getMessage());
}

echo json_encode(['success'=>true,'reply'=>$reply]);
