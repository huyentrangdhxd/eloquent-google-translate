<?php

namespace TracyTran\EloquentTranslate\Contracts;

use TracyTran\EloquentTranslate\Services\AIResponse;

interface AIServiceContract
{
    public function chat(string $prompt, array $options = []): AIResponse;

    public function chatPool(array $prompts, array $options = []): array;

    public function countTokens(string $prompt, array $options = []): int;

    public function healthCheck(): bool;

    public function getModelInfo(): array;
}