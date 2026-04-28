<?php

require_once __DIR__ . '/../Core/DTO/IntentMatch.php';
require_once __DIR__ . '/../Core/DTO/ChatResponse.php';
require_once __DIR__ . '/../Core/ResponseBuilder.php';

function responseBuilderTests(): void
{
    $templates = require __DIR__ . '/../Data/responses.php';
    $builder   = new ResponseBuilder($templates);

    $session = ['name' => 'Juan', 'title' => 'Sir'];

    suite('Slot Filling', function () use ($builder, $session, $templates) {
        $match = new IntentMatch('greeting', 1.0, 'pattern');
        $r     = $builder->build($match, $session);

        test(
            'fills {name} slot correctly',
            str_contains($r->message, 'Juan')
        );

        test(
            'does not leave unfilled slots',
            !str_contains($r->message, '{name}') &&
                !str_contains($r->message, '{title}') &&
                !str_contains($r->message, '{greeting}')
        );
    });

    suite('Intent Routing', function () use ($builder, $session) {
        $intents = [
            'admission',
            'tuition',
            'location',
            'contact',
            'course',
            'greeting',
            'thanks',
            'identity',
            'senior_high',
            'junior_high',
            'grade_school',
            'preschool',
            'stem',
            'abm',
            'humss',
            'ga',
            'he',
            'computer_science',
            'business_admin',
            'tourism',
            'hospitality',
            'physical_education',
            'facilities',
            'schedule',
        ];

        foreach ($intents as $intent) {
            $match = new IntentMatch($intent, 0.9, 'keyword');
            $r     = $builder->build($match, $session);
            test(
                "builds response for intent: {$intent}",
                !empty($r->message) && $r->intent === $intent
            );
        }
    });

    suite('Grade Level Entity Override', function () use ($builder, $session) {
        $match = new IntentMatch('grade_level', 1.0, 'pattern', 'senior_high');
        $r     = $builder->build($match, $session);
        test(
            'grade_level uses entity as intent',
            $r->intent === 'senior_high'
        );
    });

    suite('Unknown Intent Fallback', function () use ($builder, $session) {
        $match = new IntentMatch('unknown', 0.0, 'fallback');
        $r     = $builder->build($match, $session);
        test(
            'unknown intent returns fallback message',
            !empty($r->message)
        );
        test(
            'unknown message contains name',
            str_contains($r->message, 'Juan')
        );
    });

    suite('Follow Up', function () use ($builder, $session) {
        $match = new IntentMatch('admission', 1.0, 'pattern');
        $r     = $builder->build($match, $session);
        test(
            'admission has a follow-up suggestion',
            $r->followUp !== null
        );

        $match = new IntentMatch('greeting', 1.0, 'pattern');
        $r     = $builder->build($match, $session);
        test(
            'greeting has no follow-up suggestion',
            $r->followUp === null
        );
    });

    suite('Source Tracking', function () use ($builder, $session) {
        $match = new IntentMatch('tuition', 0.95, 'pattern');
        $r     = $builder->build($match, $session);
        test(
            'preserves source from match',
            $r->source === 'pattern'
        );

        $match = new IntentMatch('location', 0.7, 'keyword');
        $r     = $builder->build($match, $session);
        test(
            'preserves keyword source',
            $r->source === 'keyword'
        );
    });
}
