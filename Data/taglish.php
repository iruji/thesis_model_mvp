<?php

/**
 * Taglish / Filipino keyword expansions
 * These are loaded by the Preprocessor to normalize
 * Filipino and mixed-language inputs before intent detection
 */

return [

    // ── Question words ────────────────────────────────────────────────────
    'question_words' => [
        'ano'       => 'what',
        'anong'     => 'what',
        'saan'      => 'where',
        'nasaan'    => 'where is',
        'paano'     => 'how do i',      // changed from 'how'
        'pano'      => 'how do i',      // changed from 'how do i'
        'kailan'    => 'when',
        'sino'      => 'who',
        'magkano'   => 'how much',
        'ilan'      => 'how many',
        'bakit'     => 'why',
        'ang'       => '',              // linker — strip it
        'ng'        => '',              // linker — strip it
        'nyo'       => '',              // your (possessive) — strip it
        'nila'      => '',              // their — strip it
        'namin'     => '',              // our — strip it
        'yung'      => 'the',          // informal "the"
    ],

    // ── Common expressions ────────────────────────────────────────────────
    'expressions' => [
        'pwede ba'          => 'can i',
        'puwede ba'         => 'can i',
        'pwede bang'        => 'can i',
        'gusto ko'          => 'i want',
        'gusto ko pong'     => 'i would like to',
        'nais ko'           => 'i want',
        'tanong ko lang'    => 'i just want to ask',
        'tanong ko po'      => 'i would like to ask',
        'paki-explain'      => 'please explain',
        'pakisabi'          => 'please tell me',
        'sabihin mo'        => 'tell me',
        'ano ba'            => 'what is',
        'ano po'            => 'what is',
        'sana malaman'      => 'i would like to know',
        'gusto malaman'     => 'i want to know',
        'may tanong ako'    => 'i have a question',
        'maari bang'        => 'is it possible to',
        'pano ba'           => 'how do i',
        'paano ba'          => 'how do i',
    ],

    // ── School / enrollment terms ─────────────────────────────────────────
    'school_terms' => [
        'mag-enroll'        => 'enroll',
        'mag enroll'        => 'enroll',
        'makapag-enroll'    => 'enroll',
        'makapag enroll'    => 'enroll',
        'mag-apply'         => 'apply',
        'mag apply'         => 'apply',
        'pumasok'           => 'enroll',
        'makapasok'         => 'get admitted',
        'pasukan'           => 'school',
        'paaralan'          => 'school',
        'pamantasan'        => 'college',
        'kolehiyo'          => 'college',
        'kurso'             => 'course',
        'programa'          => 'program',
        'strand'            => 'strand',
        'track'             => 'track',
        'aralin'            => 'subject',
        'asignatura'        => 'subject',
        'grado'             => 'grade',
        'baitang'           => 'grade',
        'klase'             => 'class',
        'iskwela'           => 'school',
    ],

    // ── Payment / financial terms ─────────────────────────────────────────
    'financial_terms' => [
        'bayad'             => 'payment',
        'bayaran'           => 'fee',
        'halaga'            => 'cost',
        'presyo'            => 'price',
        'libre'             => 'free',
        'scholarship'       => 'scholarship',
        'iskolarship'       => 'scholarship',
        'iskolar'           => 'scholar',
        'tulong-pinansyal'  => 'financial aid',
        'tulong pinansyal'  => 'financial aid',
        'diskwento'         => 'discount',
        'mahal ba'          => 'is it expensive',
        'mura ba'           => 'is it affordable',
    ],

    // ── Location terms ────────────────────────────────────────────────────
    'location_terms' => [
        'lugar'             => 'location',
        'address'           => 'address',
        'direksyon'         => 'directions',
        'malapit'           => 'near',
        'malayo'            => 'far',
        'papunta'           => 'going to',
        'makarating'        => 'get there',
        'campus'            => 'campus',
    ],

    // ── Affirmative / negative ─────────────────────────────────────────────
    'affirmative' => [
        'oo'        => 'yes',
        'opo'       => 'yes',
        'sige'      => 'okay',
        'sigi'      => 'okay',
        'ayos'      => 'okay',
        'tama'      => 'correct',
        'syempre'   => 'of course',
        'naman'     => '',
    ],

    'negative' => [
        'hindi'     => 'no',
        'hinde'     => 'no',
        'ayaw'      => 'no',
        'ayoko'     => 'i do not want',
        'wag'       => 'no',
        'huwag'     => 'no',
    ],

    // ── Politeness markers (strip these — they add no intent signal) ───────
    'politeness_markers' => [
        ' po ',
        ' po.',
        ' po,',
        ' po?',
        ' po',
        ' opo ',
        ' opo.',
        ' opo,',
        ' opo?',
        ' opo',
        ' nga ',
        ' nga.',
        ' nga,',
        ' naman ',
        ' naman.',
        ' lang ',
        ' lang.',
        ' lang,',
        ' daw ',
        ' raw ',
        ' kasi ',
    ],
];
