<?php
namespace daandelange\Taxonomy;

require_once(__DIR__ . '/../../src/TaxonomyHelper.php');

//use Kirby\Form\Form;
use \Throwable;
use \Kirby\Cms\Structure;

return function(\Kirby\Content\Field $taxonomyStructureCmsField, string $fieldKey='name', ?string $langCode = null) : Structure {
    try {
        return TaxonomyHelper::getTagsQueryFromCmsField($taxonomyStructureCmsField, $fieldKey, $langCode );
    } catch(Throwable $e){
        // Return empty structure on error ?
        return new Structure();
    }
};