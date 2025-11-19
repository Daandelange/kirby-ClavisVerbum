<?php

namespace daandelange\Taxonomy;

use Kirby\Cms\App;

@include_once __DIR__ . '/vendor/autoload.php';

// Ideas :
// - Default columns : show only primary language ? Option to hide ID ?

return [
    // TranslatedStructure
    // - - - -
    // Show default language fields only in preview columns
    'preview.showDefaultOnly' => true, // bool
    // The label of a field preview. Template args: `field` and `language`.
    'preview.longLabel' => '{{ field.label }} / {{ language.code }}', // string
    // The label of a field preview without language. Template args: `field` and `language`. Only when showDefaultOnly=true.
    'preview.shortLabel' => '{{ field.label }}', // string
    // The label of a newly duplicated field. Template args: `field` and `language`
    'field.duplicationLabel' => '{{ field.label }} / {{ language.name }}',
    
    // TaxonomyStructure
    // - - - -
    // Field names to hide from the preview columns
    //'taxonomystructure.preview.hideKeys' => false, // false | array
];