<?php 

namespace TracyTran\EloquentTranslate\Facades;

use Illuminate\Support\Facades\Facade;

class EloquentTranslate extends Facade
{	
	protected static function getFacadeAccessor()
	{
		return 'eloquent-translate';
	}
}