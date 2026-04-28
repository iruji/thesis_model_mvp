<?php

class ChatResponse
{
    public function __construct(
        public readonly string  $message,
        public readonly string  $intent,
        public readonly float   $confidence,
        public readonly string  $source,
        public readonly ?string $followUp = null,
    ) {}
}
