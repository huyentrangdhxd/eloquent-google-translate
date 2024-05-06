<?php

namespace TracyTran\EloquentTranslate\Services;

use TracyTran\EloquentTranslate\Contracts\TranslatorContract;
use Google\Cloud\Translate\V2\TranslateClient;

class GoogleTranslate implements TranslatorContract
{

    public function translate($text, $locale)
    {
        $translatedText = null;

        try {
            $client = new TranslateClient([
                'key' => config('eloquent-translate.google_api_key')
            ]);


            $translatedText = $client->translate($this->convertText($text), [
                'target' => $locale
            ]);
        } catch (\Exeption $e) {
        }

        return $this->removeNoTranslate($translatedText['text']);
    }

    private function convertText($text)
    {
        $noTranslates = config('eloquent-translate.no_translate_between');

        foreach ($noTranslates as $noTranslate) {
            $startChar = preg_quote($noTranslate[0], '/');
            $endChar = preg_quote($noTranslate[1], '/');
            $pattern = '/' . $startChar . '(.*?)' . $endChar . '/s';
            $replacement = '<span class="notranslate">'.$noTranslate[0] . '$1' . $noTranslate[1].'</span>';

            // Perform replacement
            $text = preg_replace($pattern, $replacement, $text);
        }

        return $text;
    }

    private function removeNoTranslate($text)
    {
        return str_replace(['<span class="notranslate">', '</span>'], '', $text);
    }
}
