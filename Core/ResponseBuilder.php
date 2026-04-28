<?php

require_once __DIR__ . '/DTO/IntentMatch.php';
require_once __DIR__ . '/DTO/ChatResponse.php';

class ResponseBuilder
{

    private array $templates;

    private array $followUpMap = [
        'admission'        => 'Would you also like to know about tuition fees?',
        'tuition'          => 'Would you like to know about the admission requirements?',
        'course'           => 'Would you like to know the admission requirements for a specific program?',
        'senior_high'      => 'Would you like to know about a specific track like STEM or ABM?',
        'junior_high'      => 'Would you like to know about Senior High School tracks?',
        'grade_school'     => 'Would you like to know about Junior High School?',
        'preschool'        => 'Would you like to know about Grade School?',
        'computer_science' => 'Would you like to know the admission requirements for this program?',
        'computer_technology' => 'Would you like to know the admission requirements for this program?',
        'business_admin'   => 'Would you like to know the admission requirements for this program?',
        'tourism'          => 'Would you like to know the admission requirements for this program?',
        'hospitality'      => 'Would you like to know the admission requirements for this program?',
        'physical_education'  => 'Would you like to know the admission requirements for this program?',
        'early_childhood_ed'  => 'Would you like to know the admission requirements for this program?',
        'elementary_ed'    => 'Would you like to know the admission requirements for this program?',
        'secondary_ed'     => 'Would you like to know the admission requirements for this program?',
        'stem'             => 'Would you like to know more about Senior High School at SFAC?',
        'abm'              => 'Would you like to know more about Senior High School at SFAC?',
        'humss'            => 'Would you like to know more about Senior High School at SFAC?',
        'ga'               => 'Would you like to know more about Senior High School at SFAC?',
        'he'               => 'Would you like to know more about Senior High School at SFAC?',
        'location'         => 'Would you like our contact details as well?',
        'contact'          => 'Would you like directions to the campus?',
        'facilities'       => 'Would you like to know about our library system?',
    ];

    public function __construct(array $templates)
    {
        $this->templates = $templates;
    }

    public function build(IntentMatch $match, array $session): ChatResponse
    {
        $identifier = $session['name'] ?? $session['title'] ?? 'Sir';

        // For grade_level intent, entity holds the actual level
        $intent = ($match->intent === 'grade_level' && $match->entity !== null)
            ? $match->entity
            : $match->intent;

        // Get template set, fall back to unknown if intent has no templates
        $templateSet = $this->templates[$intent] ?? $this->templates['unknown'];

        // Pick a random template
        $template = $templateSet[array_rand($templateSet)];

        // Fill all slots
        $message = $this->fillSlots($template, $identifier);

        // Get follow-up suggestion if available
        $followUp = $this->followUpMap[$intent] ?? null;

        return new ChatResponse(
            message: $message,
            intent: $intent,
            confidence: $match->confidence,
            source: $match->source,
            followUp: $followUp,
        );
    }

    private function fillSlots(string $template, string $identifier): string
    {
        $greeting = $this->getTimeGreeting();

        return str_replace(
            ['{name}', '{title}', '{greeting}', '{greeting_lower}'],
            [$identifier, $identifier, $greeting, strtolower($greeting)],
            $template
        );
    }

    private function getTimeGreeting(): string
    {
        date_default_timezone_set('Asia/Manila');
        $hour = (int) date('G');
        if ($hour >= 5  && $hour < 12) return 'Good morning';
        if ($hour >= 12 && $hour < 18) return 'Good afternoon';
        return 'Good evening';
    }
}
