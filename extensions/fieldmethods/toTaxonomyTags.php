<?php

namespace daandelange\Taxonomy;

require_once(__DIR__ . '/../../src/TaxonomyHelper.php');

use \Kirby\Content\Field;
use \Kirby\Content\Content;

//use Kirby\Form\Form;

// return function(\Kirby\Cms\Field $field, string $langCode = null) : UserEntity? {
return function(Field $taxonomyTagsField) : Content {
    $data = [];
    //$tagKeys = [];
    $data = TaxonomyHelper::getTagsFromCmsField($taxonomyTagsField)??[];
    return new Content($data, $taxonomyTagsField->parent(), true);
};
