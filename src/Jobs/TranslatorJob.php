<?php

namespace TracyTran\EloquentTranslate\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use TracyTran\EloquentTranslate\Services\Translator;

class TranslatorJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $model;
    public $attribute;
    public $locale;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Model $model, $attributes, $locales)
    {
        $this->model = $model;
        $this->attributes = $attributes;
        $this->locales = $locales;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ( new Translator( $this->model, $this->attributes, $this->locales ) )->saveTranslation();
    }
}
