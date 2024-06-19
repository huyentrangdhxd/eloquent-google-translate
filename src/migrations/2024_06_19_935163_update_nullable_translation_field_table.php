<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

// use EloquentTranslate;

class UpdateNullableTranslationFieldTable extends Migration
{

    public function up()
    {
        Schema::table(config('eloquent-translate.database_table'), function (Blueprint $table) {
            $table->longText('translation')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table(config('eloquent-translate.database_table'), function (Blueprint $table) {
            $table->longText('translation')->change();
        });
    }
}
