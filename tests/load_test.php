<?php

/**
 * JARVIS Load Test
 * Tests how the chatbot pipeline handles high volume requests
 * 
 * Usage: php tests/load_test.php
 * Usage with options: php tests/load_test.php --requests=200 --report=full
 */

require_once __DIR__ . '/../Core/DTO/ProcessedInput.php';
require_once __DIR__ . '/../Core/DTO/IntentMatch.php';
require_once __DIR__ . '/../Core/DTO/ChatResponse.php';
require_once __DIR__ . '/../Core/Preprocessor.php';
require_once __DIR__ . '/../Core/PatternMatcher.php';
require_once __DIR__ . '/../Core/KeywordScorer.php';
require_once __DIR__ . '/../Core/ResponseBuilder.php';

// ── Config ────────────────────────────────────────────────────────────────────
$options      = getopt('', ['requests::', 'report::']);
$totalRequests= (int) ($options['requests'] ?? 100);
$reportMode   = $options['report'] ?? 'summary'; // summary | full

// ── Test inputs — representative sample of real user queries ──────────────────
$inputs = [
    // English
    'how do i enroll'                          => 'admission',
    'what are the requirements'                => 'admission',
    'how much is the tuition fee'              => 'tuition',
    'do you offer scholarships'                => 'tuition',
    'where is the school located'              => 'location',
    'what is your address'                     => 'location',
    'how can i contact you'                    => 'contact',
    'what courses do you offer'                => 'course',
    'tell me about computer science'           => 'computer_science',
    'tell me about business administration'    => 'business_admin',
    'what are the senior high tracks'          => 'senior_high',
    'tell me about stem strand'                => 'stem',
    'tell me about abm strand'                 => 'abm',
    'what is junior high school'               => 'junior_high',
    'tell me about grade school'               => 'grade_school',
    'hello'                                    => 'greeting',
    'thank you'                                => 'thanks',
    'who are you'                              => 'identity',
    // Filipino
    'magkano ang tuition'                      => 'tuition',
    'saan ang campus'                          => 'location',
    'paano mag-enroll'                         => 'admission',
    'ano ang mga kurso'                        => 'course',
    // Taglish
    'pwede ba mag-enroll for cs'               => 'admission',
    'magkano ang tuition sa bsba'              => 'tuition',
    // Multi-intent
    'how do i enroll and how much is tuition'  => null, // multi-intent
    'tell me about cs and hospitality'         => null, // multi-intent
    // Unknown
    'what is the weather today'                => 'unknown',
    'tell me a joke'                           => 'unknown',
];

// ── Bootstrap ─────────────────────────────────────────────────────────────────
$preprocessor    = new Preprocessor();
$patternMatcher  = new PatternMatcher();
$keywordScorer   = new KeywordScorer();
$templates       = require __DIR__ . '/../Data/responses.php';
$responseBuilder = new ResponseBuilder($templates);
$session         = ['title' => 'Sir', 'name' => null, 'awaiting_followup' => null];

// ── Run load test ─────────────────────────────────────────────────────────────
echo "\n\e[1mJARVIS Load Test\e[0m\n";
echo str_repeat('─', 50) . "\n";
echo "Requests:  {$totalRequests}\n";
echo "Inputs:    " . count($inputs) . " unique queries (cycled)\n";
echo "Mode:      Pipeline only (no HTTP overhead)\n";
echo str_repeat('─', 50) . "\n\n";

$times          = [];
$intentResults  = [];
$sourceResults  = [];
$errors         = [];
$slowThreshold  = 50; // ms — anything above this is flagged
$inputKeys      = array_keys($inputs);
$inputCount     = count($inputKeys);

$startTotal = microtime(true);

for ($i = 0; $i < $totalRequests; $i++) {
    // Cycle through inputs
    $input = $inputKeys[$i % $inputCount];

    $start = microtime(true);

    try {
        $processed = $preprocessor->process($input);
        $match     = $patternMatcher->match($processed)
                  ?? $keywordScorer->score($processed);
        $response  = $responseBuilder->build($match, $session);

        $elapsed = (microtime(true) - $start) * 1000;
        $times[] = $elapsed;

        // Track intent distribution
        $intent = $match->intent;
        $intentResults[$intent] = ($intentResults[$intent] ?? 0) + 1;

        // Track source distribution
        $source = $match->source;
        $sourceResults[$source] = ($sourceResults[$source] ?? 0) + 1;

        // Flag slow requests
        if ($elapsed > $slowThreshold) {
            $errors[] = ['input' => $input, 'time' => $elapsed, 'type' => 'slow'];
        }

        // Progress indicator every 10 requests
        if (($i + 1) % 10 === 0) {
            $pct = round(($i + 1) / $totalRequests * 100);
            echo "\r  Progress: {$pct}% (" . ($i + 1) . "/{$totalRequests})";
        }

    } catch (Throwable $e) {
        $elapsed = (microtime(true) - $start) * 1000;
        $times[] = $elapsed;
        $errors[] = ['input' => $input, 'time' => $elapsed, 'type' => 'error', 'message' => $e->getMessage()];
    }
}

$totalTime = (microtime(true) - $startTotal) * 1000;

echo "\r  Progress: 100% ({$totalRequests}/{$totalRequests})\n\n";

// ── Calculate stats ───────────────────────────────────────────────────────────
sort($times);
$count     = count($times);
$avg       = array_sum($times) / $count;
$min       = min($times);
$max       = max($times);
$median    = $times[(int)($count / 2)];
$p95       = $times[(int)($count * 0.95)];
$p99       = $times[(int)($count * 0.99)];
$underMs   = function($ms) use ($times) {
    return count(array_filter($times, fn($t) => $t < $ms));
};

$errorCount = count(array_filter($errors, fn($e) => $e['type'] === 'error'));
$slowCount  = count(array_filter($errors, fn($e) => $e['type'] === 'slow'));
$throughput = round($totalRequests / ($totalTime / 1000), 1);

// ── Print results ─────────────────────────────────────────────────────────────
echo "\e[1mPerformance Results\e[0m\n";
echo str_repeat('─', 50) . "\n";
printf("  Total time:       %.2f ms (%.2f seconds)\n", $totalTime, $totalTime/1000);
printf("  Throughput:       %s requests/second\n", $throughput);
printf("  Average:          %.2f ms\n", $avg);
printf("  Median (p50):     %.2f ms\n", $median);
printf("  p95:              %.2f ms\n", $p95);
printf("  p99:              %.2f ms\n", $p99);
printf("  Min:              %.2f ms\n", $min);
printf("  Max:              %.2f ms\n", $max);
echo "\n";

// ── Latency distribution ──────────────────────────────────────────────────────
echo "\e[1mLatency Distribution\e[0m\n";
echo str_repeat('─', 50) . "\n";
$buckets = [1, 5, 10, 25, 50, 100, 200];
foreach ($buckets as $ms) {
    $count_under = $underMs($ms);
    $pct = round($count_under / $totalRequests * 100, 1);
    $bar = str_repeat('█', (int)($pct / 2));
    printf("  < %3dms: %5.1f%% %s\n", $ms, $pct, $bar);
}
echo "\n";

// ── Source distribution ───────────────────────────────────────────────────────
echo "\e[1mSource Distribution\e[0m\n";
echo str_repeat('─', 50) . "\n";
arsort($sourceResults);
foreach ($sourceResults as $source => $count_s) {
    $pct = round($count_s / $totalRequests * 100, 1);
    printf("  %-12s %4d requests (%5.1f%%)\n", $source, $count_s, $pct);
}
echo "\n";

// ── Intent distribution (full report only) ────────────────────────────────────
if ($reportMode === 'full') {
    echo "\e[1mIntent Distribution\e[0m\n";
    echo str_repeat('─', 50) . "\n";
    arsort($intentResults);
    foreach ($intentResults as $intent => $count_i) {
        $pct = round($count_i / $totalRequests * 100, 1);
        printf("  %-30s %4d (%5.1f%%)\n", $intent, $count_i, $pct);
    }
    echo "\n";
}

// ── Errors and slow requests ──────────────────────────────────────────────────
echo "\e[1mReliability\e[0m\n";
echo str_repeat('─', 50) . "\n";
printf("  Errors:           %d / %d\n", $errorCount, $totalRequests);
printf("  Slow (>%dms):     %d / %d\n", $slowThreshold, $slowCount, $totalRequests);
$successRate = round(($totalRequests - $errorCount) / $totalRequests * 100, 2);
printf("  Success rate:     %.2f%%\n", $successRate);
echo "\n";

if (!empty($errors) && $reportMode === 'full') {
    echo "\e[1mSlow / Error Details\e[0m\n";
    echo str_repeat('─', 50) . "\n";
    foreach ($errors as $err) {
        if ($err['type'] === 'error') {
            printf("  \e[31mERROR\e[0m  [%.2fms] '%s'\n", $err['time'], $err['input']);
            printf("         %s\n", $err['message']);
        } else {
            printf("  \e[33mSLOW\e[0m   [%.2fms] '%s'\n", $err['time'], $err['input']);
        }
    }
    echo "\n";
}

// ── Verdict ───────────────────────────────────────────────────────────────────
echo "\e[1mVerdict\e[0m\n";
echo str_repeat('─', 50) . "\n";

$verdict = [];
if ($avg < 5)   $verdict[] = ["\e[32m✓\e[0m", "Excellent average response time (<5ms)"];
elseif ($avg < 20) $verdict[] = ["\e[32m✓\e[0m", "Good average response time (<20ms)"];
elseif ($avg < 50) $verdict[] = ["\e[33m⚠\e[0m", "Acceptable average response time (<50ms)"];
else               $verdict[] = ["\e[31m✗\e[0m", "Slow average response time (>50ms) — investigate"];

if ($p95 < 50)  $verdict[] = ["\e[32m✓\e[0m", "95% of requests under 50ms"];
elseif ($p95 < 100) $verdict[] = ["\e[33m⚠\e[0m", "95% of requests under 100ms"];
else               $verdict[] = ["\e[31m✗\e[0m", "p95 above 100ms — system under stress"];

if ($errorCount === 0) $verdict[] = ["\e[32m✓\e[0m", "Zero errors — pipeline is stable"];
else                   $verdict[] = ["\e[31m✗\e[0m", "{$errorCount} errors — investigate immediately"];

if ($throughput > 100) $verdict[] = ["\e[32m✓\e[0m", "Throughput above 100 req/s — handles concurrent load well"];
elseif ($throughput > 50) $verdict[] = ["\e[33m⚠\e[0m", "Throughput {$throughput} req/s — adequate for thesis demo"];
else                   $verdict[] = ["\e[31m✗\e[0m", "Low throughput — consider caching or optimization"];

foreach ($verdict as [$icon, $msg]) {
    echo "  {$icon} {$msg}\n";
}

echo "\n" . str_repeat('─', 50) . "\n";
echo "Run with --report=full for intent breakdown and slow request details\n";
echo "Run with --requests=500 to increase load\n\n";
