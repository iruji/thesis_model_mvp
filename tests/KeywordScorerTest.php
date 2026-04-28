<?php

require_once __DIR__ . '/../Core/Preprocessor.php';
require_once __DIR__ . '/../Core/KeywordScorer.php';

function keywordScorerTests(): void
{
    $p = new Preprocessor();
    $s = new KeywordScorer();

    suite('Basic Intent Detection', function () use ($p, $s) {
        $r = $s->score($p->process('I want to know about tuition'));
        test(
            'scores tuition intent',
            $r->intent === 'tuition' && !$r->isUnknown()
        );

        $r = $s->score($p->process('where is the campus located'));
        test(
            'scores location intent',
            $r->intent === 'location' && !$r->isUnknown()
        );

        $r = $s->score($p->process('what is the contact number'));
        test(
            'scores contact intent',
            $r->intent === 'contact' && !$r->isUnknown()
        );

        $r = $s->score($p->process('what programs are available'));
        test(
            'scores course intent',
            $r->intent === 'course' && !$r->isUnknown()
        );
    });

    suite('Education Levels', function () use ($p, $s) {
        $r = $s->score($p->process('tell me about elementary'));
        test(
            'scores grade_school intent',
            $r->intent === 'grade_school' && !$r->isUnknown()
        );

        $r = $s->score($p->process('information about senior high'));
        test(
            'scores senior_high intent',
            $r->intent === 'senior_high' && !$r->isUnknown()
        );

        $r = $s->score($p->process('what is junior high school'));
        test(
            'scores junior_high intent',
            $r->intent === 'junior_high' && !$r->isUnknown()
        );

        $r = $s->score($p->process('nursery and kindergarten'));
        test(
            'scores preschool intent',
            $r->intent === 'preschool' && !$r->isUnknown()
        );
    });

    suite('College Programs', function () use ($p, $s) {
        $r = $s->score($p->process('I want to take up programming'));
        test(
            'scores computer_science intent',
            $r->intent === 'computer_science' && !$r->isUnknown()
        );

        $r = $s->score($p->process('hotel management course'));
        test(
            'scores hospitality intent',
            $r->intent === 'hospitality' && !$r->isUnknown()
        );

        $r = $s->score($p->process('I like tourism and travel'));
        test(
            'scores tourism intent',
            $r->intent === 'tourism' && !$r->isUnknown()
        );
    });

    suite('Source Tracking', function () use ($p, $s) {
        $r = $s->score($p->process('tuition fee'));
        test(
            'source is keyword when matched',
            $r->source === 'keyword'
        );

        $r = $s->score($p->process('blah blah random nonsense xyz'));
        test(
            'source is fallback when unknown',
            $r->source === 'fallback'
        );
    });

    suite('Unknown Detection', function () use ($p, $s) {
        $r = $s->score($p->process('what is the weather today'));
        test(
            'returns unknown for off-topic',
            $r->isUnknown()
        );

        $r = $s->score($p->process('tell me a joke'));
        test(
            'returns unknown for unrelated',
            $r->isUnknown()
        );
    });
}
