<?php

namespace TracyTran\EloquentTranslate\Jobs;

use App\Models\TranslationJob;
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

    public string $jobId;

    public function __construct(string $jobId)
    {
        $this->jobId = $jobId;
    }

    public function handle(): void
    {
        $translationJob = TranslationJob::where('job_id', $this->jobId)->firstOrFail();
        $translationJob->markAsProcessing();

        $modelClass = $translationJob->model;
        $model = $modelClass::find($translationJob->model_id);

        if (! $model) {
            return;
        }

        try {
            $translations = App::make(TranslationServiceContract::class)
                ->translateMultiLocale(
                    $translationJob->source_locale,
                    $translationJob->target_locales,
                    $translationJob->fields
                );
            if (empty($translations) || !is_array($translations)) {
                $translationJob->markAsCompleted($translations);
                return;
            }

            $upsert = [];

            $translationAttributes = $modelClass::getTranslationAttributes();
            $translationModelClass = $modelClass::getTranslationModelClassName();

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
            $translationJob->markAsCompleted($translations);
        } catch (\Throwable $exception) {

            Log::error('Auto translate by AI failed', [
                'model' => $modelClass,
                'model_id' => $translationJob->model_id,
                'error' => $exception->getMessage(),
                'job_id' => $this->jobId,
            ]);
            $translationJob->markAsFailed($exception->getMessage());

            throw $exception;
        }
    }
}
