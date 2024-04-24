<?php 

namespace TracyTran\EloquentTranslate\Contracts;

interface TranslatorContract {

    public function translate($text, $locale);
}