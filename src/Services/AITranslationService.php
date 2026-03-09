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

        $prompts = [];
        $options = ['max_tokens' => 50000];

        foreach ($targetLocales as $locale) {
            $prompts[$locale] = $this->buildTranslationPrompt($sourceLocale, [$locale], $fields);
        }

        $results = $this->aiService->chatPool($prompts, $options);
        $data = [];

        foreach ($results as $result) {
            $content = $result->toArray()['content'] ?? [];

            if (! is_array($content)) {
                continue;
            }

            foreach ($content as $field => $translations) {
                if (! is_array($translations)) {
                    continue;
                }

                foreach ($translations as $locale => $value) {
                    $data[$field][$locale] = $value;
                }
            }
        }

        return $data;
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