<?php

namespace TracyTran\EloquentTranslate\Contracts;

interface TranslationServiceContract
{
    public function translateMultiLocale(string $sourceLocale, array $targetLocales, array $fields): array;
}