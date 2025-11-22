<?php
namespace daandelange\Taxonomy;

@include_once __DIR__ . '/vendor/autoload.php';

use \Throwable;
use \Daandelange\Taxonomy\TranslatedStructure;
use \Kirby\Content\Field as ContentField;

return function(ContentField $translatedStructureField) : TranslatedStructure {
    try {
        return TaxonomyHelper::getTranslatedStructureFromContentField($translatedStructureField);
    } catch(Throwable $e){
        // Return empty structure on error ?
        return new TranslatedStructure([],['parent'=>$translatedStructureField->parent(), 'field'=>$translatedStructureField]);
    }
};