<?php 

namespace TracyTran\EloquentTranslate\Commands;

class TranslateCommand extends BaseCommand {

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eloquent-translate:translate {--M|model=} {--F|force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Translate eloquent models';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $model = $modelRaw = $this->option('model');
        $force = $this->option('force');

        $this->setModel($model);

        // Check if model exists 
        $model = $this->getModelInstance();

        // Check if model uses translation trait 
        $this->modelUsesTrait();

        // Fetch attribtes
        $attributes = $model->getTranslationAttributes();

        // Check if attribtes are defined 
        if( ! $attributes || empty( $attributes ) )
        {
            return $this->error("Translateable attributes not specified on model class. It should be an array of attributes / columns to translate");
        }


        //Check if a base query is defined on the model. 
        // Use that instead of quering all the models in the table.
        $queryScope = 'scopeBulkTranslationsQuery';

        try {

            $methodExists = ( new \ReflectionMethod($model, $queryScope) )->isStatic();

            if( $methodExists )
            {
                // Fetch the query from the model
                $query = $model::{'bulkTranslationsQuery'}()->get();
                return $this->runRequest( $query, $force );
            }
        }
        catch(\Exception $e) {}

        //Translate all models if no query scope is defined in the model
        $model = $model->all();
        return $this->runRequest( $model );
    }

    private function runRequest($model, $force = false) {

        $this->line("\n");
        $bar = $this->output->createProgressBar(count($model));
        $bar->start();
        
        $model->each(function($model, $key) use($force, $bar){

            $model->translate($force, $force);
            $bar->advance();
        });
        
        $bar->finish();
        $this->line("\n");
    }
}