<?php 

namespace TracyTran\EloquentTranslate;

use Illuminate\Database\Eloquent\Model;
use TracyTran\EloquentTranslate\Models\Translation;

class TranslateModelObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param  \App\User  $user
     * @return void
     */
    public function created(Model $model)
    {
        if( config('eloquent-translate.auto_translate') === true)
            $model->translate();

        return $model;
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  \App\User  $user
     * @return void
     */
    public function updated(Model $model)
    {   
        if( config('eloquent-translate.auto_translate') === true)
            $model->translate();

        return $model;
    }

    /**
     * Delete translations for a model when the model is deleted
     *
     * @param  \App\User  $user
     * @return void
     */
    public function deleted(Model $model)
    {
        $modelClass = get_class( $model );

        Translation::where('model', $modelClass)
            ->where('model_id', $model->id)
            ->delete();
    }
}