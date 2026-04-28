<?php

require_once __DIR__ . '/DTO/ProcessedInput.php';
require_once __DIR__ . '/DTO/IntentMatch.php';
require_once __DIR__ . '/DTO/ChatResponse.php';

class AnalyticsLogger
{

    private string $logDir;

    public function __construct(string $logDir = null)
    {
        $this->logDir = $logDir ?? __DIR__ . '/../logs';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    // ── Public API ────────────────────────────────────────────────────────

    public function log(
        string         $sessionId,
        ProcessedInput $input,
        IntentMatch    $match,
        ChatResponse   $response
    ): void {
        $record = [
            'ts'         => date('c'),
            'session_id' => $sessionId,
            'raw'        => $input->original,
            'normalized' => $input->normalized,
            'tokens'     => $input->tokens,
            'language'   => $input->language,
            'intent'     => $response->intent,
            'confidence' => $response->confidence,
            'source'     => $response->source,
            'unknown'    => $match->isUnknown(),
            'follow_up'  => $response->followUp !== null,
        ];

        $file = $this->logDir . '/chat_' . date('Y-m-d') . '.jsonl';
        file_put_contents($file, json_encode($record) . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * Summarize a day's logs — run this weekly to tune the system
     * Returns unknown inputs so you know exactly what patterns to add
     */
    public function summarize(string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $file = $this->logDir . '/chat_' . $date . '.jsonl';

        if (!file_exists($file)) {
            return ['error' => 'No log file found for ' . $date];
        }

        $lines   = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $records = array_map('json_decode', $lines);
        $total   = count($records);

        if ($total === 0) {
            return ['error' => 'No records found for ' . $date];
        }

        // Separate unknowns
        $unknowns = array_filter($records, fn($r) => $r->unknown === true);

        // Count by source
        $sources = [];
        foreach ($records as $r) {
            $sources[$r->source] = ($sources[$r->source] ?? 0) + 1;
        }

        // Count by intent
        $intents = [];
        foreach ($records as $r) {
            $intents[$r->intent] = ($intents[$r->intent] ?? 0) + 1;
        }
        arsort($intents);

        // Count by language
        $languages = [];
        foreach ($records as $r) {
            $languages[$r->language] = ($languages[$r->language] ?? 0) + 1;
        }

        // Average confidence
        $avgConfidence = round(
            array_sum(array_column($records, 'confidence')) / $total,
            3
        );

        return [
            'date'             => $date,
            'total'            => $total,
            'unknown_count'    => count($unknowns),
            'unknown_rate'     => round(count($unknowns) / $total * 100, 1) . '%',
            'avg_confidence'   => $avgConfidence,
            'by_source'        => $sources,
            'by_intent'        => $intents,
            'by_language'      => $languages,
            'unknown_inputs'   => array_values(
                array_map(fn($r) => $r->raw, $unknowns)
            ),
        ];
    }
}
