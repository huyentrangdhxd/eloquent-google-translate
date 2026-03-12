<?php

namespace TracyTran\EloquentTranslate\Services;

use TracyTran\EloquentTranslate\Contracts\AIServiceContract;
use TracyTran\EloquentTranslate\Contracts\TranslationServiceContract;

class AITranslationService implements TranslationServiceContract
{
    protected AIServiceContract $aiService;
    public function __construct(AIServiceContract $aiService)
    {
        $this->aiService = $aiService;
    }

    public function translateMultiLocale(string $sourceLocale, array $targetLocales, array $fields): array
    {
        if (empty($fields) || empty($targetLocales)) {
            return [];
        }

        $options = ['max_tokens' => config('eloquent-translate.ai.max_tokens')];
        $prompts = $this->buildTranslationPrompt($sourceLocale, $targetLocales, $fields);
        $results = $this->aiService->chat($prompts, $options);
        if (! is_array($results)) {
            throw new \Exception('AI service did not return an array');
        }

        return $results->toArray()['content'] ?? [];
    }

    private function buildTranslationPrompt(string $sourceLocale, array $targetLocales, array $fields): string
    {
        $prompt = config('eloquent-translate.ai.prompts.multi_locale_translation');
        $translationData = [
            'source_locale' => $sourceLocale,
            'target_locales' => $targetLocales,
            'fields' => $fields,
        ];

        return str_replace(
            '{$translationData}',
            json_encode($translationData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            $prompt,
        );
    }
}
