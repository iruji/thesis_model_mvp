<?php

require_once __DIR__ . '/../Core/DTO/ProcessedInput.php';
require_once __DIR__ . '/../Core/DTO/IntentMatch.php';
require_once __DIR__ . '/../Core/DTO/ChatResponse.php';
require_once __DIR__ . '/../Core/Preprocessor.php';
require_once __DIR__ . '/../Core/PatternMatcher.php';
require_once __DIR__ . '/../Core/KeywordScorer.php';
require_once __DIR__ . '/../Core/ResponseBuilder.php';

class ConversationSimulator {
    private array   $session;
    private array   $templates;
    private Preprocessor    $preprocessor;
    private PatternMatcher  $patternMatcher;
    private KeywordScorer   $keywordScorer;
    private ResponseBuilder $responseBuilder;

    public function __construct(string $title = 'Sir') {
        $this->templates       = require __DIR__ . '/../Data/responses.php';
        $this->preprocessor    = new Preprocessor();
        $this->patternMatcher  = new PatternMatcher();
        $this->keywordScorer   = new KeywordScorer();
        $this->responseBuilder = new ResponseBuilder($this->templates);
        $this->session         = [
            'title'             => $title,
            'name'              => null,
            'awaiting_followup' => null,
        ];
    }

    public function send(string $message): ChatResponse {
        $processed = $this->preprocessor->process($message);
        $match     = $this->patternMatcher->match($processed)
                  ?? $this->keywordScorer->score($processed);
        $response  = $this->responseBuilder->build($match, $this->session);

        if ($response->followUp !== null) {
            $followUpMap = [
                'admission'        => 'tuition',
                'tuition'          => 'admission',
                'course'           => 'admission',
                'location'         => 'contact',
                'contact'          => 'location',
                'senior_high'      => 'course',
                'computer_science' => 'admission',
                'business_admin'   => 'admission',
                'facilities'       => 'website_opac',
            ];
            $this->session['awaiting_followup'] = $followUpMap[$match->intent] ?? null;
        } else {
            $this->session['awaiting_followup'] = null;
        }

        return $response;
    }

    public function getSession(): array {
        return $this->session;
    }
}

function conversationTests(): void {

    suite('Single Turn Conversations', function() {
        $sim = new ConversationSimulator();

        $r = $sim->send('hello');
        test('greeting returns correct intent', function() use ($r) {
            return $r->intent === 'greeting';
        });

        $r = $sim->send('how much is tuition');
        test('tuition query returns correct intent', function() use ($r) {
            return $r->intent === 'tuition';
        });

        $r = $sim->send('how much is tuition');
        test('tuition response is not empty', function() use ($r) {
            return !empty($r->message);
        });

        $r = $sim->send('how much is tuition');
        test('tuition response contains title', function() use ($r) {
            return str_contains($r->message, 'Sir');
        });
    });

    suite('Multi-Turn — Follow-Up Flow', function() {
        $sim = new ConversationSimulator();

        $r1 = $sim->send('how do i enroll');
        test('turn 1 detects admission', function() use ($r1) {
            return $r1->intent === 'admission';
        });

        test('turn 1 suggests follow-up', function() use ($r1) {
            return $r1->followUp !== null;
        });

        $session = $sim->getSession();
        test('follow-up state stored after admission', function() use ($session) {
            return $session['awaiting_followup'] === 'tuition';
        });
    });

    suite('Multi-Turn — Language Consistency', function() {
        $sim = new ConversationSimulator();

        $r1 = $sim->send('magkano ang tuition');
        test('filipino tuition query detected', function() use ($r1) {
            return $r1->intent === 'tuition';
        });

        $r2 = $sim->send('saan ang campus');
        test('filipino location query detected in same session', function() use ($r2) {
            return $r2->intent === 'location';
        });
    });

    suite('Multi-Turn — Title Persistence', function() {
        $sim = new ConversationSimulator("Ma'am");

        $r1 = $sim->send('what courses do you offer');
        test("title is Ma'am in first response", function() use ($r1) {
            return str_contains($r1->message, "Ma'am");
        });

        $r2 = $sim->send('how much is tuition');
        test("title persists to second turn", function() use ($r2) {
            return str_contains($r2->message, "Ma'am");
        });
    });

    suite('Pipeline Integrity', function() {
        $sim = new ConversationSimulator();

        $inputs = [
            'hello'                          => 'greeting',
            'how do i enroll'                => 'admission',
            'how much is tuition'            => 'tuition',
            'where is the school'            => 'location',
            'what courses do you offer'      => 'course',
            'tell me about computer science' => 'computer_science',
            'magkano ang tuition'            => 'tuition',
            'saan ang campus'                => 'location',
            'paano mag-enroll'               => 'admission',
            'what is the weather today'      => 'unknown',
        ];

        foreach ($inputs as $input => $expectedIntent) {
            $r = $sim->send($input);
            test("pipeline: '{$input}' → {$expectedIntent}", function() use ($r, $expectedIntent) {
                return $r->intent === $expectedIntent;
            });
        }
    });
}