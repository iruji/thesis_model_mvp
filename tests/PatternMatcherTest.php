<?php

require_once __DIR__ . '/../Core/Preprocessor.php';
require_once __DIR__ . '/../Core/PatternMatcher.php';

function patternMatcherTests(): void
{
    $p = new Preprocessor();
    $m = new PatternMatcher();

    suite('Greeting & Identity', function () use ($p, $m) {
        $r = $m->match($p->process('hello'));
        test(
            'detects hello as greeting',
            $r?->intent === 'greeting' && $r->confidence === 1.0
        );

        $r = $m->match($p->process('who are you'));
        test(
            'detects identity',
            $r?->intent === 'identity'
        );

        $r = $m->match($p->process('who made you'));
        test(
            'detects jarvis creator',
            $r?->intent === 'jarvis_creator'
        );

        $r = $m->match($p->process('thank you'));
        test(
            'detects thanks',
            $r?->intent === 'thanks'
        );
    });

    suite('Admission', function () use ($p, $m) {
        $r = $m->match($p->process('how do i enroll'));
        test(
            'detects enrollment query (english)',
            $r?->intent === 'admission'
        );

        $r = $m->match($p->process('paano mag-enroll'));
        test(
            'detects enrollment query (filipino)',
            $r?->intent === 'admission'
        );

        $r = $m->match($p->process('what are the requirements'));
        test(
            'detects requirements query',
            $r?->intent === 'admission'
        );
    });

    suite('Tuition', function () use ($p, $m) {
        $r = $m->match($p->process('how much is the tuition fee'));
        test(
            'detects tuition query (english)',
            $r?->intent === 'tuition'
        );

        $r = $m->match($p->process('magkano ang tuition'));
        test(
            'detects tuition query (filipino) after taglish normalization',
            $r?->intent === 'tuition'
        );

        $r = $m->match($p->process('do you offer scholarships'));
        test(
            'detects scholarship query',
            $r?->intent === 'tuition'
        );
    });

    suite('Location & Contact', function () use ($p, $m) {
        $r = $m->match($p->process('where is the school'));
        test(
            'detects location query',
            $r?->intent === 'location'
        );

        $r = $m->match($p->process('what is your address'));
        test(
            'detects address query',
            $r?->intent === 'location'
        );

        $r = $m->match($p->process('how can i contact you'));
        test(
            'detects contact query',
            $r?->intent === 'contact'
        );
    });

    suite('Courses', function () use ($p, $m) {
        $r = $m->match($p->process('what courses do you offer'));
        test(
            'detects course list query',
            $r?->intent === 'course'
        );

        $r = $m->match($p->process('tell me about computer science'));
        test(
            'detects computer science query',
            $r?->intent === 'computer_science'
        );

        $r = $m->match($p->process('tell me about business administration'));
        test(
            'detects business admin query',
            $r?->intent === 'business_admin'
        );
    });

    suite('Grade Level Detection', function () use ($p, $m) {
        $r = $m->match($p->process('I am in grade 3'));
        test(
            'grade 3 maps to grade_school',
            $r?->entity === 'grade_school'
        );

        $r = $m->match($p->process('my child is in grade 8'));
        test(
            'grade 8 maps to junior_high',
            $r?->entity === 'junior_high'
        );

        $r = $m->match($p->process('I am in grade 11'));
        test(
            'grade 11 maps to senior_high',
            $r?->entity === 'senior_high'
        );
    });

    suite('No Match', function () use ($p, $m) {
        $r = $m->match($p->process('what is the weather today'));
        test(
            'returns null for off-topic input',
            $r === null
        );

        $r = $m->match($p->process('tell me a joke'));
        test(
            'returns null for unrelated input',
            $r === null
        );
    });
}
