<?php

require_once __DIR__ . '/DTO/ProcessedInput.php';

class Preprocessor
{

    private array $contractions = [
        "i'm"      => "i am",
        "i've"     => "i have",
        "i'll"     => "i will",
        "i'd"      => "i would",
        "what's"   => "what is",
        "where's"  => "where is",
        "how's"    => "how is",
        "there's"  => "there is",
        "it's"     => "it is",
        "don't"    => "do not",
        "doesn't"  => "does not",
        "can't"    => "cannot",
        "won't"    => "will not",
        "isn't"    => "is not",
        "aren't"   => "are not",
        "wasn't"   => "was not",
        "weren't"  => "were not",
        "haven't"  => "have not",
        "hasn't"   => "has not",
        "they're"  => "they are",
        "you're"   => "you are",
        "we're"    => "we are",
        "that's"   => "that is",
    ];

    private array $abbreviations = [
        ' cs '     => ' computer science ',
        ' bscs '   => ' computer science ',
        ' bsba '   => ' business administration ',
        ' shs '    => ' senior high school ',
        ' jhs '    => ' junior high school ',
        ' k12 '    => ' senior high school ',
        ' k-12 '   => ' senior high school ',
        ' pe '     => ' physical education ',
        ' bped '   => ' physical education ',
        ' ece '    => ' early childhood education ',
        ' beced '  => ' early childhood education ',
        ' beed '   => ' elementary education ',
        ' bsed '   => ' secondary education ',
        ' bstm '   => ' tourism management ',
        ' bshm '   => ' hospitality management ',
        ' abm '    => ' accountancy business management ',
        ' humss '  => ' humanities social sciences ',
        ' stem '   => ' science technology engineering mathematics ',
        ' ga '     => ' general academics ',
        ' he '     => ' home economics ',
        ' opac '   => ' library catalog ',
        ' gcs '    => ' grade computation system ',
        ' sfac '   => ' saint francis of assisi college ',
        ' hm '     => ' hospitality management ',
        ' tm '     => ' tourism management ',
        ' act '    => ' computer technology ',
    ];

    private array $stopWords = [
        'a',
        'an',
        'the',
        'is',
        'are',
        'was',
        'were',
        'do',
        'does',
        'did',
        'be',
        'been',
        'being',
        'in',
        'on',
        'at',
        'to',
        'for',
        'of',
        'with',
        'and',
        'or',
        'but',
        'so',
        'if',
        'as',
        'this',
        'that',
        'these',
        'those',
        'it',
        'me',
        'my',
        'you',
        'your',
        'we',
        'our',
        'please',
        'just',
        'like',
        'really',
        'very',
        'i',
        'he',
        'she',
        'they',
        'we',
        'us',
        'what',
        'where',
        'when',
        'which',
        'who',
        'how',
    ];

    private array $filipinoMarkers = [
        'ano',
        'saan',
        'paano',
        'magkano',
        'pwede',
        'puwede',
        'po',
        'opo',
        'nga',
        'ba',
        'yung',
        'ng',
        'sa',
        'mga',
        'naman',
        'kaya',
        'gusto',
        'tanong',
        'tungkol',
        'ito',
        'iyon',
        'dito',
        'doon',
        'para',
        'hindi',
        'oo',
        'wala',
        'meron',
        'may',
        'ako',
        'siya',
        'nila',
        'namin',
        'natin',
        'niya',
        'taga',
        'kung',
        'ang',
        'yung',
        'daw',
        'raw',
        'lang',
        'din',
        'rin',
        'pala',
        'kasi',
        'eh',
        'ah',
        'ay',
        'na',
        'pa',
    ];
    private array $taglish;

    public function __construct()
    {
        $this->taglish = require __DIR__ . '/../Data/taglish.php';
    }

    public function process(string $raw): ProcessedInput
    {
        $normalized = $this->normalize($raw);
        $language   = $this->detectLanguage($normalized);
        $normalized = $this->expandContractions($normalized);
        $normalized = $this->expandAbbreviations($normalized);
        $normalized = $this->normalizeTaglish($normalized);
        $tokens     = $this->tokenize($normalized);
        $tokens     = $this->removeStopWords($tokens);

        return new ProcessedInput(
            original: $raw,
            normalized: $normalized,
            tokens: $tokens,
            language: $language,
        );
    }

    private function normalize(string $text): string
    {
        $text = strtolower(trim($text));
        // Strip ALL punctuation so abbreviations like SHS? expand correctly
        $text = preg_replace('/[^\w\s\']/', ' ', $text);
        // Collapse multiple spaces into one
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private function normalizeTaglish(string $text): string
    {
        // Strip politeness markers first — they add noise
        foreach ($this->taglish['politeness_markers'] as $marker) {
            $text = str_replace($marker, ' ', $text);
        }

        // Expand all taglish term groups in order
        $groups = [
            'expressions',      // multi-word phrases first
            'school_terms',     // domain terms second
            'financial_terms',
            'location_terms',
            'affirmative',
            'negative',
            'question_words',   // single words last — avoids breaking multi-word matches
        ];

        foreach ($groups as $group) {
            foreach ($this->taglish[$group] as $tagalog => $english) {
                $pattern = '/\b' . preg_quote($tagalog, '/') . '\b/i';
                if ($english === '') {
                    // For words we want to strip, replace with a space
                    // then collapse spaces at the end
                    $text = preg_replace($pattern, ' ', $text);
                } else {
                    $text = preg_replace($pattern, $english, $text);
                }
            }
        }

        // Collapse any extra spaces from stripping
        $text = trim(preg_replace('/\s+/', ' ', $text));

        return $text;
    }

    private function expandContractions(string $text): string
    {
        return str_replace(
            array_keys($this->contractions),
            array_values($this->contractions),
            $text
        );
    }

    private function expandAbbreviations(string $text): string
    {
        // Wrap in spaces so abbreviations at start/end of string are caught
        $text = ' ' . $text . ' ';
        $text = str_replace(
            array_keys($this->abbreviations),
            array_values($this->abbreviations),
            $text
        );
        // Collapse any double spaces introduced by expansion
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function tokenize(string $text): array
    {
        return array_values(array_filter(
            explode(' ', $text),
            fn($t) => strlen($t) > 1  // drop single characters
        ));
    }

    private function removeStopWords(array $tokens): array
    {
        return array_values(
            array_filter($tokens, fn($t) => !in_array($t, $this->stopWords))
        );
    }

    private function detectLanguage(string $text): string
    {
        $hits = 0;
        foreach ($this->filipinoMarkers as $marker) {
            if (preg_match('/\b' . preg_quote($marker, '/') . '\b/', $text)) {
                $hits++;
            }
        }
        if ($hits === 0) return 'en';
        if ($hits === 1)  return 'mixed';
        return 'tl';
    }
}
