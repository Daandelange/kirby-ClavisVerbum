<?php

namespace daandelange\Taxonomy;

use Kirby\Cms\App;
use \Kirby\Content\Field as ContentField;

@include_once __DIR__ . '/vendor/autoload.php';


require_once(__DIR__ . '/src/TaxonomyHelper.php');
require_once(__DIR__ . '/src/TranslationHelper.php');

const translatedStructureFieldName  = 'translatedstructure';

App::plugin('daandelange/taxonomy', [
    'options'      => require __DIR__.'/extensions/options.php',

    'fields' => [
        
        // A tags-field that uses a hidden structure to populate itself
        //'taxonomy' => 'Daandelange\BaseKit\TaxonomyField',
        // Todo: Since 3.8.2, some sanitising is natively embedded, maybe some code has become obsolete ?
        TaxonomyHelper::$taxonomyTagsFieldName => require __DIR__.'/extensions/fields/taxonomytags.php',

        // A structure field that watched another field for saving
        TaxonomyHelper::$taxonomyStructureFieldName => require __DIR__.'/extensions/fields/taxonomystructure.php',
        TaxonomyHelper::$translatedStructureFieldName => require __DIR__.'/extensions/fields/translatedstructure.php',
    ],

    'fieldMethods' => [
        // Easily parse tags from the frontend !
        'toTaxonomy' => function(ContentField $tagsField/*, bool $returnAllOptions = false*/) : ?array {
            $tags = TaxonomyHelper::getTagsFromCmsField($tagsField, /*$returnAllOptions ? TaxonomyItemGetterMode::TAXONOMY_OPTIONS :*/ TaxonomyItemGetterMode::SANITIZED_TAGS );
            return $tags;
        },
        // Custom export-as-tags function for usage in API/Template
        // Cannot be in field.methods because that seems to only be available in the panel, not in the cms/api/template namespace
        'toTaxonomyQuery' => require __DIR__.'/extensions/fieldmethods/toTaxonomyQuery.php',
        'toTranslatedStruct' => require __DIR__.'/extensions/fieldmethods/toTaxonomyQuery.php',
    ],

    // Translations
    'translations' => [
        'en' => require __DIR__.'/extensions/translations/en.php',
        'fr' => require __DIR__.'/extensions/translations/fr.php',
    ],
]);
