<?php

require_once __DIR__ . '/../Core/Preprocessor.php';

function preprocessorTests(): void
{
    $p = new Preprocessor();

    suite('Normalize', function () use ($p) {
        $r = $p->process('  Hello World!  ');
        test(
            'trims whitespace',
            $r->normalized === 'hello world'
        );

        $r = $p->process('WHAT IS THE TUITION FEE???');
        test(
            'lowercases input',
            $r->normalized === 'what is the tuition fee'
        );
    });

    suite('Contraction Expansion', function () use ($p) {
        $r = $p->process("I'm asking about enrollment");
        test(
            "expands i'm to i am",
            str_contains($r->normalized, 'i am')
        );

        $r = $p->process("What's the tuition fee?");
        test(
            "expands what's to what is",
            str_contains($r->normalized, 'what is')
        );

        $r = $p->process("I don't know the requirements");
        test(
            "expands don't to do not",
            str_contains($r->normalized, 'do not')
        );
    });

    suite('Abbreviation Expansion', function () use ($p) {
        $r = $p->process('I want to take CS');
        test(
            'expands CS to computer science',
            str_contains($r->normalized, 'computer science')
        );

        $r = $p->process('What about SHS?');
        test(
            'expands SHS to senior high school',
            str_contains($r->normalized, 'senior high school')
        );

        $r = $p->process('Tell me about BSBA');
        test(
            'expands BSBA to business administration',
            str_contains($r->normalized, 'business administration')
        );

        $r = $p->process('Info about ABM strand');
        test(
            'expands ABM to accountancy business management',
            str_contains($r->normalized, 'accountancy business management')
        );
    });

    suite('Tokenization', function () use ($p) {
        $r = $p->process('what is the address of the school');
        test(
            'removes stop words from tokens',
            !in_array('the', $r->tokens) && !in_array('of', $r->tokens)
        );

        test(
            'keeps meaningful words',
            in_array('address', $r->tokens) && in_array('school', $r->tokens)
        );
    });

    suite('Language Detection', function() use ($p) {
        $r = $p->process('hello what courses do you offer');
        test('detects English', function() use ($r) {
            return $r->language === 'en';
        });

        $r = $p->process('magkano ang tuition po');
        test('detects Filipino', function() use ($r) {
            return $r->language === 'tl';
        });

        $r = $p->process('i want to enroll ba');
        test('detects mixed', function() use ($r) {
            return $r->language === 'mixed';
        });
    });

    suite('Taglish Normalization', function () use ($p) {
        $r = $p->process('magkano ang tuition');
        test(
            'expands magkano to how much',
            str_contains($r->normalized, 'how much')
        );

        $r = $p->process('saan ang campus');
        test(
            'expands saan to where',
            str_contains($r->normalized, 'where')
        );

        $r = $p->process('paano mag enroll');
        test(
            'expands paano to how do i and mag enroll to enroll',
            str_contains($r->normalized, 'how do i') &&
                str_contains($r->normalized, 'enroll')
        );

        $r = $p->process('ano ang kurso nyo po');
        test(
            'strips po and expands kurso to course',
            str_contains($r->normalized, 'course') &&
                !str_contains($r->normalized, 'po')
        );

        $r = $p->process('pwede ba mag-apply');
        test(
            'expands pwede ba to can i',
            str_contains($r->normalized, 'can i')
        );

        $r = $p->process('magkano ang bayad sa kolehiyo');
        test(
            'expands multiple taglish terms in one sentence',
            str_contains($r->normalized, 'how much') &&
                str_contains($r->normalized, 'payment') &&
                str_contains($r->normalized, 'college')
        );
    });
}
