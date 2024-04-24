<?php 

namespace TracyTran\EloquentTranslate\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{

    protected $fillable = [

        'model_id',
        'model',
        'locale',
        'attribute',
        'translation'
    ];

    public function getTable()
    {
        return config('eloquent-translate.database_table');
    }
}