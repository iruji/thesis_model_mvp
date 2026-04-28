<?php

require_once __DIR__ . '/DTO/ProcessedInput.php';
require_once __DIR__ . '/DTO/IntentMatch.php';

class KeywordScorer
{

    /**
     * Minimum confidence to return a result vs 'unknown'
     * Below this threshold we admit we don't know
     */
    private float $threshold = 0.40;

    /**
     * Multi-word keywords score higher than single words
     * because they are more specific and less ambiguous
     */
    private array $intentKeywords = [

        // ── ADMISSION ────────────────────────────────────────────────────
        'admission' => [
            'enroll',
            'apply',
            'application',
            'requirement',
            'requirements',
            'entrance',
            'qualify',
            'admission',
            'admissions',
            'how to enroll',
            'how to apply',
            'admission requirements',
            'enrollment requirements',
            'what do i need',
            'what are the requirements',
            'registration',
        ],

        // ── TUITION ──────────────────────────────────────────────────────
        'tuition' => [
            'tuition',
            'fee',
            'fees',
            'cost',
            'payment',
            'scholarship',
            'financial aid',
            'bayad',
            'magkano',
            'halaga',
            'tuition fee',
            'school fee',
            'how much',
            'payment scheme',
            'payment plan',
            'discount',
            'free',
            'subsidized',
            'financial assistance',
        ],

        // ── LOCATION ─────────────────────────────────────────────────────
        'location' => [
            'location',
            'address',
            'campus',
            'bacoor',
            'cavite',
            'bayanan',
            'directions',
            'saan',
            'how to get there',
            'where',
            'located',
            'las pinas',
            'find you',
            'maps',
            'google maps',
            'commute',
        ],

        // ── CONTACT ──────────────────────────────────────────────────────
        'contact' => [
            'contact',
            'phone',
            'email',
            'number',
            'reach',
            'call',
            'message',
            'contact number',
            'phone number',
            'email address',
            'contact information',
            'contact details',
            'get in touch',
            'office',
            'hotline',
            'telephone',
        ],

        // ── COURSES / PROGRAMS ───────────────────────────────────────────
        'course' => [
            'course',
            'courses',
            'program',
            'programs',
            'degree',
            'degrees',
            'major',
            'majors',
            'curriculum',
            'offered',
            'available',
            'what do you offer',
            'course offerings',
            'programs offered',
            'degrees offered',
            'available courses',
            'available programs',
        ],

        // ── SCHEDULE ─────────────────────────────────────────────────────
        'schedule' => [
            'schedule',
            'calendar',
            'semester',
            'class schedule',
            'when start',
            'academic calendar',
            'school year',
            'school calendar',
            'when does school start',
            'enrollment period',
            'enrollment schedule',
            'opening of classes',
            'start of classes',
            'kailan',
        ],

        // ── FACILITIES ───────────────────────────────────────────────────
        'facilities' => [
            'library',
            'laboratory',
            'gym',
            'gymnasium',
            'cafeteria',
            'facilities',
            'clinic',
            'building',
            'classrooms',
            'computer lab',
            'science lab',
            'sports',
            'amenities',
            'campus facilities',
        ],

        // ── SENIOR HIGH ──────────────────────────────────────────────────
        'senior_high' => [
            'senior high',
            'senior high school',
            'grade 11',
            'grade 12',
            'strand',
            'track',
            'shs',
            'k-12',
            'k12',
            'twelfth grade',
            'eleventh grade',
            'senior highschool',
        ],

        // ── JUNIOR HIGH ──────────────────────────────────────────────────
        'junior_high' => [
            'junior high',
            'junior high school',
            'grade 7',
            'grade 8',
            'grade 9',
            'grade 10',
            'jhs',
            'seventh grade',
            'eighth grade',
            'ninth grade',
            'tenth grade',
            'junior highschool',
        ],

        // ── GRADE SCHOOL ─────────────────────────────────────────────────
        'grade_school' => [
            'grade school',
            'elementary',
            'primary',
            'grade 1',
            'grade 2',
            'grade 3',
            'grade 4',
            'grade 5',
            'grade 6',
            'elementary school',
            'primary school',
            'primary education',
        ],

        // ── PRESCHOOL ────────────────────────────────────────────────────
        'preschool' => [
            'preschool',
            'pre school',
            'kindergarten',
            'kinder',
            'nursery',
            'toddler',
            'daycare',
            'day care',
            'pre-kinder',
            'prekinder',
            'early childhood',
        ],

        // ── SENIOR HIGH TRACKS ───────────────────────────────────────────
        'stem' => [
            'stem',
            'stem track',
            'stem strand',
            'science technology engineering mathematics',
            'physics',
            'calculus',
            'biology',
            'chemistry',
            'engineering track',
            'science track',
        ],
        'abm' => [
            'abm',
            'abm track',
            'abm strand',
            'accountancy business management',
            'accounting',
            'abm strand',
            'business track',
        ],
        'humss' => [
            'humss',
            'humss track',
            'humss strand',
            'humanities social sciences',
            'liberal arts',
            'social science track',
            'humanities track',
        ],
        'ga' => [
            'general academics',
            'ga track',
            'ga strand',
            'general academic strand',
            'general studies',
        ],
        'he' => [
            'home economics',
            'he track',
            'he strand',
            'culinary',
            'cooking',
            'food technology',
            'home ec',
            'cookery',
        ],

        // ── COLLEGE PROGRAMS ─────────────────────────────────────────────
        'computer_science' => [
            'computer science',
            'bs computer science',
            'bscs',
            'programming',
            'software',
            'coding',
            'software engineering',
            'it degree',
            'information technology degree',
        ],
        'computer_technology' => [
            'computer technology',
            'associate computer technology',
            'computer tech',
            'associate degree',
            'act',
            'computer associate',
            '2 year course',
        ],
        'business_admin' => [
            'business administration',
            'business admin',
            'bsba',
            'operations management',
            'financial management',
            'marketing management',
            'business degree',
            'bs business',
            'business course',
        ],
        'tourism' => [
            'tourism',
            'tourism management',
            'bs tourism',
            'bstm',
            'travel',
            'tour guide',
            'travel agency',
            'hospitality tourism',
        ],
        'hospitality' => [
            'hospitality',
            'hospitality management',
            'bs hospitality',
            'bshm',
            'hotel management',
            'restaurant management',
            'food service',
            'hotel course',
        ],
        'physical_education' => [
            'physical education',
            'bs physical education',
            'bped',
            'sports science',
            'coaching',
            'pe course',
            'fitness',
            'sports education',
        ],
        'early_childhood_ed' => [
            'early childhood education',
            'beced',
            'early childhood',
            'preschool education',
            'kindergarten education',
            'child development',
            'ece degree',
        ],
        'elementary_ed' => [
            'elementary education',
            'beed',
            'primary education',
            'grade school education',
            'teaching elementary',
            'elementary teaching',
            'bs elementary education',
        ],
        'secondary_ed' => [
            'secondary education',
            'bsed',
            'high school education',
            'teaching high school',
            'secondary teaching',
            'bs secondary education',
            'high school teacher',
        ],

        // ── IDENTITY ─────────────────────────────────────────────────────
        'identity' => [
            'who are you',
            'what are you',
            'your name',
            'jarvis',
            'introduce yourself',
            'what is jarvis',
            'jarvis meaning',
            'jarvis acronym',
            'what does jarvis stand for',
        ],

        // ── GREETING ─────────────────────────────────────────────────────
        'greeting' => [
            'hello',
            'hi',
            'hey',
            'good morning',
            'good afternoon',
            'good evening',
            'good day',
            'kamusta',
            'kumusta',
            'musta',
        ],

        // ── THANKS ───────────────────────────────────────────────────────
        'thanks' => [
            'thanks',
            'thank you',
            'salamat',
            'maraming salamat',
            'appreciate',
            'thank you so much',
            'thx',
        ],

        // ── WEBSITE NAVIGATION ───────────────────────────────────────────
        'website_online_grade' => [
            'online grade',
            'check grades',
            'view grades',
            'grade portal',
            'online grades',
            'check my grades',
            'see my grades',
            'academic record',
            'grade checking',
        ],
        'website_opac' => [
            'opac',
            'library catalog',
            'online library',
            'library search',
            'book search',
            'library system',
            'borrow book',
        ],
        'website_alumni' => [
            'alumni',
            'alumni tracer',
            'graduates',
            'former students',
            'alumni network',
            'alumni association',
        ],
        'website_enrollment' => [
            'online enrollment',
            'enrollment portal',
            'enroll online',
            'online registration',
            'enrollment link',
            'where to enroll online',
        ],
    ];

    public function score(ProcessedInput $input): IntentMatch
    {
        $scores = [];

        foreach ($this->intentKeywords as $intent => $keywords) {
            $score = 0;

            foreach ($keywords as $keyword) {
                if (str_contains($input->normalized, $keyword)) {
                    // Multi-word keywords score much higher — more specific
                    $wordCount = substr_count($keyword, ' ') + 1;
                    $score += $wordCount * 10;
                }
            }

            // Also match individual tokens for single-word keywords
            foreach ($input->tokens as $token) {
                foreach ($keywords as $keyword) {
                    // Only match single-word keywords against tokens
                    if (!str_contains($keyword, ' ') && $keyword === $token) {
                        $score += 5;
                    }
                }
            }

            if ($score > 0) {
                $scores[$intent] = $score;
            }
        }

        if (empty($scores)) {
            return new IntentMatch(
                intent: 'unknown',
                confidence: 0.0,
                source: 'fallback',
            );
        }

        arsort($scores);
        $topIntent  = array_key_first($scores);
        $topScore   = $scores[$topIntent];
        $total      = array_sum($scores);
        $confidence = $total > 0 ? $topScore / $total : 0.0;

        if ($confidence < $this->threshold) {
            return new IntentMatch(
                intent: 'unknown',
                confidence: round($confidence, 3),
                source: 'fallback',
            );
        }

        return new IntentMatch(
            intent: $topIntent,
            confidence: round($confidence, 3),
            source: 'keyword',
        );
    }
}
