<?php

require_once __DIR__ . '/DTO/ProcessedInput.php';
require_once __DIR__ . '/DTO/IntentMatch.php';

class PatternMatcher
{

    /**
     * Ordered by specificity — most specific patterns first.
     * First match wins, so order matters.
     */
    private array $patterns = [

        // ── IDENTITY / BOT INFO ──────────────────────────────────────────
        [
            'pattern'    => '/^(who are you|what are you|your name|introduce yourself|what is jarvis|jarvis meaning|what does jarvis stand for|jarvis acronym)$/i',
            'intent'     => 'identity',
            'confidence' => 1.0,
        ],
        [
            'pattern'    => '/(who (made|created|built) you|who is your (creator|developer|programmer))/i',
            'intent'     => 'jarvis_creator',
            'confidence' => 1.0,
        ],

        // ── GREETING ─────────────────────────────────────────────────────
        [
            'pattern'    => '/^(hi|hello|hey|good morning|good afternoon|good evening|good day|yo|sup|musta|kamusta|kumusta)[\s\!\.\,]?$/i',
            'intent'     => 'greeting',
            'confidence' => 1.0,
        ],

        // ── THANKS ───────────────────────────────────────────────────────
        [
            'pattern'    => '/^(thanks|thank you|thx|salamat|ty|maraming salamat|thank you so much)[\s\!\.\,]?$/i',
            'intent'     => 'thanks',
            'confidence' => 1.0,
        ],

        // ── ADMISSION / ENROLLMENT ───────────────────────────────────────
        [
            'pattern'    => '/how (do i|can i|to) (enroll|apply|register|get in|be admitted)/i',
            'intent'     => 'admission',
            'confidence' => 1.0,
        ],
        [
            'pattern'    => '/paano (mag-?[\s]?enroll|mag-?[\s]?apply|makapag-?[\s]?enroll|pumasok|makapasok)/i',
            'intent'     => 'admission',
            'confidence' => 1.0,
        ],
        [
            'pattern'    => '/what (are|is) the (admission |enrollment )?(requirements?|documents?|docs?|qualifications?|needed)/i',
            'intent'     => 'admission',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/ano (ang|ba ang) (requirements?|kailangan|documents?|kelangan) (para |sa )?(mag-?enroll|makapasok|admission)?/i',
            'intent'     => 'admission',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(admission|enrollment) (requirements?|process|procedure|steps?)/i',
            'intent'     => 'admission',
            'confidence' => 0.95,
        ],

        // ── TUITION / FEES ───────────────────────────────────────────────
        [
            'pattern'    => '/how much (is|are|does|will )?(the )?(tuition|fee|fees|enrollment|it cost|schooling cost)/i',
            'intent'     => 'tuition',
            'confidence' => 1.0,
        ],
        [
            'pattern'    => '/magkano (ang )?(tuition|bayad|fee|fees|enrollment|pagbabayad)/i',
            'intent'     => 'tuition',
            'confidence' => 1.0,
        ],
        [
            'pattern'    => '/(tuition|school) (fee|fees|rate|rates|cost|payment)/i',
            'intent'     => 'tuition',
            'confidence' => 0.95,
        ],
        [
            'pattern'    => '/(scholarship|financial aid|discount|payment scheme|payment plan)/i',
            'intent'     => 'tuition',
            'confidence' => 0.93,
        ],

        // ── LOCATION ─────────────────────────────────────────────────────
        [
            'pattern'    => '/where (is|are) (you|the school|sfac|saint francis|the campus|located|your campus)/i',
            'intent'     => 'location',
            'confidence' => 1.0,
        ],
        [
            'pattern'    => '/saan (ba )?(ang|located|naroroon|makikita|nandoon) (ang )?(school|sfac|campus|paaralan)/i',
            'intent'     => 'location',
            'confidence' => 1.0,
        ],
        [
            'pattern'    => '/(address|directions?|how to get (there|to sfac|to the school)|campus location)/i',
            'intent'     => 'location',
            'confidence' => 0.95,
        ],

        // ── CONTACT ──────────────────────────────────────────────────────
        [
            'pattern'    => '/how (can i|do i|to) (contact|reach|call|email|message) (you|sfac|the school|admissions?)/i',
            'intent'     => 'contact',
            'confidence' => 1.0,
        ],
        [
            'pattern'    => '/(contact|phone|number|email|reach) (number|info|information|details?)? ?(of|for)? ?(the )?(school|sfac|admissions?|office)/i',
            'intent'     => 'contact',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/paano (makipag-?ugnayan|makontrata|makausap) (sa )?(school|sfac|admissions?)/i',
            'intent'     => 'contact',
            'confidence' => 1.0,
        ],

        // ── COURSES / PROGRAMS ───────────────────────────────────────────
        [
            'pattern'    => '/what (courses?|programs?|degrees?|majors?) (do you|does sfac|are) (offer|available|have|offering)/i',
            'intent'     => 'course',
            'confidence' => 1.0,
        ],
        [
            'pattern'    => '/(anong|ano ang) (courses?|programs?|kurso|degree) (ang )?(available|offered|meron|mayroon|inaalok)/i',
            'intent'     => 'course',
            'confidence' => 1.0,
        ],
        [
            'pattern'    => '/(list|show) (of )?(all )?(courses?|programs?|degrees?|majors?)/i',
            'intent'     => 'course',
            'confidence' => 0.97,
        ],

        // ── FACILITIES ───────────────────────────────────────────────────
        [
            'pattern'    => '/what (facilities|amenities|buildings?|rooms?|labs?|laboratories?) (do you|does sfac|are) (have|available|offer)/i',
            'intent'     => 'facilities',
            'confidence' => 1.0,
        ],
        [
            'pattern'    => '/(tell me about|about|what is|info on) (the )?(library|laboratory|gym|cafeteria|clinic|facilities)/i',
            'intent'     => 'facilities',
            'confidence' => 0.95,
        ],

        // ── SCHEDULE ─────────────────────────────────────────────────────
        [
            'pattern'    => '/(school|class|academic) (schedule|calendar|year|semester)/i',
            'intent'     => 'schedule',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/when (does|do|will|is) (school|class|enrollment|semester) (start|begin|open|end)/i',
            'intent'     => 'schedule',
            'confidence' => 1.0,
        ],
        [
            'pattern'    => '/kailan (magsisimula|magtatapos|mag-e-enroll|magbubukas) (ang )?(klase|school|enrollment|semester)/i',
            'intent'     => 'schedule',
            'confidence' => 1.0,
        ],

        // ── SPECIFIC PROGRAMS ────────────────────────────────────────────
        [
            'pattern'    => '/(tell me about|about|what is|info on|information about) (computer science|bscs)/i',
            'intent'     => 'computer_science',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(tell me about|about|what is|info on|information about) (computer technology|act)/i',
            'intent'     => 'computer_technology',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(tell me about|about|what is|info on|information about) (business administration|bsba)/i',
            'intent'     => 'business_admin',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(tell me about|about|what is|info on|information about) (tourism management|bstm)/i',
            'intent'     => 'tourism',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(tell me about|about|what is|info on|information about) (hospitality management|bshm)/i',
            'intent'     => 'hospitality',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(tell me about|about|what is|info on|information about) (physical education|bped)/i',
            'intent'     => 'physical_education',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(tell me about|about|what is|info on|information about) (early childhood education|beced|ece)/i',
            'intent'     => 'early_childhood_ed',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(tell me about|about|what is|info on|information about) (elementary education|beed)/i',
            'intent'     => 'elementary_ed',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(tell me about|about|what is|info on|information about) (secondary education|bsed)/i',
            'intent'     => 'secondary_ed',
            'confidence' => 0.97,
        ],

        // ── SENIOR HIGH TRACKS ───────────────────────────────────────────
        [
            'pattern'    => '/(tell me about|about|what is|info on) (science technology engineering mathematics|stem track|stem strand)/i',
            'intent'     => 'stem',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(tell me about|about|what is|info on) (accountancy business management|abm track|abm strand)/i',
            'intent'     => 'abm',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(tell me about|about|what is|info on) (humanities social sciences|humss track|humss strand)/i',
            'intent'     => 'humss',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(tell me about|about|what is|info on) (general academics|ga track|ga strand)/i',
            'intent'     => 'ga',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(tell me about|about|what is|info on) (home economics|he track|he strand)/i',
            'intent'     => 'he',
            'confidence' => 0.97,
        ],

        // ── EDUCATION LEVELS ─────────────────────────────────────────────
        [
            'pattern'    => '/(tell me about|about|what is|info on) (senior high school|shs|grade 11|grade 12)/i',
            'intent'     => 'senior_high',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(tell me about|about|what is|info on) (junior high school|jhs|grade 7|grade 8|grade 9|grade 10)/i',
            'intent'     => 'junior_high',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(tell me about|about|what is|info on) (grade school|elementary|primary school)/i',
            'intent'     => 'grade_school',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(tell me about|about|what is|info on) (preschool|kindergarten|kinder|nursery)/i',
            'intent'     => 'preschool',
            'confidence' => 0.97,
        ],

        // ── GRADE NUMBER DETECTION ───────────────────────────────────────
        [
            'pattern'    => '/\bgrade\s*(1[0-2]|[1-9])\b/i',
            'intent'     => 'grade_level',
            'confidence' => 1.0,
            'extractor'  => 'extractGradeLevel',
        ],

        // ── WEBSITE NAVIGATION ───────────────────────────────────────────
        [
            'pattern'    => '/(how to|where to|where can i) (check|view|see|access) (my )?(grades?|gwa|academic record)/i',
            'intent'     => 'website_online_grade',
            'confidence' => 1.0,
        ],
        [
            'pattern'    => '/(library|opac|book|catalog) (search|system|online|access|catalog)/i',
            'intent'     => 'website_opac',
            'confidence' => 0.97,
        ],
        [
            'pattern'    => '/(alumni|graduate) (tracer|network|association|records?)/i',
            'intent'     => 'website_alumni',
            'confidence' => 0.97,
        ],
    ];

    public function match(ProcessedInput $input): ?IntentMatch
    {
        foreach ($this->patterns as $rule) {
            if (preg_match($rule['pattern'], $input->normalized, $matches)) {
                $entity = null;
                if (isset($rule['extractor'])) {
                    $entity = $this->{$rule['extractor']}($matches);
                }
                return new IntentMatch(
                    intent: $rule['intent'],
                    confidence: $rule['confidence'],
                    source: 'pattern',
                    entity: $entity,
                );
            }
        }
        return null;
    }

    private function extractGradeLevel(array $matches): string
    {
        $grade = (int) $matches[1];
        if ($grade >= 1  && $grade <= 6)  return 'grade_school';
        if ($grade >= 7  && $grade <= 10) return 'junior_high';
        if ($grade >= 11 && $grade <= 12) return 'senior_high';
        return 'unknown';
    }
}
