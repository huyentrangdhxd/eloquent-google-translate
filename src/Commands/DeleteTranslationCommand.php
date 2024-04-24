<?php 

namespace TracyTran\EloquentTranslate\Commands;

use TracyTran\EloquentTranslate\Models\Translation;

class DeleteTranslationCommand extends BaseCommand {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eloquent-translate:clear {--M|model=} {--I|id=} {--L|locale=} {--A|attribute=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete model translations';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $model = $this->option('model');
        $locale = $this->option('locale');
        $modelID = $this->option('id');
        $attribute = $this->option('attribute');

        if ($this->confirm('Are you sure you want to perform this operation?')) {
            
            $translations = Translation::query();

            if( $model )
            {
                try {

                    $model = new $model;
                }
                catch( \Exception $e )
                {
                    return $this->error("Could not find model class.");
                }

                $translations->where('model', get_class($model) );
            }

            if( $locale ){

                $locale = explode(',', $locale);
                $translations->whereIn('locale', $locale);
            }

            if( $modelID )
            {
                $ids = explode(',', $modelID);
                $translations->whereIn('model_id', $ids);
            }

            if( $attribute )
            {
                $attribute = explode(',', $attribute);
                $translations->whereIn('attribute', $attribute);
            }

            return $translations->delete();
        }
    }
}