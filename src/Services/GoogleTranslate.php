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

            $translatedText = $client->translate($text, [
                'target' => $locale
            ]);
        } catch (\Exeption $e) {
        }

        return $translatedText['text'];
    }
}
