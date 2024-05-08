<?php

namespace TracyTran\EloquentTranslate\Traits;

use TracyTran\EloquentTranslate\Jobs\TranslatorJob;
use TracyTran\EloquentTranslate\Models\Translation;
use TracyTran\EloquentTranslate\Services\Translator;
use TracyTran\EloquentTranslate\TranslateModelObserver;
use TracyTran\EloquentTranslate\Facades\EloquentTranslate;

trait TranslatorTrait
{

    public static function getTranslationModelClassName()
    {
        return get_class();
    }

    // this function will update appends variable
    public function initializeTranslatorTrait()
    {
        $this->appends = array_unique(array_merge($this->appends, $this->getTranslationAttributes()));
        $this->hidden[] = 'localeTranslations';
    }

    /**
     * Hook into the boot method of the model and register the observer
     *
     * @return void
     */
    protected static function bootTranslatorTrait(): void
    {
        $modelClass = self::getTranslationModelClassName();
        $modelClass::observe(new TranslateModelObserver);

        static::addGlobalScope('translate', function ($builder) {
            $builder->with('localeTranslations');
        });

    }

    public function __call($method, $parameters)
    {
        if (preg_match('/^get(.+)Attribute$/', $method, $matches)) {
            $key = lcfirst($matches[1]);
            if (in_array($key, static::getTranslationAttributes())) {
                return $this->getAttributeTranslation($key);
            }
        }

        return parent::__call($method, $parameters);
    }

    public function getAttributeTranslation($key, $attr = null)
    {
        $attr = $attr ?? parent::getAttribute($key);

        // Model field we are trying to access is not in the array of
        // attributes to translate so we skip.
        if (!in_array($key, $this->getTranslationAttributes()) || !$this->relationLoaded('localeTranslations')) {

            return $attr;
        }

        try {
            $translationModel = $this->localeTranslations->where('attribute', $key)->first();

            return $translationModel ? $translationModel->translation : $attr;

        } catch (\Exception $e) {
        }

        return $attr;
    }

    public function getAttribute($key, $attr = null)
    {
        return $this->getAttributeTranslation($key, $attr = null);
    }

    public function translate($locale = null)
    {
        $model = $this;
        $validAttributes = [];

        foreach ($model->getTranslationAttributes() as $attribute) {
            $value = $model->{$attribute};
            if (
                !$value ||
                $this->attributesToIgnoreAttribute($model, $attribute) ||
                (!$model->wasChanged($attribute) && !$model->wasRecentlyCreated)
            ) {
                continue;
            }

            $validAttributes[] = $attribute;

        }
        $locales = $locale ?? config('eloquent-translate.locales');

        if (config('eloquent-translate.queue') === true) {
            // Disatch the job
            dispatch(new TranslatorJob($model, $validAttributes, $locales));
        } else {

            // Run without queue
            (new Translator($model, $validAttributes, $locales))->saveTranslation();
        }

        return true;
    }

    public function attributesToIgnoreAttribute($model, $attr)
    {

        if (method_exists($this, 'ignoreAttributes')) {

            $ignoreAttributes = $this->ignoreAttributes();

            if (!array_key_exists($attr, $ignoreAttributes)) {

                return false;
            }

            try {

                $attributeData = $ignoreAttributes[$attr];

                if ($model->{$attributeData['column']} == $attributeData['value']) {

                    return true;
                }
            } catch (\Exception $e) {
            }
        }

        return false;
    }

    /**
     * Set translations manually on the model.
     *
     * @return Boolean|Illuminate\Database\Eloquent\Model
     */
    public function setTranslation($attribute, $locale, $translation, $force = false)
    {
        // Check if the attribute name is defined in the model. If it's not,
        // and the $force parameter is not true, we skip.

        if (!$force && !in_array($attribute, $this->getTranslationAttributes()))
            return false;

        return $this->translations()->updateOrCreate(
            [
                'model' => $this->getTranslationModelClassName(),
                'model_id' => $this->id,
                'attribute' => $attribute,
                'locale' => $locale,
            ],
            [
                'translation' => $translation
            ]
        );
    }

    /**
     * Set translations manually on the model.
     *
     * @return Boolean|Illuminate\Database\Eloquent\Model
     */
    public function setTranslations($attribute, $translations)
    {
        // Check if the attribute name is defined in the model. If it's not,
        // and the $force parameter is not true, we skip.

        if (!in_array($attribute, $this->getTranslationAttributes()))
            return false;

        collect($translations)->each(function ($translation, $locale) use ($attribute) {

            $this->translations()->updateOrCreate(
                [
                    'model' => $this->getTranslationModelClassName(),
                    'model_id' => $this->id,
                    'attribute' => $attribute,
                    'locale' => $locale,
                ],
                [
                    'translation' => $translation
                ]
            );
        });

        return true;
    }

    /**
     * Fetch translations for a given model.
     *
     * @return Illuminate\Database\Eloquent\Model|Illuminate\Support\Collection
     */
    public function getTranslation($attribute, $locale, $default = null)
    {
        $translation = $this->translations()
            ->where('attribute', $attribute)
            ->where('locale', $locale);

        if ($translation->count() > 0) {
            return $translation->first()->translation;
        }

        return $default;
    }

    /**
     * Check if model has translation for a given locale and attribute
     *
     * @return Boolean
     */
    public function hasTranslation($attribute, $locale)
    {
        $translation = $this->translations()
            ->where('attribute', $attribute)
            ->where('locale', $locale);

        return $translation->count() > 0;
    }

    /**
     * Fetch translations for a given model and a locale.
     *
     * @return Illuminate\Support\Collection
     */
    public function getTranslations($locale)
    {
        return $this->translations()->where('locale', $locale)->get();
    }

    /**
     * The translation relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function translations()
    {
        return $this->hasMany(Translation::class, 'model_id', 'id')->where('model', $this->getTranslationModelClassName());
    }

    public function localeTranslations()
    {
        return $this->translations()->where('locale', EloquentTranslate::getLocale());
    }

    /**
     * Get the attributes to be translateable
     *
     * @return array
     */
    public static abstract function getTranslationAttributes(): array;

    /**
     * Delete model translations by locale
     *
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function deleteTranslation($attribute, $locale)
    {
        return $this->translations()
            ->where('attribute', $attribute)
            ->where('locale', $locale)
            ->delete();
    }

    public function deleteAllTranslations()
    {
        return $this->translations()->delete();
    }
}
