<?php

namespace daandelange\Taxonomy;

require_once(__DIR__ . '/../../src/TaxonomyHelper.php');

use \Kirby\Content\Field;
use \Kirby\Option\Options;

//use Kirby\Form\Form;
// return 0;
// return function(\Kirby\Cms\Field $field, string $langCode = null) : UserEntity? {
return function(Field $taxonomyTagsField) : array {
    $value = $taxonomyTagsField->split();
    if(is_array($value) && count($value)>0){
        $binding = TaxonomyHelper::getTaxonomyBindingsFromCmsField($taxonomyTagsField);
        if($binding && array_key_exists('field', $binding) && $query = $binding['field']){
            if(is_string($query) && $model = $taxonomyTagsField->model()){
                if($taxonomyStructure = $model->query($query, '\Kirby\Content\Field')){
                    return $taxonomyStructure->toTaxonomyStructure()->toTags($value);
                }
            }
        }
    }

    return [];
};
