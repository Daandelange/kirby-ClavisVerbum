<?php

namespace daandelange\Taxonomy;

@include_once __DIR__ . '/vendor/autoload.php';
require_once(__DIR__ . '/../../src/TaxonomyHelper.php');

use \Kirby\Form\Form;
use \Kirby\Toolkit\Str;
use \Kirby\Exception\InvalidArgumentException;
use \Kirby\Field\FieldOptions;
use \Daandelange\Helpers\BlueprintHelper;

// A tags-field that uses a hidden structure to populate itself
// Todo: Since 3.8.2, some sanitising is natively embedded, maybe some code has become obsolete ?
return [
    'extends' => 'tags',
    // !!! Available only on Kirby\Form\Field objects, not in Kirby\Cms\Field
    'methods' => [
        // Override this to parse the options. At least needed to ensure keys are slugs when the content file is manually edited.
        'getOptions' => function () {
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
        'getTaxonomyBindingsOld' => function(): ?array {
            return TaxonomyHelper::getTaxonomyBindingsFromFormField($this);
        },
        'getBoundStructureFieldCms' => function( ) : \Kirby\Content\Field {
            $tagsField = $this; // Form/Field
            $taxonomyBinding = TaxonomyHelper::getTaxonomyBindingsFromFormField($tagsField);
            if(!$taxonomyBinding){
                throw new \Exception("Taxonomy bindings empty, please configure the taxonomybinding in your blueprint to enable the Add Tag function.");
            }
            
            $structureCmsField = $tagsField->model()->query($taxonomyBinding['field']);

            if(!$structureCmsField || $structureCmsField instanceof \Kirby\Content\Field === false || !$structureCmsField->exists()){
                throw new \Kirby\Exception\Exception("The taxonomy binding didn't return a valid structure field. Please correct the binding in the blueprint.");
            }

            return $structureCmsField;
        },
        'getBoundStructureField' => function( ) : \Kirby\Form\Field {
            $structureCmsField = $this->getBoundStructureFieldCms();
            $structureFieldBlueprint = BlueprintHelper::getFieldBlueprint($structureCmsField, true);

            // Secure
            if(!in_array($structureFieldBlueprint['type'], ['taxonomystructure', 'structure'])){
                throw new \Exception("The taxonomy binding does not point to a structure field !");
            }
            
            $structureFieldBlueprint['model'] = $structureCmsField->model();
            $structureFieldBlueprint['value'] = $structureCmsField->value();
            $structureFieldBlueprint['key'] = $structureCmsField->key();

            // WARNING: every call builds a new form field... How to grab it from pages ?
            $structureFormField = \Kirby\Form\Field::factory($structureFieldBlueprint['type'], $structureFieldBlueprint);

            return $structureFormField;
        },
        'getTaxonomyTextKeyForLang' => function(?\Kirby\Cms\Language $lang=null, ?string $baseTextKey=null ): ?string {
            // return TaxonomyHelper::getTaxonomyTextKeyForLang($this->getTaxonomyBindings(), $lang);
            return TaxonomyHelper::getTaxonomyTextKeyForLang(TaxonomyHelper::getTaxonomyBindingsFromFormField($this), $lang);
        },
        'getTaxonomyValueKey' => function() : ?string {
            // return TaxonomyHelper::getTaxonomyValueKey($this->getTaxonomyBindings());
            return TaxonomyHelper::getTaxonomyValueKey(TaxonomyHelper::getTaxonomyBindingsFromFormField($this));
        },

        // Copy of native function, used to fill data
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
        'form' => function(){
            $structureFormField = $this->getBoundStructureField();
            return $structureFormField->form();
        },
        'fields' => function () : array {
            $structureFormField = $this->getBoundStructureField();
            return $structureFormField->fields();
            if (empty($this->fields) === true) {
                throw new \Exception('Please provide some fields for the structure');
            }

            return $this->form()->fields()->toArray();
        },
        'taxonomyEndpoint' => function() : string {
            //$structureFormField = $this;
            $structureFormField = $this->getBoundStructureFieldCms();
            //return "";
            return $structureFormField->model()->apiUrl(true).'/fields/'.$structureFormField->key();
        },
        // The panel link to the origina taxonomy structure field
        'taxonomyEditUrl' => function() : string {
            $structureFormField = $this->getBoundStructureFieldCms();
            return $structureFormField->model()?->panel()->path()??'';
        },
        // If the taxonomy struct is on the same model (different panel behaviour)
        'taxonomyIsOnSameModel' => function() : bool {
            // Note: $this = Form\Field
            $structureCmsField = $this->getBoundStructureFieldCms();
            return $structureCmsField && ($structureCmsField?->model() == $this->model());
        },
        'structureFieldName' => function() : string {
            $structureCmsField = $this->getBoundStructureFieldCms();
            return $structureCmsField?->key()??'';
        },
        'keyfieldname' => function(){
            return TaxonomyHelper::$taxonomyStructureKeyFieldName;
        },
    ],
    'props' => [
        'fields' => function (array $fields=[]) { // For structure functionality
            $structureFormField = $this->getBoundStructureField();
            return $structureFormField->fields();
        },
        'type'  => TaxonomyHelper::$taxonomyTagsFieldName,
        'options' => function ($options = []) {
            // Simply over-ride the value when taxonomybindings are enabled
            // $taxonomyBindings = $this->getTaxonomyBindings();
            $taxonomyBindings = TaxonomyHelper::getTaxonomyBindingsFromFormField($this);
            if ($taxonomyBindings) {
                $taxonomyFieldAddr = $taxonomyBindings['field'];
                $fallbackField = $taxonomyBindings['valuekey'];
                return [
                    'type'  => 'query',
                    'query' => $taxonomyFieldAddr.'.toTaxonomyQuery(\''.$taxonomyBindings['textkey'].'\')',
                    'value' => '{{ structureItem.value }}',
                    'text'  => '{{ structureItem.text }}',
                    'info'  => '{{ structureItem.info }}',
                    'tag'  => '{{ structureItem.tag }}',
                ];
            }
            // Fallback to user value ?
            return $options;
        },
        'translate' => function ($translate = false) {
            // Note: setting translate=false here, the panel seems to still enable it in the panel BUT not allow saving :o
            return false;
        }, // Revert translation to false by default. Might cause attr/props issues ?
        //'disabled' => false, // Enable panel in all languages, so it remains editable. Sent value will be ignored.
        'counter' => function ($counter = false) { return $counter; }, // false by default
        'accept' => function ($accept = 'all') {
            //return 'all'; // All enables writing new categories
            return 'options';  // Restricts $accept to "options". Todo: change this.
        },
        'taxonomybindings' => function (?array $value = null) {
            // If they are needed earlier, try using : TaxonomyHelper::getTaxonomyBindingsFromFormField($this);
            return TaxonomyHelper::ParseTaxonomyBindings($value);
        }
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
                    //$tagData = ['id'=>'testadd','name_fr'=>'Testing Add'];
                    if(empty($tagData) || empty($tagData['newTag'])) return [
                        'status'    => 'error',
                        'label'     => 'Data Error',
                        'message'   => 'Cannot add tag without any data!',
                        'code'      => 200,
                    ];
                    $tagData = $tagData['newTag'];

                    // Todo: auth ?
                    // if (kirby()->user() && kirby()->user()->isAdmin()) {

                    try {
                        // We only update in the default language as the field is never translated
                        $defaultLang = $this->kirby()->defaultLanguage()->code();

                        // Grab taxonomy structure binding
                        $structureFormField = $this->field()->getBoundStructureField();
                        
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
                                    'message'   => "The tag ID already exists, please choose another one.",
                                ],
                            ];
                        };

                        // Append value
                        $structureValues[] = $newTag;

                        // Update. Note: All translations are saved in default lang until Kirby handles nested field translations better.
                        $updatedPage = $structureFormField->model()->save([ $structureFormField->key() => $structureValues ], $defaultLang, false);

                        // Convert newly added data to tag options
                        //$newOptions = \Kirby\Option\OptionsQuery::factory($structureFormField->query())->resolve($structureValues);
                        // array_values = Ensure to stay an array when transmitted to the panel
                        $newOptions = array_values($updatedPage->{$structureFormField->key()}()->toTaxonomyQuery()->toArray()); 
                        
                        $newTagData = [];
                        foreach($newOptions as $o){
                            if($o['value']==$newTag['id']){
                                $newTagData = $o;
                                break;
                            }
                        }
                        $modelsAreSame = $structureFormField->model() == $this->field()->model();

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
];
