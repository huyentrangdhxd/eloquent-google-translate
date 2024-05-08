<?php

namespace TracyTran\EloquentTranslate;

use Illuminate\Database\Eloquent\Model;

class EloquentTranslate {

    private $locale;

    public function __construct( $locale = null )
    {
        $this->locale = $locale  ?? get_translate_locale();
    }

    /**
     * Gets the table name where translations are stored
     *
     * @return string
     */
    public function getTranslationsTableName()
    {
        return config('eloquent-translate.database_table');
    }

    public function getLocale()
    {
        return $this->locale;
    }
}
