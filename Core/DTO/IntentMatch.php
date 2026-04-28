<?php

class IntentMatch
{
    public function __construct(
        public readonly string  $intent,
        public readonly float   $confidence,
        public readonly string  $source,
        public readonly ?string $entity = null,
    ) {}

    public function isUnknown(): bool
    {
        return $this->intent === 'unknown';
    }

    public function isHighConfidence(): bool
    {
        return $this->confidence >= 0.85;
    }
}
