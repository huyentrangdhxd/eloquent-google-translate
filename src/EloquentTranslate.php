<?php

namespace TracyTran\EloquentTranslate;

use Illuminate\Database\Eloquent\Model;

class EloquentTranslate {

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
        return get_translate_locale();
    }
}
