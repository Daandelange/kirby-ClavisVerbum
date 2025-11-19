<?php

namespace daandelange\Taxonomy;

use \Daandelange\Helpers\BlueprintHelper;
use \Daandelange\Helpers\FieldHelper;
use \Kirby\Cms\App;


class TranslationHelper {

    // Clones translateable fields: one per language
    public static function expandTranslateableFields(array $customFields, ?string $duplicationLabel=null) : array {
        $fields = [];

        // Expand the user blueprint with the field-type's defaults
        // $customFields = \Kirby\Cms\Blueprint::fieldsProps($fields);
        $customFields = BlueprintHelper::expandFieldsProps($customFields);

        if(!$duplicationLabel || empty($duplicationLabel)){
            $duplicationLabel = '{{ field.label }} / {{ language.name }}';
        }

        // For all fields entry, duplicate for each language
        foreach($customFields as $key => $field){

            // If the field translateable, clone it per language
            if(isset($field['translate']) && $field['translate']===true){
                // Duplicate translateable fields for translation
                $languages = App::instance()->languages()->sortBy('isDefault', 'desc', 'code', 'asc');
                foreach($languages as $lang){
                    $colName = $key.('_'.$lang->code());
                    
                    // Keep all existing user-provided data
                    $fields[$colName]=$field;

                    // Set translation variables
                    $fields[$colName]['translate']  = false; // Never translate as they are now duplicated
                    $fields[$colName]['name']       = $colName; // Must be same as key

                    // Apply templated label
                    if(isset($field['istranslatedfield']) && $field['istranslatedfield']===true){
                        $field['labelorig']  = $field['label'];
                        $field['label']      = \Kirby\Toolkit\Str::template($duplicationLabel, ['field'=>$field, 'language'=>kirby()->language($field['langcode']??null)], ['fallback' => '-']); // Explicitly add the translation to the field
                    }

                    // Apply required placeholder dynamically
                    if(isset($field['required']) && $field['required']==='defaultlang'){
                        $fields[$colName]['required'] = $lang->isDefault();
                    }

                    // Set some extra convenience data
                    $fields[$colName]['istranslatedfield'] = true;
                    $fields[$colName]['isdefaultlang'] = $lang->isDefault();
                    $fields[$colName]['langcode'] = $lang->code();
                }
            }
            else {
                // If the field isn't translateable, just copy it (when not set, assume to translate it)
                $fields[$key]=$field;
                // Enforce translation
                $fields[$key]['translate']=false;
                $fields[$key]['istranslatedfield'] = false;
            }
        }
        return $fields;
    }
};
?>