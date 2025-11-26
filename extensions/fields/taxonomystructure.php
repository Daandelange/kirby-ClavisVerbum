<?php

namespace daandelange\Taxonomy;

require_once(__DIR__ . '/../../src/TaxonomyHelper.php');

use Daandelange\Helpers\BlueprintHelper;
use \Kirby\Toolkit\A;

// Import native form blueprint to re-use certain parts that we have to override but can't inherit
$translatedStructureFieldBlueprint = require( __DIR__.'/translatedstructure.php');

// Trick to change the order of props&computed
$translatedStructureFieldBlueprint = A::prepend( $translatedStructureFieldBlueprint, [
    'props' => array_fill_keys([
        // Simple props first !
        'hidepkeyspreview', 'allowremove', 'previewShowDefaultOnly', 'previewShowCurrentOnly',
        'previewLabel',
        'spreadlangsoverwidth',

        // // The dependent props
        // 'fields',
        // // And the more heavy ones
        // 'hiddenpreviewfields', 'columns',
        // // Last = values & default
        // 'value', 'default',
    ], null),
    'computed' => array_fill_keys([
        'keyfieldname', 'type',
        //'columns',// put columns last
    ], null),
]);

return A::append( $translatedStructureFieldBlueprint, [
    // Not needed anymore with append trick above
    //'extends' => 'translatedstructure',

    'methods' => [
        'parseFields' => function(array $userFields) : array {
            // Inject the minimum required fields
            $fields = TaxonomyHelper::parseTaxonomyStructureFields($userFields);

            // Expand with translations
            $fields = $this->expandTranslateableFields($fields);

            return $fields;
        },
    ],
    'props' => [
        'type'  => TaxonomyHelper::$taxonomyStructureFieldName,
        // Disable panel batch removal
        'batch' => function (bool $batch = false) : bool {
			return $batch; // checkme : disable batch ?
		},
        // Fallback field when translations are empty (todo???)
        'translationfallback' => function(string $value='') : string {
            //if($value=='defaultlang') return $value;
            if(!empty($value)) return $value;
            return TaxonomyHelper::$taxonomyStructureKeyFieldName;// Default = use the id as translation
        },
        // Disable row duplication (panel UI freature)
		'duplicate' => function(bool $value=false) : bool{
            return false;
        },
        // Disable drag&drop sorting by default
        'sortable' => function(?bool $value=null) : bool {
            return $value??false;
        },
        // Default pagination treshold
        'limit' => function(int $value=-1) : int {
            if( is_int($value) && $value > 0 ) return $value;
            return 20;
        },
        // Default sort column
        'sortBy' => function(string $value='id') : string {
            return $value;
        },
        // Invert default value
        'spreadlangsoverwidth' => function(bool $value=true) : bool {
            return $value;
        },
        // Hide keys field from preview
        'hidepkeyspreview' => function(bool $value=false) : bool {
            return $value;
        },
        // Hide alternate languages ?
        'hiddenpreviewfields' => function(array $value=[]) : array {
            $extraFields=[];
            $showDefaultLangOnly = $this->previewShowDefaultOnly();
            $showCurrentLangOnly = $this->previewShowCurrentOnly();

            // Remove alt languages as per settings
            if($showCurrentLangOnly || $showDefaultLangOnly){
                foreach($this->fields() as $field){
                    if($field['istranslatedfield']==true){
                        if(($showCurrentLangOnly && $field['iscurrentlang']) || ($showDefaultLangOnly && $field['isdefaultlang'])){
                            continue; // ignore = keep field
                        }
                        $extraFields[] = $field['name'];
                    }
                }
            }

            // Inject key field ?
            if($this->hidepkeyspreview()===true){
                // Fixme: rather grab key from taxonomybinding ?
                $extraFields[] = TaxonomyHelper::$taxonomyStructureKeyFieldName;
            }
            return A::append($value, $extraFields);
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
        'tagsBinding' => function(){ // Unused ?
            // Get tagbindings
            return TaxonomyHelper::parseTagsBindingFromFields($this->fields);
        }
    ],
    
    // Todo: autotranslate toStructure() ? (frontend helper)
    
]);
