<?php

namespace TracyTran\EloquentTranslate\Services;

use Illuminate\Support\Arr;
use TracyTran\EloquentTranslate\Models\Translation;
use Illuminate\Database\Eloquent\Model;

class Translator
{

    public function __construct(Model $model, $attributes, $locales)
    {
        $this->model = $model;
        $this->attributes = $attributes;
        $this->locales = Arr::wrap($locales);
    }

    public function saveTranslation()
    {
        $upsert = [];

        foreach ($this->attributes as $attribute) {
            foreach ($this->locales as $locale) {
                $upsert[] = [
                    'model' => get_class($this->model),
                    'model_id' => $this->model->id,
                    'attribute' => $this->attribute,
                    'locale' => $this->locale,
                    'translation' => $this->getTranslation($this->model->{$this->attribute}, $this->locale)
                ];
            }
        }

        Translation::upsert($upsert, ['model', 'model_id', 'locale', 'attribute']);
    }

    /**
     * Gets the translation string
     *
     * @return string
     */
    public function getTranslation($text, $locale)
    {
        $translatorService = new GoogleTranslate;

        return $translatorService->translate($text, $locale);
    }
}
