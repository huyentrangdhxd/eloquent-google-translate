<?php

namespace TracyTran\EloquentTranslate\Services;

use Illuminate\Support\Facades\Http;
use TracyTran\EloquentTranslate\Contracts\TranslationServiceContract;

class DeepLService implements TranslationServiceContract
{
    protected string $apiKey;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('eloquent-translate.ai.drivers.deepl.api_key');
        $this->apiUrl = config('eloquent-translate.ai.drivers.deepl.api_url');
    }

    public function translateMultiLocale(string $sourceLocale, array $targetLocales, array $fields): array
    {
        if (empty($fields) || empty($targetLocales)) {
            return [];
        }

        $translations = [];
        $successLocales = [];
        $failedLocales = [];

        // Prepare texts and protect variables
        $protectedFields = array_map(function ($text) {
            return $this->protectVariables((string)($text ?? ''));
        }, $fields);

        $fieldKeys = array_keys($protectedFields);
        $fieldValues = array_values($protectedFields);

        $responses = Http::pool(fn ($pool) => array_map(
            fn ($locale) => $pool->as($locale)->withHeaders([
                'Authorization' => 'DeepL-Auth-Key ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'text' => $fieldValues,
                'source_lang' => strtoupper($sourceLocale),
                'target_lang' => $this->mapLocale($locale),
                'tag_handling' => 'html',
                'ignore_tags' => ['ignore'],
            ]),
            $targetLocales
        ));

        foreach ($responses as $locale => $response) {
            if ($response->successful()) {
                $successLocales[] = $locale;
                $result = $response->json();
                foreach ($result['translations'] ?? [] as $index => $translation) {
                    $fieldId = $fieldKeys[$index];
                    $translations[$fieldId][$locale] = $this->unprotectVariables($translation['text']);
                }
            } else {
                $failedLocales[] = $locale;
            }
        }

        return [
            'translations' => $translations,
            'success_locales' => $successLocales,
            'failed_locales' => $failedLocales,
        ];
    }

    protected function protectVariables(string $text): string
    {
        $noTranslates = config('eloquent-translate.no_translate_between', [['{{', '}}']]);

        foreach ($noTranslates as $noTranslate) {
            $startChar = preg_quote($noTranslate[0], '/');
            $endChar = preg_quote($noTranslate[1], '/');
            $pattern = '/' . $startChar . '(.*?)' . $endChar . '/s';
            
            $text = preg_replace_callback($pattern, function ($matches) use ($noTranslate) {
                return '<ignore>' . $noTranslate[0] . $matches[1] . $noTranslate[1] . '</ignore>';
            }, $text);
        }

        return $text;
    }

    protected function unprotectVariables(string $text): string
    {
        return str_replace(['<ignore>', '</ignore>'], '', $text);
    }

    protected function mapLocale(string $locale): string
    {
        $locale = strtoupper($locale);

        $map = [
            'en' => 'en-us',
            'pt' => 'pt-pt',
        ];

        return $map[$locale] ?? $locale;
    }
}
