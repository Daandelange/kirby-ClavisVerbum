<?php

namespace daandelange\Taxonomy;

@include_once __DIR__ . '/vendor/autoload.php';
require_once(__DIR__ . '/../../src/TaxonomyHelper.php');

use \Kirby\Form\Form;
use \Kirby\Toolkit\Str;
use \Kirby\Exception\InvalidArgumentException;
use \Kirby\Field\FieldOptions;
use \Daandelange\Helpers\BlueprintHelper;
use \Daandelange\Helpers\FieldHelper;
use \Kirby\Toolkit\A;

// A tags-field that uses a hidden structure to populate itself

// Sorry for the mess below, this is the best way I found to extend these native fields.
// Load native field
$kr = kirby()->root('kirby');
$nativeStructureFieldBlueprint = require( $kr.'/config/fields/tags.php');
// Prioritize own options
$nativeStructureFieldBlueprint = A::prepend($nativeStructureFieldBlueprint, ['props'=>array_fill_keys([
    // Keys to prepend in a specific order
    TaxonomyHelper::$taxonomyBindingsPropName,
    'boundStructureContentField',
    'boundStructureFormField',
    'options',
    'fields',
], null)]);
// Oh gosh ! --> mixin.props.xyz are always placed before plugin.props.abc
$nativeOptionsMixin = require( $kr.'/config/fields/mixins/options.php');
// Manually mix the tags field
if(array_key_exists('mixins', $nativeStructureFieldBlueprint) && is_array($nativeStructureFieldBlueprint['mixins'])){
    // Remove 
    $nativeStructureFieldBlueprint['mixins']=array_filter($nativeStructureFieldBlueprint['mixins'], function($item){
        return $item!='options';
    });
}
// Mix in options
$nativeStructureFieldBlueprint = A::append($nativeStructureFieldBlueprint, $nativeOptionsMixin);


return A::append($nativeStructureFieldBlueprint, [
    //'extends' => 'tags',
    // !!! Available only on Kirby\Form\Field objects, not in Kirby\Cms\Field
    'methods' => [
        // Override this to parse the options. At least needed to ensure keys are slugs when the content file is manually edited.
        // Note: Called by options.computed.options (in compute stage: converts query options to expanded)
        'getOptions' => function () : array {
            // Original func from config/fields/mixins/options.php
            $props   = FieldOptions::polyfill($this->props); // Standardize options (with old APIs?)
            if(!isset($props['options']['info'])) $props['options']['info']='{{ structureItem.info }}';
            if(!isset($props['options']['icon'])) $props['options']['icon']='tag';
            $options = FieldOptions::factory($props['options']);
            $options = $options->render($this->model());

            foreach($options as $i => $option){
                $options[$i]['value']=Str::slug($options[$i]['value']);
                // if(in_array($options[$i]['value'], $existingValues)) unset($options[$i]); // Rm duplicates
                // else $existingValues[] = $options[$i]['value'];
            }
            return $options;
        },
        // 'getTaxonomyBindingsOld' => function(): ?array {
        //     return TaxonomyHelper::getTaxonomyBindingsFromFormField($this);
        // },
        'getTaxonomyTextKeyForLang' => function(?\Kirby\Cms\Language $lang=null, ?string $baseTextKey=null ): ?string {
            // return TaxonomyHelper::getTaxonomyTextKeyForLang($this->getTaxonomyBindings(), $lang);
            return TaxonomyHelper::getTaxonomyTextKeyForLang(TaxonomyHelper::getTaxonomyBindingsFromFormField($this), $lang);
        },
        'getTaxonomyValueKey' => function() : ?string {
            // return TaxonomyHelper::getTaxonomyValueKey($this->getTaxonomyBindings());
            return TaxonomyHelper::getTaxonomyValueKey(TaxonomyHelper::getTaxonomyBindingsFromFormField($this));
        },

        // Copy of native function, used to fill data
        // Note: first Kirby parses all props+computed, then $field->fill() triggers prop.value and runs all computed again.
        // Note: BUT attrs, methods, options and type cannot be changed during 2nd stage.
        // toValues() is called multiple times during these stages, for default value and filled value (from storage or on panel save)
        'toValues' => function ($value) {
            if (is_null($value) === true) {
                return [];
            }

            if (is_array($value) === false) {
                $value = Str::split($value, $this->separator());
            }

            // Added code block : Prevent duplicate entries in panel too (Vue throws error on duplicate keys)
            $existingValues = [];
            foreach($value as $i => $v){
                if(in_array($v, $existingValues)) unset($value[$i]);
                else $existingValues[] = $v;
            }

            if ($this->accept === 'options') {
                $value = $this->sanitizeOptions($value);
            }

            return $value;
        },
    ],
    'computed' => [
        // Returns the bound taxonomy form, for adding data like the native structure field
        'form' => function() : Form {
            $structureFormField = $this->boundStructureFormField();
            return $structureFormField->form();
        },
        'taxonomyEndpoint' => function() : string {
            $structureFormField = $this->boundStructureContentField();
            return $structureFormField->model()->apiUrl(true).'/fields/'.$structureFormField->key();
        },
        // The panel link to the origina taxonomy structure field
        'taxonomyEditUrl' => function() : string {
            $structureFormField = $this->boundStructureContentField();
            return $structureFormField->model()?->panel()->path()??'';
        },
        // If the taxonomy struct is on the same model (different panel behaviour)
        'taxonomyIsOnSameModel' => function() : bool {
            $structureCmsField = $this->boundStructureContentField();
            return $structureCmsField && ($structureCmsField?->model() == $this->model());
        },
        // todo: remove in favour of reading from the (resolved?) taxonomybinding ! 
        // todo: too late to have this in computed ?
        'structureFieldName' => function() : string {
            $structureCmsField = $this->boundStructureContentField();
            return $structureCmsField?->key()??'';
        },
        'keyfieldname' => function() : string {
            return TaxonomyHelper::$taxonomyStructureKeyFieldName;
        },
    ],
    // Important to have the props in the correct order, so it's fetched first !
    'props' => [
        TaxonomyHelper::$taxonomyBindingsPropName => function (?array $value = null) : ?array {
            // If they are needed earlier, try using : TaxonomyHelper::getTaxonomyBindingsFromFormField($this);
            return TaxonomyHelper::ParseTaxonomyBindings($value);
        },
        'boundStructureContentField' => function($value=null) : ?\Kirby\Content\Field {
            /** @var Kirby/Form/Field $this */
            $taxonomyBinding = $this->taxonomybindings(); // Warning! Requires `taxonomybindings` prop to be inited !
            // $taxonomyBinding = TaxonomyHelper::getTaxonomyBindingsFromFormField($tagsField); // Otherwise use this
            if(!$taxonomyBinding){
                throw new \Exception("Taxonomy bindings empty, please configure the taxonomybinding in your blueprint to enable the Add Tag function.");
            }
            
            $structureCmsField = TaxonomyHelper::resolveTaxonomyBindingField($this->model(), $taxonomyBinding);
            return $structureCmsField;
        },
        'boundStructureFormField' => function($value=null) : ?\Kirby\Form\Field {
            $structureCmsField = $this->boundStructureContentField();
            $structureFormField = FieldHelper::getFormFieldFromCmsField($structureCmsField);
            
            // Secure
            if(!in_array($structureFormField->type(), ['taxonomystructure', 'structure'])){
                throw new \Exception("The taxonomy binding does not point to a structure field !");
            }
            
            return $structureFormField;
        },
        'fields' => function (array $fields=[]) : array { // For structure functionality
            $structureFormField = $this->boundStructureFormField();
            return $structureFormField->fields();
        },
        'type'  => TaxonomyHelper::$taxonomyTagsFieldName,
        // Fixme: options is an overridden prop (first in props order) but needs to be parsed after taxonomybindings !!??
        'options' => function (array|string $options = []) : array {
            // Simply over-ride the value when taxonomybindings are enabled
            $taxonomyBindings = $this->taxonomybindings()??[]; // <-- fixme: called before the prop is ready. Will return the attr value (from user blueprint) instead of the prop'ed value
            $taxonomyOptions = TaxonomyHelper::getFieldOptionsPropsFromTaxonomyBinding($taxonomyBindings);
            if ($taxonomyOptions) {
                return $taxonomyOptions;
            }
            // Fallback to user provided value ?
            return $options;
        },
        'translate' => function (bool $translate = false) : bool {
            // Note: setting translate=false here, the panel seems to still enable it in the panel BUT not allow saving :o
            return false;
        }, // Revert translation to false by default. Might cause attr/props issues ?
        //'disabled' => false, // Enable panel in all languages, so it remains editable. Sent value will be ignored.
        'counter' => function (bool $counter = false) : bool { return $counter; }, // false by default
        'accept' => function (?string $accept = null) : string {
            return 'options';  // Restricts $accept to "options". Todo: change this.
        },
    ],
    // Api endpoint for updating the parent field ?
    'api' => function () {
        $field = $this;
        return [
            [
                'pattern' => 'addTag',
                'method'  => 'ALL',//'POST', // todo: restore post
                'action'  => function () {
                    /** @var Kirby/Cms/Api $this */
                    
                    // Todo: exit early when data is empty ?
                    $tagData = $this->requestBody();
                    if(empty($tagData) || empty($tagData['newTag'])) return [
                        'status'    => 'error',
                        'label'     => 'Data Error',
                        'message'   => 'Cannot add tag without any data!', // todo: translateme !
                        'code'      => 200,
                    ];
                    $tagData = $tagData['newTag'];

                    // Todo: auth / permissions ?
                    // if (kirby()->user() && kirby()->user()->isAdmin()) {

                    // We only update in the default language as the field is never translated
                    $defaultLang = $this->kirby()->defaultLanguage()->code();
                    $currentLang = $this->kirby()->language()->code();
                    if($defaultLang != $currentLang){
                        throw new \Kirby\Exception\LogicException("You can't add tags from another language !");
                    }

                    try {
                        // Grab taxonomy structure binding
                        $structureFormField = $this->field()->boundStructureFormField();
                        
                        if(isset($structureFieldBlueprint['type']) && !in_array($structureFieldBlueprint['type'],[ 'taxonomystructure'])){
                            throw new \LogicException("The resulting query is not a taxonomystructure field !");
                        }

                        // Put received data in form
                        $structureForm = $structureFormField->form()->fill(input: $tagData, passthrough: true);
                    }
                    catch( \Throwable $e ){
                        return [
                            'status'    => 'error',
                            'code'      => 200,
                            'errors'    => [
                                'label'     => 'Parse Error',
                                'message'   => 'There seems to be an issue with your blueprint setup. Are taxonomybindings correctly set ? '.$e->getMessage(),
                            ],
                        ];
                    }

                    // Data is valid ?
                    if($structureForm->isValid()){
                        // Grab normalized data
                        $newTag = $structureForm->content();
                        $structureValues = $structureFormField->value();

                        // Todo:  Check if tag key is free
                        foreach($structureValues as $data){
                            if($data['id']===$newTag['id']) return [
                                'status'    => 'error',
                                'code'      => 200,
                                'errors'    => [
                                    'label'     => 'Duplicate ID',
                                    'message'   => 'The tag ID `'.$data['id'].'`already exists, please choose another one.',
                                ],
                            ];
                        };

                        // Append value
                        $structureValues[] = $newTag;

                        // Update. Note: All translations are saved in default lang until Kirby handles nested field translations better.
                        $updatedPage = $structureFormField->model()->save([ $structureFormField->key() => $structureValues ], $defaultLang, false);

                        // Convert newly added data to tag options
                        // array_values = Ensure to stay an array when transmitted to the panel
                        $newOptions = array_values($updatedPage->{$structureFormField->key()}()->toTaxonomyQuery()->toArray()); 
                        
                        $newTagData = [];
                        foreach($newOptions as $o){
                            if($o['value']==$newTag['id']){
                                $newTagData = $o;
                                break;
                            }
                        }
                        //$modelsAreSame = $structureFormField->model() == $this->field()->model();

                        return [
                            'status'    => 'success',
                            'code'      => 200,
                            'label'     => 'Success',
                            'message'   => "The tag has been added.",
                            'data'      => [
                                'options'               => $newOptions,// TaxonomyHelper::getTagsQueryFromCmsField($taxonomyStructureCmsField, $fieldKey, $langCode ); $structureValues, // Todo: parse them as tagsquery !!!
                                'newTag'                => $newTagData, // Todo: same
                                //'newOptions'=> $newOptions,
                                'newStructureContent'   => $structureValues,
                                // Todo: below move to computed ?
                                //'structureFieldName'    => $modelsAreSame?$structureFormField->key():'', // Save some bandwidth
                                //'structureIsOnSameModel'=> $modelsAreSame, // When both fields are on same page. 
                                //'structureFieldApi'     => 
                            ],
                        ];
                    }
                    // Respond with the form error messages to correct
                    else throw new InvalidArgumentException(
                        fallback: 'The form is not valid, please correct it.',
                        details: $structureForm->errors(),
                    );
                }
            ]
        ];
    },
]);
