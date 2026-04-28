<?php

$passed = 0;
$failed = 0;

function test(string $description, mixed $assertion): void {
    global $passed, $failed;
    try {
        $result = is_callable($assertion) ? $assertion() : $assertion;
        if ($result === true) {
            echo "    \e[32m✓\e[0m {$description}\n";
            $passed++;
        } else {
            echo "    \e[31m✗\e[0m {$description}\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "    \e[31m✗\e[0m {$description}\n";
        echo "      Exception: " . $e->getMessage() . "\n";
        $failed++;
    }
}

function suite(string $name, callable $fn): void
{
    echo "  \e[33m{$name}\e[0m\n";
    $fn();
    echo "\n";
}

function group(string $name, callable $fn): void
{
    echo "\n\e[1m{$name}\e[0m\n" . str_repeat('─', 40) . "\n";
    $fn();
}

// ── Load and run test files ───────────────────────────
require_once __DIR__ . '/PreprocessorTest.php';
require_once __DIR__ . '/PatternMatcherTest.php';
require_once __DIR__ . '/KeywordScorerTest.php';
require_once __DIR__ . '/ResponseBuilderTest.php';
require_once __DIR__ . '/ConversationTest.php';

group('Preprocessor',    fn() => preprocessorTests());
group('PatternMatcher',  fn() => patternMatcherTests());
group('KeywordScorer',   fn() => keywordScorerTests());
group('ResponseBuilder', fn() => responseBuilderTests());
group('Conversation', fn() => conversationTests());

// ── Summary ───────────────────────────────────────────
global $passed, $failed;
$total = $passed + $failed;
echo str_repeat('─', 40) . "\n";
echo "\e[1mResults: {$total} tests\e[0m — ";
echo "\e[32m{$passed} passed\e[0m";
if ($failed > 0) {
    echo ", \e[31m{$failed} failed\e[0m";
}
echo "\n";
if ($failed > 0) exit(1);
