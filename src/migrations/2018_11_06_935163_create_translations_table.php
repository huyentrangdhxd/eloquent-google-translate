<?php 

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

// use EloquentTranslate;

class CreateTranslationsTable extends Migration {

    public function up()
    {
        Schema::create( config('eloquent-translate.database_table') , function(Blueprint $table) {

            $table->increments('id')->unsigned();
            $table->bigInteger('model_id')->unsigned();
            $table->string('attribute');
            $table->string('model');
            $table->string('locale', 10);
            $table->longText('translation');
            $table->timestamps();

            $table->unique(['model', 'model_id', 'locale', 'attribute']);
        });
    }

    public function down()
    {
        Schema::drop( config('eloquent-translate.database_table') );
    }
}