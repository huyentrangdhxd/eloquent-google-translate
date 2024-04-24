<?php 

namespace TracyTran\EloquentTranslate\Services;

use TracyTran\EloquentTranslate\Models\Translation;
use Illuminate\Database\Eloquent\Model;

class Translator {

    public function __construct(Model $model, $attribute, $locale)
    {
        $this->model = $model;
        $this->attribute = $attribute;
        $this->locale = $locale;
    }

    public function saveTranslation(){

        Translation::updateOrCreate(
            [
                'model' => get_class( $this->model ),
                'model_id' => $this->model->id,
                'attribute' => $this->attribute,
                'locale' => $this->locale,
            ],
            [
                'translation' => $this->getTranslation( $this->model->{$this->attribute}, $this->locale )
            ]
        );
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