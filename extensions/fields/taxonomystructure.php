<?php

namespace daandelange\Taxonomy;

require_once(__DIR__ . '/../../src/TaxonomyHelper.php');

use Daandelange\Helpers\BlueprintHelper;
use \Kirby\Toolkit\A;

// Import native form blueprint to re-use certain parts that we have to override but can't inherit
$translatedStructureFieldBlueprint = require( __DIR__.'/translatedstructure.php');

return A::append( $translatedStructureFieldBlueprint, [
    // Not needed anymore with append trick above
    //'extends' => 'translatedstructure',

    'methods' => [
        'parseFields' => function(array $userFields) : array {
            // Inject the minimum required fields
            $fields = BlueprintHelper::parseTaxonomyStructureFields($userFields);

            // Expand with translations
            $fields = $this->expandTranslateableFields($fields);

            return $fields;
        },
    ],
    'props' => [
        'type'  => TaxonomyHelper::$taxonomyStructureFieldName,
        // Disable panel batch removal
        'batch' => function (bool $batch = false) {
			return $batch; // checkme : disable batch ?
		},
        // Fallback field when translations are empty
        'translationfallback' => function(string $value=''){
            //if($value=='defaultlang') return $value;
            if(!empty($value)) return $value;
            return TaxonomyHelper::$taxonomyStructureKeyFieldName;// Default = use the id as translation
        },
        // Disable row duplication (panel UI freature)
		'duplicate' => function(bool $value=false){
            return false;
        },
        // Disable drag&drop sorting by default
        'sortable' => function(?bool $value=null){
            return $value??false;
        },
        // Default pagination treshold
        'limit' => function(int $value=-1){
            if( is_int($value) && $value > 0 ) return $value;
            return 20;
        },
        // Default sort column
        'sortBy' => function(string $value='id'){
            return $value;
        },
        // Invert default value
        'spreadlangsoverwidth' => function(bool $value=true){
            return $value;
        },
        // Hide keys field from preview
        'hidepkeyspreview' => function(bool $value=false){
            return $value;
        },
        // Hide alternate languages ?
        'hiddenpreviewfields' => function(array $value=[]){
            // Inject key field ?
            if($this->hidepkeyspreview()===true){
                return A::append($value, [TaxonomyHelper::$taxonomyStructureKeyFieldName]);
            }
            return $value;
        },
        // Allow deleting entries ?
        'allowremove' => function(bool $value=false){
            return $value;
        },
    ],
    'computed' => [
        'type'  => function(){
            return 'taxonomystructure'; // Use our custom field
        },
        'keyfieldname' => function(){
            return TaxonomyHelper::$taxonomyStructureKeyFieldName;
        },
    ],
    
    // Todo: autotranslate toStructure() ? (frontend helper)
    
]);
