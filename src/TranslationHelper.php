<?php

namespace daandelange\Taxonomy;

use \Daandelange\Helpers\BlueprintHelper;
// use \Daandelange\Helpers\FieldHelper;
use \Kirby\Cms\App;
use \Kirby\Toolkit\Str;
use \Kirby\Toolkit\I18n;
use \Kirby\Cms\ModelWithContent;
use \Kirby\Exception\InvalidArgumentException;
use \Kirby\Content\Field as ContentField;
use \Kirby\Form\Field as FormField;

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

                    // Explicitly translate the field label
                    $fields[$colName]['label']      = I18n::translate($field['label'], $field['label'], $lang->code());
                    // Apply templated label
                    $fields[$colName]['labelorig']  = $fields[$colName]['label'];
                    $fields[$colName]['label']      = Str::template($duplicationLabel, ['field'=>$fields[$colName], 'language'=>$lang], ['fallback' => '-']); // Explicitly add the translation to the field

                    // Apply required placeholder dynamically
                    if(isset($field['required']) && $field['required']==='defaultlang'){
                        $fields[$colName]['required'] = $lang->isDefault();
                    }

                    // Set some extra convenience data
                    $fields[$colName]['istranslatedfield'] = true;
                    $fields[$colName]['isdefaultlang'] = $lang->isDefault();
                    $fields[$colName]['iscurrentlang'] = $lang->is(App::instance()->language());
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

    //
    public static function getDefaultTranslationValueFromContentField(ContentField $field) : mixed {
        $key = $field->key();
        $model = $field->model();
        if(!$model || !($model instanceof ModelWithContent)){
            throw new InvalidArgumentException(message:'The field `'.$key.'` has no model to get the default language translation!');
        }
        return static::getDefaultTranslationValueFromModel($model, $key);
    }

    //
    public static function getDefaultTranslationValueFromFormField(FormField $field) : mixed {
        $name = $field->name()??$field->key(); // Checkme: is this fallback correct ?
        if(!is_string($name) || empty($name)){
            throw new InvalidArgumentException(message:'The field has no key : Can\'t get default translated value !');
        }
        $model = $field->model();
        if(!$model || !($model instanceof ModelWithContent)){
            throw new InvalidArgumentException(message:'The field `'.$name.'` has no model to get the default language translation!');
        }
        return static::getDefaultTranslationValueFromModel($model, $name);
    }

    //
    public static function getDefaultTranslationValueFromModel(ModelWithContent $model, string $fieldKey) : mixed {
        // Exit early ?
        if(!$model->content()->has($fieldKey)){
            throw new InvalidArgumentException(message:'The model has no `'.$fieldKey.'` field !');
        }
        
        $defaultLang = kirby()->defaultLanguage()->code();
        $defaultTranslation = $model->translation($defaultLang);
        if($defaultTranslation->exists()){
            $content = $model->translation($defaultLang)->content();
            if(array_key_exists($fieldKey, $content)){
                return $content[$fieldKey];
            }
        }
        // return no value if unable to fetch from default lang.
        return null;
    }
};
?>