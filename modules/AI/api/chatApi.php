<?php
/**
 * SFAS — AI Chat API (Multi-Provider)
 * File: modules/AI/api/chatApi.php
 *
 * Supports: Google Gemini, Groq, OpenRouter, Anthropic Claude
 * Auto-detects provider from API key prefix
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

// ── Get API Key ─────────────────────────────────────────────
$apiKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : ($_ENV['ANTHROPIC_API_KEY'] ?? '');

if (empty($apiKey)) {
    // Fallback response
    echo json_encode(['success'=>true, 'reply' => 
        "To enable the AI assistant, please add your API key to the .env file.\n\n" .
        "Get a FREE key at:\n" .
        "• Google Gemini: https://aistudio.google.com/app/apikey\n" .
        "• Groq: https://console.groq.com\n" .
        "• OpenRouter: https://openrouter.ai/keys\n\n" .
        "In the meantime, check the Advisory Tips section for farming guidance."
    ]);
    exit;
}

// ── Detect provider from key prefix ────────────────────────
$provider = 'anthropic';
if (str_starts_with($apiKey, 'AIza')) {
    $provider = 'gemini';
} elseif (str_starts_with($apiKey, 'gsk_')) {
    $provider = 'groq';
} elseif (str_starts_with($apiKey, 'sk-or-')) {
    $provider = 'openrouter';
} elseif (str_starts_with($apiKey, 'sk-ant-')) {
    $provider = 'anthropic';
}

// ─────────────────────────────────────────────────────────────
// ── ROUTE TO APPROPRIATE PROVIDER ──────────────────────────
// ─────────────────────────────────────────────────────────────

$reply = '';
$error = '';

switch ($provider) {
    
    /* ──────────────────────────────────────────────────────────
       PROVIDER 1: Google Gemini (FREE)
       ────────────────────────────────────────────────────────── */
    case 'gemini':
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $systemPrompt . "\n\nUser: " . $message]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1000,
            ]
        ];
        
        $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        
        if ($curlErr) {
            $error = 'Could not reach Gemini service. Check your internet connection.';
            break;
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode !== 200) {
            $errMsg = $data['error']['message'] ?? 'Gemini service error ('.$httpCode.')';
            $error = $errMsg;
            break;
        }
        
        $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        break;
    
    /* ──────────────────────────────────────────────────────────
       PROVIDER 2: Groq (FREE - Fastest)
       ────────────────────────────────────────────────────────── */
    case 'groq':
        // Build messages array
        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        foreach ($cleanHistory as $h) {
            $messages[] = $h;
        }
        $messages[] = ['role' => 'user', 'content' => $message];
        
        $payload = [
            'model' => 'llama-3.1-8b-instant',
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ];
        
        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        
        if ($curlErr) {
            $error = 'Could not reach Groq service. Check your internet connection.';
            break;
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode !== 200) {
            $errMsg = $data['error']['message'] ?? 'Groq service error ('.$httpCode.')';
            $error = $errMsg;
            break;
        }
        
        $reply = $data['choices'][0]['message']['content'] ?? '';
        break;
    
    /* ──────────────────────────────────────────────────────────
       PROVIDER 3: OpenRouter (FREE - Many Models)
       ────────────────────────────────────────────────────────── */
    case 'openrouter':
        // Build messages array
        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        foreach ($cleanHistory as $h) {
            $messages[] = $h;
        }
        $messages[] = ['role' => 'user', 'content' => $message];
        
        $payload = [
            'model' => 'meta-llama/llama-3.1-8b-instruct:free',
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ];
        
        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        
        if ($curlErr) {
            $error = 'Could not reach OpenRouter service. Check your internet connection.';
            break;
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode !== 200) {
            $errMsg = $data['error']['message'] ?? 'OpenRouter service error ('.$httpCode.')';
            $error = $errMsg;
            break;
        }
        
        $reply = $data['choices'][0]['message']['content'] ?? '';
        break;
    
    /* ──────────────────────────────────────────────────────────
       PROVIDER 4: Anthropic Claude
       ────────────────────────────────────────────────────────── */
    case 'anthropic':
    default:
        // Build messages array for Claude
        $messages = [];
        foreach ($cleanHistory as $h) {
            $messages[] = $h;
        }
        $messages[] = ['role' => 'user', 'content' => $message];
        
        $payload = [
            'model' => 'claude-sonnet-4-6',
            'max_tokens' => 1000,
            'system' => $systemPrompt,
            'messages' => $messages,
        ];
        
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        
        if ($curlErr) {
            $error = 'Could not reach Anthropic service. Check your internet connection.';
            break;
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode !== 200) {
            $errMsg = $data['error']['message'] ?? 'Anthropic service error ('.$httpCode.')';
            $error = $errMsg;
            break;
        }
        
        $reply = $data['content'][0]['text'] ?? '';
        break;
}

// ── Handle errors ───────────────────────────────────────────
if (!empty($error)) {
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}

if (empty($reply)) {
    echo json_encode(['success' => false, 'message' => 'No response from AI. Please try again.']);
    exit;
}

// ── Log to DB ──────────────────────────────────────────────
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

echo json_encode([
    'success' => true, 
    'reply' => $reply, 
    'provider' => $provider
]);