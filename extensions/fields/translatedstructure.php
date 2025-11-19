<?php

namespace daandelange\Taxonomy;

use Daandelange\Helpers\BlueprintHelper;
use \Kirby\Form\Form;
use \Kirby\Data\Data;
use Kirby\Exception\InvalidArgumentException;
use \Kirby\Toolkit\I18n;
use \Kirby\Toolkit\A;

// TODO:
// - [DONE] Force key field to be unique on save / load ?
// - Prevent editing key field (prevents breaking content links)

// Import native form blueprint to re-use certain parts that we have to override but can't inherit
// The structure field and issue 4107 make this field really complex to extend including weird behaviour.
// Example: Using `'extends' => 'structure'` should inherit the whole blueprint.
// But in this plugin, not overriding `methods.rows` manually results in empty value while overriding it with `$nativeStructureFieldBlueprint['methods']['rows']` works. (I expect both to have identical behaviour)
// Note : This was observed in the context of another field that extends this one.
$nativeStructureFieldBlueprint = require( kirby()->root('kirby').'/config/fields/structure.php' );

return A::append( $nativeStructureFieldBlueprint, [
    // Not needed as we use $nativeStructureFieldBlueprint
    //'extends' => 'structure',
    
    'methods' => [
        // Helper for getting only the unique fields (used by the validator)
        'getUniqueFields' => function() : array {
            return array_column(array_filter(
                $this->fields(),
                fn ($field) => (isset($field['unique']) && $field['unique']===true)
            ), 'name');
            // Alternative method :
            // $uniqueFields = [];
            // foreach ($this->fields() as $field) {
            //     // Respect unique flag of blueprint fields. (Custom flag)
            //     if(isset($field['unique']) && $field['unique']===true){
            //         $uniqueFields[] = $field['name'];
            //     }
            // }
            // return $uniqueFields;
        },
        // Backup of the original columns function
        'originalcolumns' => $nativeStructureFieldBlueprint['computed']['columns'],

        // Expands user fields to translated fields
        'expandTranslateableFields' => function(array $customFields) : array {
            $fields = [];

            // Expand the user blueprint with the field-type's defaults
            $customFields = BlueprintHelper::expandFieldsProps($customFields); // get fully expanded props
            $customFields = TranslationHelper::expandTranslateableFields($customFields??[], option('daandelange.taxonomy.field.duplicationLabel', '{{ field.label }} / {{ language.name }}')); // Clone translateable fields
            $fields = BlueprintHelper::sanitizeTranslatedStructureFieldsProps($customFields??[]);

            return $fields;
        },
        // Overrides structure form which grabs the form from $this->attrs['fields'] which isn't set soon enough.
        // Until https://github.com/getkirby/kirby/issues/4107 is merged, and after we'll need this for compatibility with older kirby versions.
        // 'form' => function (array $values = []) { 
        //     $this->form ??= new Form(
		// 		fields: $this->fields ?? $this->attrs['fields'] ?? [], // <-- changed from `$this->attrs['fields']`
		// 		model: $this->model,
		// 		language: 'current'
		// 	);
		// 	return $this->form->reset();
        // },
    ],
    'props' => [
        'type'  => 'translatedstructure',

        // Force enable the field in the panel ? Causes the field to be saved when translate is not set in the blueprint, causing confusion. Don't force on save.
        'disabled' => function(bool $value=false){
            return $value || !$this->kirby()->language()->isDefault();
        },

        // Always disable translations !
        'translate' => false,

        // Fallback field when translations are empty
        'translationfallback' => function(string $value=''){
            if($value=='defaultlang') return $value;
            return $value;
        },
        'spreadlangsoverwidth' => function(bool $value=false){
            return $value;
        },
        // Auto fields builder : a key and one entry per language
        'fields' => function(array $customFields=[]){ // Default [] = a name field
            $fields = $this->expandTranslateableFields($customFields);

            // hack trough Kirby's API to ensure every value is set soon enough. It's a way of applying props in the way Kirby does.
            // Sensitive code (might break if Kirby changes internally)
            // Execution order of attr parsing: first blueprint (attrs), then props, then computed.
            //$this->fields = $this->attrs['fields'] = $fields; // Use either this or this.methods.form, see https://github.com/getkirby/kirby/issues/4107
            $this->props['fields'] = $this->attrs['fields'] = $fields; // Use either this or this.methods.form, see https://github.com/getkirby/kirby/issues/4107
            return $fields;
        },
        // Explicit fields to remove from the preview columns
        'hiddenpreviewfields' => function(array $value=[]){
            return $value;
        },
    ],
    'validations' => [
        // Unique key validator for the field structure field. Only for the UI to prevent duplicates.
        'noduplicates' => function ($values) {
            $uniqueFields = $this->getUniqueFields();
            // Loop fields with "unique" attr
            foreach($uniqueFields as $uniqueField){
                // For each data entry, double-loop to check one against other
                foreach ($values as $key => $value) {
                    foreach ($values as $k => $v) {
                        if($key == $k) continue; // Ignore same field id
                        //if( $value['id'] == $v['id'] ) throw new \Kirby\Exception\InvalidArgumentException( I18n::template('error.validation.noduplicates', 'The key "{key}" already exists.', ['key'=>$value['id']]) );
                        if( $value[$uniqueField] == $v[$uniqueField] ){
                            //return false;
                            throw new \Kirby\Exception\InvalidArgumentException( I18n::template('error.validation.noduplicates', 'The key "{key}" already exists.', ['key'=>$value['id']]) );
                        }
                    }
                }
            }
            return true;
        },
    ],
    'computed' => [
        // Make the panel use the native structure field 
        'type'  => function(){ return 'structure'; },
        //'type'  => 'taxonomystructure', // Use a custom field

        // Filter the native columns
        'columns' => function() {
            // Call native function
            $columns = $this->originalcolumns();
            // Alternative method :
            //$columns = \Closure::fromCallable($nativeStructureFieldBlueprint['computed']['columns'])->call($this);
            
            if(count($this->fields)<=0){
                //throw new InvalidArgumentException('Internal orchestration error in the translatedstructure field.');
                // No fields props: return originalcolumns
                return $columns;
            }

            // Adapt data (filter out fields for preview)
            foreach($columns as $key=>$column){
                // Grab field props
                $field = $this->fields[$key];
                if(is_array($field)){
                    // Remove key preview ?
                    $hideFields = $this->hiddenpreviewfields();//props['hiddenpreviewfields'];
                    if($hideFields !== false && is_array($hideFields) && !empty($hideFields)){
                        if(in_array($field['name'], $hideFields)){
                            unset($columns[$key]);
                            continue;
                        }
                    }

                    if( isset($field['istranslatedfield']) && $field['istranslatedfield']===true ){
                        // Reset field to original label (for usage by template strings)
                        $field['label'] = $field['labelorig']??$field['label'];
                        
                        // Remove alt languages from preview columns
                        if(option('daandelange.taxonomy.preview.showDefaultOnly', true)){
                            if(isset($field['isdefaultlang'])){
                                // Remove col ?
                                if($field['isdefaultlang']===false){
                                    unset($columns[$key]);
                                    continue;
                                }
                                // Remove language from label
                                else {
                                    $columns[$key]['label'] = \Kirby\Toolkit\Str::template(option('daandelange.taxonomy.preview.shortLabel', '{{ field.label }}'), ['field'=>$field, 'language'=>kirby()->language($field['langcode']??null)], ['fallback' => '-']);
                                    continue;
                                }
                            }
                        }

                        // Rename remaining columns
                        $previewLabel = option('daandelange.taxonomy.preview.longLabel', '{{ field.label }} / {{ language.code }}');
                        if(!empty($previewLabel)){
                            $columns[$key]['label'] = \Kirby\Toolkit\Str::template($previewLabel, ['field'=>$field, 'language'=>kirby()->language($field['langcode']??null)], ['fallback' => '-']);
                                continue;
                        }
                    }
                }
            }
            return $columns;
        },
    ],
    'collectionFilters' => [
        // Is this still useful ?
        'removeduplicates' => function ($collection, $field, $uniqueFieldKey = 'id') {
            $existing = [];
            foreach ($collection->data as $key => $item ) {
                if( in_array($collection->{$uniqueFieldKey}(), $existing) ) unset($collection->$key);
                else $existing[] = $collection->{$uniqueFieldKey}();
            }
            return $collection;
        }
    ],
    // Todo: autotranslate toStructure() ?
]);
