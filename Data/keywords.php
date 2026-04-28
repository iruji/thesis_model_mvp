<?php

/**
 * Keyword configuration
 * Reserved for runtime keyword tuning without touching KeywordScorer.php
 * Loaded by KeywordScorer in v2
 */

return [
    'version' => '1.0',
    'updated' => '2024-01-01',

    // Intent aliases — alternate ways to say the same thing
    // These will be merged into KeywordScorer keyword lists in v2
    'aliases' => [
        'admission' => [
            'how to get in',
            'application process',
            'entry requirements',
        ],
        'tuition' => [
            'school fees',
            'cost of education',
            'how much to study',
        ],
        'location' => [
            'how to go there',
            'directions to sfac',
            'sfac address',
        ],
    ],
];
