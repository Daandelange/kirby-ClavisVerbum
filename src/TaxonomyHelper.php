<?php

namespace daandelange\Taxonomy;

use \Kirby\Exception\InvalidArgumentException;
use \Kirby\Exception\LogicException;
use \Kirby\Toolkit\Str;
use \Kirby\Toolkit\A;
use \Kirby\Data\Yaml;
use \Closure;
use \Kirby\Form\Field as FormField;
use \Kirby\Content\Field as ContentField;
use \Kirby\Form\FieldClass as FormFieldClass;
use \Kirby\Cms\Blueprint;
use \Kirby\Cms\Language;
use \Kirby\Cms\Structure;
use \Kirby\Cms\Page;

use \Daandelange\Helpers\BlueprintHelper;
use \Daandelange\Helpers\FieldHelper;

require_once(__DIR__.'/TaxonomyStructure.php');


class TaxonomyItemGetterMode {
    const SANITIZED_TAGS = 1; // Return field items/tags, restricted to available options
    const UNSANITIZED_TAGS = 2; // Return field items, tranformed to tags.
    const MISSING_TAXONOMY = 3; // Return tagfield items missing in the taxonomy structure.
    const TAXONOMY_OPTIONS = 4; // Returns all options from the bound taxonomy structure.
    const MISSING_AND_UPDATED_TAXONOMY = 5; // Like MISSING_TAXONOMY but also includes changed fields.
    //const UNUSED_TAGS = 6; // Return field items. (available for autoselect)
};

class TaxonomyHelper {
    // Field names
    public static string $taxonomyTagsFieldName         = 'taxonomytags';
    public static string $translatedStructureFieldName  = 'translatedstructure';
    public static string $taxonomyStructureFieldName    = 'taxonomystructure';
    public static string $taxonomyStructureKeyFieldName = 'id';
    public static string $taxonomyStructureTextFieldName= 'text';
    public static string $taxonomyBindingsPropName      = 'taxonomybindings';

    // Static var to pass variables between different fields
    private static array $taxonomyFieldsToUpdate        = [];

    // Replica of original Kirby tagsfield.save()
    public static function nativeTagsFieldSave(FormField $tagsField, array|null $value = null) : string {
        return FieldHelper::nativeFieldFunction('tags', 'save', $tagsField, null, $value);
    }

    // Replica of original Kirby structurefield.save()
    public static function nativeStructureFieldSave(FormField $structureField, $value = null) : array {
        return FieldHelper::nativeFieldFunction('structure', 'save', $structureField, null, $value);
    }

    
    // Blueprint parsers / getters
    // public static function getFieldsBlueprintFromFormField(FormField $field) : ?array { // was: getFieldBlueprintsFromCmsField
    //     if($page = $field->model()){
    //         return $page->blueprint()->fields();
    //     }
    //     return null;
    // }

    // public static function getFieldsBlueprintFromCmsField(ContentField $field) : ?array { // was: getFieldBlueprintsFromCmsField
    //     if($page = $field->model()){
    //         return $page->blueprint()->fields();
    //     }
    //     return null;
    // }

    // public static function getFieldBlueprintFromFormField(FormField $field) : ?array {
    //     if($page = $field->model()){
    //         return $page->blueprint()->field($field->key());
    //     }
    //     return null;
    // }

    // Returns the taxonomy binding from a FormField blueprint
    public static function getTaxonomyBindingsFromFormField(FormField $field) : ?array {
        $bindings = FieldHelper::getFieldPropsFromFormField($field, static::$taxonomyBindingsPropName);
        return $bindings?static::parseTaxonomyBindings($bindings, false):null;
    }

    // Returns the taxonomy binding from a ContentField blueprint
    public static function getTaxonomyBindingsFromCmsField(ContentField $field) : ?array {
        $fieldProps = FieldHelper::getFieldPropsFromCmsField($field, static::$taxonomyBindingsPropName);
        return $fieldProps?static::parseTaxonomyBindings($fieldProps, false):null;
    }

    // Returns parsed taxonomy bindings
    public static function parseTaxonomyBindings(array|string $taxonomyBindings, bool $allowThrow = true) : ?array {
        // Kirby style: if string, use as default value for array
        if(is_string($taxonomyBindings)) $taxonomyBindings = ['field'=>$taxonomyBindings];

        // Ensure correct blueprint setup
        if( count($taxonomyBindings) < 1 || !isset($taxonomyBindings['field']) || empty($taxonomyBindings['field']) ){
            if($allowThrow)
                throw new InvalidArgumentException('If using "taxonomybindings", please provide at least a "field" property.');
            else
                return null;
        }
        // Sanitize text and value keys
        if( !isset($taxonomyBindings['textkey'])  || !is_string($taxonomyBindings['textkey' ]) ){
            $taxonomyBindings['textkey']  = taxonomyHelper::$taxonomyStructureTextFieldName;
        }
        if( !isset($taxonomyBindings['valuekey']) || !is_string($taxonomyBindings['valuekey']) ){
            $taxonomyBindings['valuekey'] = taxonomyHelper::$taxonomyStructureKeyFieldName;
        }

        // Todo: Append custom fields and separate text, value fields ?
        // And validate if they exist in the blueprint ?

        return $taxonomyBindings;
    }

    // Formatting utilities
    // - - - -

    // Extracts the text field from the right language
    public static function getTaxonomyTextKeyForLang(array $taxonomyBindings, ?Language $lang=null ): ?string {
        //if(!$lang) $lang = $this->kirby()->defaultLanguage(); // Uses default language
        if(!$lang) $lang = kirby()->language(); // Uses the currently active language by default

        // Fixme: default lang still without `_code` ?
        return ($taxonomyBindings['textkey']??'name').( $lang->isDefault() ? ('') : ('_'.$lang->code()));
    }

    // Gets the unique key field name ('id' by default)
    public static function getTaxonomyValueKey(array $taxonomyBindings) : ?string {
        return $taxonomyBindings['valuekey']??static::$taxonomyStructureKeyFieldName;
    }

    // Returns the field's selected tags as fully expanded & sanitised tags
    // Fixme : this needs some rewriting
    public static function getTagsFromFormField(FormField $tagsFormField, int $returnMode = TaxonomyItemGetterMode::SANITIZED_TAGS, ?array $alternativeTags = null ) : ?array {
        if(!$tagsFormField || $tagsFormField->type() !== TaxonomyHelper::$taxonomyTagsFieldName) return null;
        // Todo: Errored here --> $tagsFormField->value() needs to be $tagsCmsField->value()
        $tags = $tagsFormField->toTags( $tagsFormField->value() );

        // Sanitize tag items ?
        if($tags && is_array($tags)) foreach($tags as $ti => $tag){
            // Ensure keys are lowercase&co ?
            $tags[$ti]['value'] = Str::slug($tags[$ti]['value']);

            // todo: Replace empty values by value from other langs ? (now this is done with the query fetch option fallback to default lang. Maybe need to ensure default lang is filled ?
            if(empty($tags[$ti]['text'])){
                foreach( kirby()->languages()->sortBy('isDefault', 'desc') as $l){

                }
            }

        }

        // Inject alt values ? (used from save() to keep track of the translation)
        $originalTags = null;
        if( $alternativeTags && $returnMode!==TaxonomyItemGetterMode::TAXONOMY_OPTIONS ){
            $originalTags = $tags;//$tagsFormField->toTags( $tagsFormField->model()->{$tagsFormField->name()}()->value() );

            // Get corresponding tags entry
            foreach($alternativeTags as $ati => $altTag){
                $existingEntry = null;
                foreach($tags as $ti => $tag) {
                    if($tag['value'] === Str::slug($altTag['value'])){
                        $existingEntry=$ti;
                        break;
                    }
                }
                // Update existing tag
                if($existingEntry!==null){
                    $tags[$existingEntry]['text'] = $altTag['text'];
                }
                // Add new tags (only in UPDATING mode)
                elseif($returnMode===TaxonomyItemGetterMode::MISSING_AND_UPDATED_TAXONOMY) {
                    $tags[]=['text'=>$altTag['text'],'value'=>Str::slug($altTag['value'])];
                }

            }
        }

        if($tags && is_array($tags)){


            // From here we have sanitized options and tags and we can modify it according to the return needs
            if( $returnMode===TaxonomyItemGetterMode::UNSANITIZED_TAGS ){
                return $tags; // done
            }

            // Other modes depend on options
            $options = $tagsFormField->getOptions();
            if( $returnMode===TaxonomyItemGetterMode::TAXONOMY_OPTIONS ){
                return $options??[];
            }

            $existingKeys = array_column($options, 'value');
            if($options && count($options)>0){
                if( $returnMode===TaxonomyItemGetterMode::MISSING_TAXONOMY ){
                    $tags = array_filter($tags, function($tag) use($existingKeys) {
                        return !in_array($tag['value'], $existingKeys);
                    });
                }
                elseif( $returnMode===TaxonomyItemGetterMode::MISSING_AND_UPDATED_TAXONOMY ){
                    $tags = array_filter($tags, function($tag) use($existingKeys, $originalTags) {
                        // Check changes
                        $changedTags = $originalTags ? array_filter($originalTags, function($originalTag) use($tag) {
                            return $tag['value'] == Str::slug($originalTag['value']) && $tag['text'] != $originalTag['text'];
                        }) : [];
                        return (!in_array($tag['value'], $existingKeys)) || (count($changedTags) > 0);
                    });
                }
                // Default mode in else. Theorically TaxonomyItemGetterMode::SANITIZED_TAGS
                else {
                    $tags = array_filter($tags, function($tag) use($existingKeys) {
                        return in_array($tag['value'], $existingKeys);
                    });
                }
                return $tags;
            }
        }
        return null;
    }

    // Returns the field's selected tags as fully expanded & anitised tags
    public static function getTagsFromCmsField(ContentField $cmsField, int $returnMode = TaxonomyItemGetterMode::SANITIZED_TAGS) : ?array {
        $formField = FieldHelper::getFormFieldFromCmsField($cmsField);
        return $formField ? static::getTagsFromFormField($formField, $returnMode) : null;
    }

    // Custom export-as-tags function for usage in API/Template (loading FormFields is probably quite heavy in CMS namespace, this aims to be a lightweight alternative to the provided panel functions)
    // Cannot be in field.methods because that seems to only be available in the panel, not in the cms/api/template namespace
    public static function getTagsQueryFromCmsField(ContentField $taxonomyStructureCmsField, string $fieldKey='name', ?string $langCode = null) : Structure {
        // Check field type
        // Fixme: Useless as php already checks types ?
        if(!($taxonomyStructureCmsField instanceof ContentField)){
            throw new InvalidArgumentException('getTagsQueryFromCmsField : the field argument #0 is not a Kirby\Content\Field !');
        }
        // Might be unneccessary, but virtual fields are untested, so throw.
        if(!$taxonomyStructureCmsField->exists()){
            throw new InvalidArgumentException("getTagsQueryFromCmsField: The provided field \"".$taxonomyStructureCmsField->name()."\" does not exist in the content and blueprint, it's probably a virtual field ! (unsupported)");
        }

        // Precompute some variables
        $defaultLang = $taxonomyStructureCmsField->parent()->kirby()->defaultLanguage()->code();
        $contentLang = $taxonomyStructureCmsField->parent()->kirby()->language()->code();//$taxonomyStructureCmsField->model()->language()->code(); // tocheck : Maybe the $langCode arg should use this by default ?
        // Set default value to current lang by default or if lang doesn't exist
        if( $langCode==null || !$taxonomyStructureCmsField->parent()->kirby()->languages()->has($langCode) ) $langCode = $contentLang;
        
        // Parse fields
        // $fieldBlueprint = $taxonomyStructureCmsField->getFieldBlueprint(true);
        $fieldBlueprint = BlueprintHelper::getFieldBlueprint($taxonomyStructureCmsField, true);
        if(!isset($fieldBlueprint['fields']) || (isset($fieldBlueprint['type']) && !in_array($fieldBlueprint['type'],['structure','taxonomystructure'])))
            throw new LogicException("Could not parse the fields attr from blueprint ! Is this a structure field ?");
        $fields = $fieldBlueprint['fields']; // Note: unparsed fields, the raw blueprint data, no custom props applied.

        $existingFieldKey = '';
        // Is the field key valid ?
        if( isset($fields[$fieldKey]) ){
            // Field doesn't translate
            if($fields[$fieldKey]['translate']===false){
                if( isset($fields[$fieldKey]) ){
                    $existingFieldKey = $fieldKey;
                }
            }
            // Field translated
            else {
                // Check if there's a translation
                if( true || isset($fields[$fieldKey.'_'.$langCode]) ){ // fixme
                    $existingFieldKey = $fieldKey.'_'.$langCode;
                }
                // Fallback on untranslated for convenience ?
                else if( isset($fields[$fieldKey]) ){
                    $existingFieldKey = $fieldKey;
                }
            }
        }
        // Field not valid
        if($existingFieldKey==''){
            // Fallback on key if available
            if( isset($fields[static::$taxonomyStructureKeyFieldName]) ) $existingFieldKey = $fields[static::$taxonomyStructureKeyFieldName];
            else throw new InvalidArgumentException("getTagsQueryFromCmsField(): The provided structure has no \"".$fieldKey."\" field !");
        }


        // Now $existingFieldKey exists theoretically, move on to content
        $collection = $taxonomyStructureCmsField->toStructure();
        $tags = [];//new Structure();

        foreach( $collection as $tag){ // Loop all available tags
            $key = Str::slug($tag->{taxonomyHelper::$taxonomyStructureKeyFieldName}());
            $value = $tag->{$existingFieldKey}()->value();
            // Provide fallbacks if empty values
            if( empty($value) ){
                // Fallback to default lang ? (per tagitem)
                $test = $tag->{$fieldKey.'_'.$defaultLang}();
                if(isset($fieldBlueprint['translationfallback']) && $fieldBlueprint['translationfallback']=='defaultlang' && $tag->{$fieldKey.'_'.$defaultLang}()->isNotEmpty() ){
                    $value = $tag->{$fieldKey.'_'.$defaultLang}()->value();
                }
                // Fallback on key
                if( empty($value) ){
                    $value = $tag->{TaxonomyHelper::$taxonomyStructureKeyFieldName}();//->value();
                }
            }

            // Append tag
            $tags[] = [ // For the panel tags field to work, we need unkeyed array
                'text' => $value,
                'value' => $key,
                'info' => $tag->{'description'.'_'.$langCode}()->value(), // no works to send it to panel meta...
                'icon' => 'tag' // no works....
            ];
        }

        $structure = Structure::factory($tags, ['parent' => $taxonomyStructureCmsField->model(), 'field' => $taxonomyStructureCmsField]);
        return $structure;//new Structure($tags);
    }

    // Afterpage hook for updating cached field values in the default lang from another lang. To prevent triggering save many times, just stack-up changes and apply them once manually to bypass core translation logic.
    public static function updateTaxonomyFieldsFromCache(Page $page) : bool {
        die("This function `updateTaxonomyFieldsFromCache` isn't ready yet !");
        if(
            kirby()->request()->method()==='PATCH' && // Panel edit request
            !kirby()->language()->isDefault() && // In the default language, regular save() will intercept the value
            count(TaxonomyHelper::$taxonomyFieldsToUpdate) > 0
        ){
            //$page->update( [ $structureFieldName => Yaml::encode($structureValue)], $page->kirby()->defaultLanguage()->code(), false );
            $page->update( TaxonomyHelper::$taxonomyFieldsToUpdate, $page->kirby()->defaultLanguage()->code(), false );
            return true;
        }
        return false;
    }

}
