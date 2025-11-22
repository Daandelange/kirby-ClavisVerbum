<?php
namespace daandelange\Taxonomy;

@include_once __DIR__ . '/vendor/autoload.php';

use \Throwable;
use \Daandelange\Taxonomy\TaxonomyStructure;
use \Kirby\Content\Field as ContentField;

return function(ContentField $taxonomyStructureField) : TaxonomyStructure {
    try {
        return TaxonomyHelper::getTaxonomyStructureFromContentField($taxonomyStructureField);
    } catch(Throwable $e){
        // Return empty structure on error ?
        return new TaxonomyStructure([],['parent'=>$taxonomyStructureField->model(), 'field'=>$taxonomyStructureField]);
    }
};