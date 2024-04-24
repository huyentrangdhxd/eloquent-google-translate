<?php 

return [

    

    /*
    |--------------------------------------------------------------------------
    | Database Table name
    |--------------------------------------------------------------------------
    |
    | Database table where translations are stored
    |
    | @required String
    */

    'database_table' => 'translations',

    /*
    |--------------------------------------------------------------------------
    | Google API Key
    |--------------------------------------------------------------------------
    |
    | Google API Key, you can get yours from Google Cloud Console and make sure 
    | you have translations enabled.
    |
    | @required String
    */

    'google_api_key' => env('GOOGLE_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Queue Translations
    |--------------------------------------------------------------------------
    |
    | This library uses a 3rd party to automatically translate your attributes 
    | if `auto_translate` is enabled. It is very important this value is set 
    | to true so it doesn't slow down your app. 
    |
    | It uses Laravel's internal queue system so be sure you understand how 
    | queue works in Laravel and you have already setup your queue.
    |   
    | @required Boolean
    */
    'queue' => env('ELOQUENT_TRANSLATE_QUEUE', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    |
    | The name of the queue where translations should be stored. Setting this 
    | value to null will use the `default` laravel queue
    |
    | @var String|null
    */
    'queue_name' => env( 'ELOQUENT_TRANSLATE_QUEUE_NAME', 'translation'),

    /*
    |--------------------------------------------------------------------------
    | Translation Locales
    |--------------------------------------------------------------------------
    |
    | A list of locales to automatically translate to.
    |
    | Check here for valid values https://cloud.google.com/translate/docs/languages
    |
    | @required Array
    */
    'locales' => [

        'fr',
        'es',
        'pt',
        'sw',
        'ar',
        'yo',
        'ha',
        'ig'
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Locale
    |--------------------------------------------------------------------------
    |
    | Default Locale to use if none is set. 
    |
    | If it is set to a value not in your locales array, the translation will 
    | default to the original value set in your model.
    |
    | @var String
    */
    'fallback_locale' => env( 'ELOQUENT_TRANSLATE_FALLBACK_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Automatically detect locale 
    |--------------------------------------------------------------------------
    |
    | Set this to true to automatically detect the user's locale and return the 
    | translation value of the attributes.
    |
    | This checks for a "locale" from the cookie of the user's browser or a 
    | Translate-Locale HTTP header value. (Usefull if you're using an API).
    |
    | @var Boolean
    */
    'detect_locale' => env( 'ELOQUENT_TRANSLATE_DETECT_LOCALE', true),

    /*
    |--------------------------------------------------------------------------
    | Automatically translate model
    |--------------------------------------------------------------------------
    |
    | A model observer is already set to hook to the create and update event.
    |
    | Automatic translation is done over the internet through a provider, the
    | default for now is Google Translate api. If you set this value to false, 
    | you'll have to manually create translations for your model.
    |
    | Check the documentation for `setTranslation(s)` methods
    |
    | @var Boolean
    */
    'auto_translate' => env( 'ELOQUENT_TRANSLATE_AUTO', true)
];