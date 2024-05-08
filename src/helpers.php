<?php

use Illuminate\Support\Facades\Cache;

if (!function_exists('getallheaders')) {

    function getallheaders()
    {
        if (!is_array($_SERVER)) {
            return array();
        }

        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

}

if (!function_exists('get_translate_locale')) {

    function get_translate_locale(): ?string
    {
        return Cache::driver('array')->get('user_locale')
            ?? app()->getLocale()
            ?? config('eloquent-translate.fallback_locale');
    }
}
