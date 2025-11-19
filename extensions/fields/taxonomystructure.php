<?php

namespace daandelange\Taxonomy;

require_once(__DIR__ . '/../../src/TaxonomyHelper.php');

//namespace daandelange\BaseKit;

// use \Kirby\Form\Form;
// use \Kirby\Data\Data;
// use \Kirby\Toolkit\I18n;
use \Kirby\Toolkit\A;

// Import native form blueprint to re-use certain parts that we have to override but can't inherit
$translatedStructureFieldBlueprint = require( __DIR__.'/translatedstructure.php');

return A::append( $translatedStructureFieldBlueprint, [
    //'taxonomystructure' => [
    //'extends' => 'translatedstructure',

    'methods' => [

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
        // Auto fields builder : a key and one entry per language
        'fields' => function(array $customFields=[]){ // Default [] = a name field
            // Backup & Delete user key field
            $userKeyField = $customFields[TaxonomyHelper::$taxonomyStructureKeyFieldName] ?? [];
            //unset($customFields[TaxonomyHelper::$taxonomyStructureKeyFieldName]);
            
            // If not provided, prepend ID, otherwise keep user provided position
            if(!array_key_exists(TaxonomyHelper::$taxonomyStructureKeyFieldName, $customFields)){
                $customFields = [TaxonomyHelper::$taxonomyStructureKeyFieldName=>[]] + $customFields;
            }

            // Inject or replace ID field
            $customFields[TaxonomyHelper::$taxonomyStructureKeyFieldName] = [ 
                'type'  => 'slug', // Provides out-of-the box sanitization (UI only!)
                'name'  => TaxonomyHelper::$taxonomyStructureKeyFieldName,
                'label' => $userKeyField['label']??'ID', // Allow changing the ID
                'icon'  => $userKeyField['icon']??'tag', // Allow changing the icon
                'translate' => false, // Never translate the id.
                //'disabled' => true, // Not possible, also disables it in add function !!! Would be nice to disable editing somehow.
                'required' => true, // Always require ID
                'minlength' => $userKeyField['minlength'] ?? 2,
                'maxlength' => $userKeyField['maxlength'] ?? 32,
                'unique' => true, // Custom unique validator flag
                //'sync' => TaxonomyHelper::$taxonomyStructureKeyFieldName.((kirby()->multilang() && kirby()->languages()->count() > 1)?'_'.kirby()->defaultLanguage()->code():''), // Sync slug with default lang title
                'sync' => 'name_fr',//TaxonomyHelper::$taxonomyStructureKeyFieldName.'_'.kirby()->defaultLanguage()->code(), // Sync slug with default lang title
                'mobile' => true, // Always visible on mobile
                //'hidden' => false, // Must be set not to break structurefield.computed.columns
                //'saveable' => true,
                //'default' => '',
                'disabled' => true, // prevent editing
                //'readonly' => true,
            ];

            // Add default "name" field if no fields are provided
            if(count($customFields) <= 1){
                $customFields[TaxonomyHelper::$taxonomyStructureKeyFieldName]=[
                    'type'  => 'text',
                    'name'  => TaxonomyHelper::$taxonomyStructureKeyFieldName,
                    'label' => 'Name',
                    'required' => true, // Custom naming convention, to be documented
                    //'hidden' => false, // Prevents undefined array key "hidden" in native structurefield
                    //'translate' => true,
                ];
            }

            // Expand with translations
            $fields = $this->expandTranslateableFields($customFields);

            // hack trough Kirby's API to ensure every value is set soon enough. It's a way of applying props in the way Kirby does.
            // Sensitive code (might break if Kirby changes internally)
            // Execution order of attr parsing: first blueprint (attrs), then props, then computed.
            // Use either this or this.methods.form, see https://github.com/getkirby/kirby/issues/4107
            $this->fields = $this->attrs['fields'] = $fields;
            return $fields;
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
