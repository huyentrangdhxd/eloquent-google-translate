<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('eloquent-translate.database_table_log'), function (Blueprint $table) {
            $table->id();
            $table->bigInteger('model_id')->unsigned();
            $table->string('model');
            $table->string('uuid')->unique()->index();
            $table->string('source_locale', 10);
            $table->json('target_locales');
            $table->json('fields');
            $table->string('status');
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('eloquent-translate.database_table_log'));
    }
};
