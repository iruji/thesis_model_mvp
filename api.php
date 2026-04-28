<?php

/**
 * JARVIS Chatbot API
 * SFAC Enhanced Edition - MVP v1.0
 */

// ── Error handling ────────────────────────────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// ── CORS ──────────────────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── Dependencies ──────────────────────────────────────────────────────────
require_once __DIR__ . '/Core/DTO/ProcessedInput.php';
require_once __DIR__ . '/Core/DTO/IntentMatch.php';
require_once __DIR__ . '/Core/DTO/ChatResponse.php';
require_once __DIR__ . '/Core/Preprocessor.php';
require_once __DIR__ . '/Core/PatternMatcher.php';
require_once __DIR__ . '/Core/KeywordScorer.php';
require_once __DIR__ . '/Core/ResponseBuilder.php';
require_once __DIR__ . '/Core/AnalyticsLogger.php';
require_once __DIR__ . '/Session/SessionManager.php';

// ── Bootstrap ─────────────────────────────────────────────────────────────
$templates      = require __DIR__ . '/Data/responses.php';
$preprocessor   = new Preprocessor();
$patternMatcher = new PatternMatcher();
$keywordScorer  = new KeywordScorer();
$responseBuilder = new ResponseBuilder($templates);
$logger         = new AnalyticsLogger();
$sessionManager = new SessionManager();

// ── Rate limiting ─────────────────────────────────────────────────────────
session_start();

$RATE_LIMIT  = 30;
$RATE_WINDOW = 60;

function isRateLimited(): bool
{
    global $RATE_LIMIT, $RATE_WINDOW;

    if (!isset($_SESSION['request_times'])) {
        $_SESSION['request_times'] = [];
    }

    $now = time();
    $_SESSION['request_times'] = array_filter(
        $_SESSION['request_times'],
        fn($t) => ($now - $t) < $RATE_WINDOW
    );

    if (count($_SESSION['request_times']) >= $RATE_LIMIT) {
        return true;
    }

    $_SESSION['request_times'][] = $now;
    return false;
}

// ── Input validation ──────────────────────────────────────────────────────
function validateMessage(mixed $text, int $maxLength = 500): ?string
{
    if (empty($text) || !is_string($text)) return null;
    $text = trim($text);
    if (strlen($text) === 0 || strlen($text) > $maxLength) return null;
    return $text;
}

function validateSessionId(mixed $id): bool
{
    if (empty($id) || !is_string($id)) return false;
    if (strlen($id) > 50) return false;
    return (bool) preg_match('/^[a-zA-Z0-9_-]+$/', $id);
}

// ── Profanity filter ──────────────────────────────────────────────────────
function containsProfanity(string $text): bool
{
    $words = [
        // Filipino
        'putang',
        'puta',
        'gago',
        'gaga',
        'bobo',
        'boba',
        'tangina',
        'tanga',
        'ulol',
        'tarantado',
        'gunggong',
        'hayop',
        'leche',
        'punyeta',
        'pakyu',
        'yawa',
        'lintik',
        'bwisit',
        'engot',
        // English
        'fuck',
        'shit',
        'ass',
        'asshole',
        'bitch',
        'bastard',
        'crap',
        'idiot',
        'stupid',
        'moron',
        'wtf',
        'stfu',
        'dick',
        'cunt',
        'slut',
        'whore',
    ];
    foreach ($words as $word) {
        if (str_contains($text, $word)) return true;
    }
    return false;
}

function getProfanityResponse(string $name): string
{
    $responses = [
        "I would appreciate if we kept this conversation respectful, {$name}. I am here to help with SFAC inquiries.",
        "That language is not something I can engage with, {$name}. Feel free to ask me about SFAC programs or services.",
        "Let us keep things respectful, {$name}. I am happy to assist with any SFAC-related questions.",
    ];
    return $responses[array_rand($responses)];
}

// ── Shutdown words ────────────────────────────────────────────────────────
$shutdownWords = [
    'quit',
    'exit',
    'goodbye',
    'bye',
    'bye bye',
    'shutdown',
    'power off',
    'log off',
    'sign out',
    'okay bye',
    'ok bye',
    'see ya',
    'see you',
    'cya',
    'later',
    'gtg',
    'gotta go',
];

// ── Helper functions for multi-intent processing ────────────────────────────

/**
 * Detect multiple intents by splitting on conjunctions
 */
function detectMultipleIntents(
    ProcessedInput $processed,
    PatternMatcher $patternMatcher,
    KeywordScorer  $keywordScorer
): array {
    $separators = [
        ' and ',
        ' also ',
        ' plus ',
        ' as well as ',
        ' pati ',
        ' at ',
        ' tapos '
    ];

    $parts   = [strtolower($processed->normalized)];
    $intents = [];

    foreach ($separators as $sep) {
        $newParts = [];
        foreach ($parts as $part) {
            $split = explode($sep, $part);
            foreach ($split as $s) {
                $s = trim($s);
                if (strlen($s) > 2) $newParts[] = $s;
            }
        }
        if (count($newParts) > count($parts)) {
            $parts = $newParts;
        }
    }

    foreach ($parts as $part) {
        if (strlen($part) < 3) continue;

        $tempInput = new ProcessedInput(
            original: $part,
            normalized: $part,
            tokens: array_filter(explode(' ', $part), fn($t) => strlen($t) > 1),
            language: $processed->language,
        );

        // Check for program keywords within each part
        $programKeywords = [
            'computer science'         => 'computer_science',
            'hospitality management'   => 'hospitality',
            'hospitality'              => 'hospitality',
            'tourism management'       => 'tourism',
            'tourism'                  => 'tourism',
            'business administration'  => 'business_admin',
            'physical education'       => 'physical_education',
            'early childhood education' => 'early_childhood_ed',
            'elementary education'     => 'elementary_ed',
            'secondary education'      => 'secondary_ed',
            'senior high school'       => 'senior_high',
            'junior high school'       => 'junior_high',
            'grade school'             => 'grade_school',
            'preschool'                => 'preschool',
            'stem'                     => 'stem',
            'accountancy business management' => 'abm',
            'humanities social sciences'      => 'humss',
            'general academics'        => 'ga',
            'home economics'           => 'he',
        ];

        // Add any programs found in this part as separate intents
        foreach ($programKeywords as $keyword => $intent) {
            if (str_contains($part, $keyword) && !in_array($intent, $intents)) {
                $intents[] = $intent;
            }
        }

        // Also run normal intent detection
        $match = $patternMatcher->match($tempInput)
            ?? $keywordScorer->score($tempInput);

        if (
            !$match->isUnknown()
            && !in_array($match->intent, $intents)
            && !in_array($match->intent, array_values($programKeywords))
        ) {
            $intents[] = $match->intent;
        }

        if (count($intents) >= 3) break;
    }

    return $intents;
}

/**
 * Build a combined response for multiple intents
 */
function buildMultiResponse(
    array           $intents,
    ProcessedInput  $processed,
    array           $session,
    ResponseBuilder $responseBuilder,
    array           $templates,
    string          $identifier
): ChatResponse {
    $combined = "I found answers to your questions, {$identifier}:\n\n";

    foreach ($intents as $i => $intent) {
        $templateSet = $templates[$intent] ?? $templates['unknown'];
        $template    = $templateSet[array_rand($templateSet)];
        $answer      = str_replace(
            ['{name}', '{title}'],
            [$identifier, $identifier],
            $template
        );
        $label = ucwords(str_replace('_', ' ', $intent));
        $combined .= "► {$label}:\n{$answer}";
        if ($i < count($intents) - 1) {
            $combined .= "\n\n";
        }
    }

    return new ChatResponse(
        message: $combined,
        intent: implode('+', $intents),
        confidence: 0.9,
        source: 'multi',
        followUp: null,
    );
}

// ── Router ────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? 'home';

switch ($action) {

    // ── HOME ─────────────────────────────────────────────────────────────
    case 'home':
        echo json_encode([
            'name'    => 'JARVIS API',
            'version' => '1.0.0',
            'status'  => 'operational',
            'endpoints' => [
                'POST ?action=chat',
                'POST ?action=set_title',
                'GET  ?action=health',
                'GET  ?action=stats',
            ],
        ]);
        break;

    // ── CHAT ─────────────────────────────────────────────────────────────
    case 'chat':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        if (isRateLimited()) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests. Please wait a moment.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $rawMessage = validateMessage($input['message'] ?? null);
        if (!$rawMessage) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or missing message.']);
            exit;
        }

        $sessionId = $input['session_id'] ?? 'default_' . time();
        if (!validateSessionId($sessionId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid session ID.']);
            exit;
        }

        // Get session
        $session    = $sessionManager->getSession($sessionId);
        $identifier = $session['name'] ?? $session['title'] ?? 'Sir';
        $lowered    = strtolower(trim($rawMessage));

        // Shutdown check
        if (in_array($lowered, $shutdownWords)) {
            $goodbyes = [
                "Shutting down systems. Until next time, {$identifier}. SFAC looks forward to serving you again.",
                "Powering off. Farewell, {$identifier}. Remember, excellence in education awaits at SFAC.",
                "System offline. Goodbye, {$identifier}. Feel free to contact SFAC anytime.",
            ];
            echo json_encode([
                'response' => $goodbyes[array_rand($goodbyes)],
                'status'   => 'shutdown',
            ]);
            exit;
        }

        // Profanity check
        if (containsProfanity($lowered)) {
            $sessionManager->update($sessionId, ['awaiting_followup' => null]);
            echo json_encode([
                'response' => getProfanityResponse($identifier),
                'status'   => 'success',
            ]);
            exit;
        }

        // Name extraction
        $namePatterns = [
            '/my name is\s+([a-zA-Z\s]{2,20})/i',
            '/call me\s+([a-zA-Z]{2,20})/i',
            '/i am\s+([a-zA-Z]{2,20})/i',
        ];
        foreach ($namePatterns as $pattern) {
            if (preg_match($pattern, $rawMessage, $m)) {
                $name = ucwords(trim($m[1]));
                $commonWords = [
                    'happy',
                    'sad',
                    'fine',
                    'good',
                    'bad',
                    'interested',
                    'excited',
                    'ready',
                    'done'
                ];
                if (!in_array(strtolower($name), $commonWords) && strlen($name) >= 2) {
                    $sessionManager->update($sessionId, ['name' => $name]);
                    echo json_encode([
                        'response' => "Nice to meet you, {$name}. How can I help you with SFAC today?",
                        'status'   => 'success',
                    ]);
                    exit;
                }
            }
        }

        // Follow-up handler
        $awaitingFollowUp = $session['awaiting_followup'] ?? null;
        if ($awaitingFollowUp) {
            $positive = [
                'yes',
                'yeah',
                'yep',
                'yup',
                'sure',
                'ok',
                'okay',
                'please',
                'go ahead',
                'oo',
                'opo',
                'sige',
                'of course'
            ];
            $negative = [
                'no',
                'nope',
                'nah',
                'no thanks',
                'hindi',
                'wag',
                'ayaw',
                'pass',
                'skip',
                'never mind',
                'nevermind'
            ];

            $isYes = false;
            $isNo  = false;

            foreach ($positive as $word) {
                if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $lowered)) {
                    $isYes = true;
                    break;
                }
            }
            foreach ($negative as $word) {
                if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $lowered)) {
                    $isNo = true;
                    break;
                }
            }

            if ($isYes) {
                $sessionManager->update($sessionId, ['awaiting_followup' => null]);
                $followUpTemplates = $templates[$awaitingFollowUp] ?? $templates['unknown'];
                $followUpTemplate  = $followUpTemplates[array_rand($followUpTemplates)];
                $followUpMessage   = str_replace(
                    ['{name}', '{title}'],
                    [$identifier, $identifier],
                    $followUpTemplate
                );
                echo json_encode([
                    'response' => $followUpMessage,
                    'status'   => 'success',
                ]);
                exit;
            }

            if ($isNo) {
                $sessionManager->update($sessionId, ['awaiting_followup' => null]);
                $noResponses = [
                    "No problem, {$identifier}. Let me know if there is anything else I can help you with.",
                    "Understood, {$identifier}. Feel free to ask if you have other questions about SFAC.",
                    "Alright, {$identifier}. I am here whenever you need more information.",
                ];
                echo json_encode([
                    'response' => $noResponses[array_rand($noResponses)],
                    'status'   => 'success',
                ]);
                exit;
            }

            // Not yes or no — clear follow-up and process normally
            $sessionManager->update($sessionId, ['awaiting_followup' => null]);
        }

        // ── Main pipeline ─────────────────────────────────────────────
        try {
            $processed = $preprocessor->process($rawMessage);

            // Check for multi-intent conjunctions
            $conjunctions = [
                ' and ',
                ' also ',
                ' as well as ',
                ' plus ',
                ' pati ',
                ' at ',
                ' tapos '
            ];
            $hasConjunction = false;
            foreach ($conjunctions as $conj) {
                if (str_contains(strtolower($rawMessage), $conj)) {
                    $hasConjunction = true;
                    break;
                }
            }

            if ($hasConjunction) {
                $match    = $patternMatcher->match($processed)
                    ?? $keywordScorer->score($processed);
                $intents  = detectMultipleIntents(
                    $processed,
                    $patternMatcher,
                    $keywordScorer
                );
                $response = $responseBuilder->build($match, $session);

                if (count($intents) > 1) {
                    $response = buildMultiResponse(
                        $intents,
                        $processed,
                        $session,
                        $responseBuilder,
                        $templates,
                        $identifier
                    );
                }
            } else {
                $match    = $patternMatcher->match($processed)
                    ?? $keywordScorer->score($processed);
                $response = $responseBuilder->build($match, $session);
            }

            // Store follow-up in session if present
            if ($response->followUp !== null) {
                $followUpMap = [
                    'admission'        => 'tuition',
                    'tuition'          => 'admission',
                    'course'           => 'admission',
                    'senior_high'      => 'course',
                    'junior_high'      => 'senior_high',
                    'grade_school'     => 'junior_high',
                    'preschool'        => 'grade_school',
                    'computer_science' => 'admission',
                    'computer_technology' => 'admission',
                    'business_admin'   => 'admission',
                    'tourism'          => 'admission',
                    'hospitality'      => 'admission',
                    'physical_education'  => 'admission',
                    'early_childhood_ed'  => 'admission',
                    'elementary_ed'    => 'admission',
                    'secondary_ed'     => 'admission',
                    'stem'             => 'senior_high',
                    'abm'              => 'senior_high',
                    'humss'            => 'senior_high',
                    'ga'               => 'senior_high',
                    'he'               => 'senior_high',
                    'location'         => 'contact',
                    'contact'          => 'location',
                    'facilities'       => 'website_opac',
                ];

                $nextIntent = $followUpMap[$match->intent] ?? null;
                if ($nextIntent) {
                    $sessionManager->update($sessionId, [
                        'awaiting_followup' => $nextIntent,
                    ]);
                }
            }

            // Log everything
            $logger->log($sessionId, $processed, $match, $response);

            $output = ['response' => $response->message, 'status' => 'success'];

            // Append follow-up to response if present
            if ($response->followUp !== null) {
                $output['response'] .= "\n\n" . $response->followUp . " (Reply yes or no)";
            }

            echo json_encode($output);
        } catch (Throwable $e) {
            error_log('JARVIS Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'error'  => 'System malfunction. Please try again.',
                'status' => 'error',
            ]);
        }
        break;

    // ── SET TITLE ────────────────────────────────────────────────────────
    case 'set_title':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $input     = json_decode(file_get_contents('php://input'), true);
        $sessionId = $input['session_id'] ?? 'default_' . time();
        $choice    = strtolower($input['title'] ?? '');

        if (!validateSessionId($sessionId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid session ID']);
            exit;
        }

        $title = (str_contains($choice, 'ma') || str_contains($choice, 'miss'))
            ? "Ma'am"
            : "Sir";

        $sessionManager->update($sessionId, ['title' => $title]);

        echo json_encode([
            'response' => "Acknowledged, {$title}. JARVIS is ready to assist with your SFAC inquiries.",
            'status'   => 'success',
        ]);
        break;

    // ── HEALTH ───────────────────────────────────────────────────────────
    case 'health':
        echo json_encode([
            'status'          => 'healthy',
            'timestamp'       => time(),
            'active_sessions' => $sessionManager->count(),
            'version'         => '1.0.0',
        ]);
        break;

    // ── STATS ────────────────────────────────────────────────────────────
    case 'stats':
        $date    = $_GET['date'] ?? date('Y-m-d');
        $summary = $logger->summarize($date);
        echo json_encode($summary);
        break;

    // ── 404 ──────────────────────────────────────────────────────────────
    default:
        http_response_code(404);
        echo json_encode([
            'error'    => 'Endpoint not found',
            'endpoints' => [
                'POST ?action=chat',
                'POST ?action=set_title',
                'GET  ?action=health',
                'GET  ?action=stats',
            ],
        ]);
        break;
}
