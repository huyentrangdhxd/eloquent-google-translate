<?php

namespace TracyTran\EloquentTranslate\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use TracyTran\EloquentTranslate\Contracts\TranslationServiceContract;
use TracyTran\EloquentTranslate\Models\TranslationLog;

class AITranslateSelectedLocalesJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $timeout = 600;

    protected $tries = 1;

    public string $uuid;

    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function handle(): void
    {
        $translationJob = TranslationLog::where('uuid', $this->uuid)->firstOrFail();
        $translationJob->markAsProcessing();

        $modelClass = $translationJob->model;
        $model = $modelClass::find($translationJob->model_id);
        $translationAttributes = $modelClass::getTranslationAttributes();
        $translationModelClass = $modelClass::getTranslationModelClassName();
        $upsert = [];

        if (! $model) {
            throw new \Exception("Model {$modelClass} with ID {$translationJob->model_id} not found");
        }

        try {
            $result = App::make(TranslationServiceContract::class)
                ->translateMultiLocale(
                    $translationJob->source_locale,
                    $translationJob->target_locales,
                    $translationJob->fields
                );

            $translations = $result['translations'] ?? [];
            $successLocales = $result['success_locales'] ?? [];
            $failedLocales = $result['failed_locales'] ?? [];

            foreach ($translations as $attribute => $localeTranslations) {

                if (! in_array($attribute, $translationAttributes) || ! is_array($localeTranslations)) {
                    continue;
                }

                foreach ($localeTranslations as $locale => $translation) {

                    if (! in_array($locale, $translationJob->target_locales) || is_null($translation)) {
                        continue;
                    }

                    $upsert[] = [
                        'model' => $translationModelClass,
                        'model_id' => $translationJob->model_id,
                        'attribute' => $attribute,
                        'locale' => $locale,
                        'translation' => $translation,
                    ];
                }
            }

            if (! empty($upsert)) {
                $model->translations()->upsert(
                    $upsert,
                    ['model', 'model_id', 'locale', 'attribute']
                );

                $model->update(['updated_at' => now()]);
            }

            if (! empty($failedLocales)) {
                $message = 'Failed locales: ' . implode(', ', $failedLocales);
                if (! empty($successLocales)) {
                    $message .= ' | Translated locales: ' . implode(', ', $successLocales);
                }
                $translationJob->markAsFailed($message);
            } else {
                $translationJob->markAsCompleted($translations);
            }
        } catch (\Throwable $exception) {

            Log::error('Auto translate by AI failed', [
                'model' => $modelClass,
                'model_id' => $translationJob->model_id,
                'error' => $exception->getMessage(),
                'uuid' => $this->uuid,
            ]);
            $translationJob->markAsFailed($exception->getMessage());

            throw $exception;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $translationJob = TranslationLog::where('uuid', $this->uuid)->firstOrFail();

        if ($translationJob) {
            $translationJob->markAsFailed($exception->getMessage());
        }

        Log::error('Translation job permanently failed', [
            'uuid' => $translationJob->uuid,
            'error' => $exception->getMessage(),
        ]);
    }
}
