<?php

namespace TracyTran\EloquentTranslate\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use TracyTran\EloquentTranslate\Contracts\TranslationServiceContract;

class AITranslateSelectedLocalesJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $timeout = 600;

    protected $tries = 1;

    public $model;

    public function __construct(
        Model $model,
        public string $sourceLocale,
        public array $targetLocales,
        public array $fields,
    ) {
        $this->model = $model;
    }

    public function handle(): void
    {
        try {
            $translations = App::make(TranslationServiceContract::class)
                ->translateMultiLocale($this->sourceLocale, $this->targetLocales, $this->fields);

            if (empty($translations) || ! is_array($translations)) {
                return;
            }

            $upsert = [];
            $translationAttributes = $this->model::getTranslationAttributes();
            $translationModelClass = $this->model::getTranslationModelClassName();

            foreach ($translations as $attribute => $localeTranslations) {
                if (! in_array($attribute, $translationAttributes) || ! is_array($localeTranslations)) {
                    continue;
                }

                foreach ($localeTranslations as $locale => $translation) {
                    if (! in_array($locale, $this->targetLocales) || is_null($translation)) {
                        continue;
                    }

                    $upsert[] = [
                        'model' => $translationModelClass,
                        'model_id' => $this->model->id,
                        'attribute' => $attribute,
                        'locale' => $locale,
                        'translation' => $translation,
                    ];
                }
            }

            if (! empty($upsert)) {
                $this->model->translations()->upsert($upsert, ['model', 'model_id', 'locale', 'attribute']);
                $this->model->update(['updated_at' => now()]);
            }
        } catch (\Throwable $exception) {
            Log::error('Auto translate by AI failed', [
                'model' => $this->model::getTranslationModelClassName(),
                'model_id' => $this->model->id,
                'error' => $exception->getMessage(),
                'source_locale' => $this->sourceLocale,
                'target_locales' => $this->targetLocales,
                'fields' => array_keys($this->fields),
            ]);

            throw $exception;
        }
    }
}