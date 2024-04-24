<?php 

namespace TracyTran\EloquentTranslate\Commands;

use Illuminate\Console\Command;
use TracyTran\EloquentTranslate\Traits\TranslatorTrait;

class BaseCommand extends Command {

    protected $model;

    protected function setModel($model)
    {
        $this->model = $model;
    }

    protected function getModelInstance()
    {
        $modelInstance = null;

        // Check if model exists 
        if( ! $this->model )
        {
            $this->error("You need to specify a model class with the --model option");
            exit();
        }

        return new $this->model;
    }

    protected function modelUsesTrait() 
    {
        // Check if model class uses the translation traits
        if( ! in_array( TranslatorTrait::class, array_keys ( class_uses( $this->model ) ) ) )
        {
            $this->error("Translation is not setup for this model");
            exit();
        }
    }
}