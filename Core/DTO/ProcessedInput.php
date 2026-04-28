<?php

class ProcessedInput
{
    public function __construct(
        public readonly string $original,
        public readonly string $normalized,
        public readonly array  $tokens,
        public readonly string $language,
    ) {}
}
