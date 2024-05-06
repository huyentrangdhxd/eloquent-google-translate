<?php

namespace TracyTran\EloquentTranslate\Commands;

use App\Enums\Language;
use Illuminate\Support\Arr;
use TracyTran\EloquentTranslate\Jobs\TranslatorJob;
use TracyTran\EloquentTranslate\Services\Translator;

class TranslateCommand extends BaseCommand
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eloquent-translate:translate {model} {locale?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Translate eloquent models';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $model = $modelRaw = $this->argument('model');

        $this->setModel($model);

        // Check if model exists
        $model = $this->getModelInstance();

        // Check if model uses translation trait
        $this->modelUsesTrait();

        $query = $this->translate($model);

    }

    private function translate($model)
    {
        $locales = $this->argument('locale')
            ? Arr::wrap($this->argument('locale'))
            : config('eloquent-translate.locales');
        // Fetch attribtes
        $attributes = $model->getTranslationAttributes();

        // Check if attribtes are defined
        if (!$attributes || empty($attributes)) {
            return $this->error("Translateable attributes not specified on model class. It should be an array of attributes / columns to translate");
        }

        // Check if a base query is defined on the model.
        // Use that instead of quering all the models in the table.
        $queryScope = 'scopeBulkTranslationsQuery';

        $this->line("\n Model:". $model::class);
        $bar = $this->output->createProgressBar(count($attributes) + count($locales));
        $bar->start();

        $model = (method_exists($model, $queryScope)) ? $model->{'bulkTranslationsQuery'}() : $model;
        foreach ($attributes as $attribute) {
            foreach ($locales as $locale) {
                $query = (clone $model)->whereDoesntHave(
                    'translations',
                    fn($q) => $q->where('locale', $locale)->where('attribute', $attribute)
                );
                $this->runRequest($query->get(), $attribute, $locale);
                $bar->advance();
            }

        }

        $bar->finish();
        $this->line("\n");
    }

    private function runRequest($list, $attribute, $locale)
    {

        $list->each(function ($model, $key)  use ($attribute, $locale) {

            if (config('eloquent-translate.queue') === true) {
                // Disatch the job
                dispatch(new TranslatorJob($model, $attribute, $locale));
            } else {

                // Run without queue
                (new Translator($model, $attribute, $locale))->saveTranslation();
            }
        });
    }
}
