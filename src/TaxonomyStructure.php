<?php

namespace Daandelange\Taxonomy;

// use Kirby\Exception\InvalidArgumentException;
// use Kirby\Cms\Structure;
// use Kirby\Cms\StructureObject;
// use Kirby\Cms\App;

// use \Daandelange\Helpers\BlueprintHelper;
// use \Daandelange\Taxonomy\TranslationHelper;
// use Daandelange\Helpers\FieldHelper;
use \Daandelange\Taxonomy\TaxonomyHelper;
// use Kirby\Form\FieldClass;
// use \Kirby\Content\Field as ContentField;
use \Daandelange\Taxonomy\TranslatedStructure;
use Kirby\Field\FieldOptions;
use Kirby\Option\Options;
use \Kirby\Toolkit\Str;
use \Kirby\Toolkit\A;

/**
 * TaxonomyCollection is a Structure with a method to .....
 *
 */
class TaxonomyStructure extends TranslatedStructure { // (which extends Collection)

	// Default template+mapping for transforming into options
	const defaultTagFormat = [
		'value' => '{{ structureItem.id }}',
		'text' => '{{ structureItem.name }}',
		'info' => '{{ structureItem.description }}',
		'icon' => 'tag',
	];

	protected array $tagsBinding;

	// Returns the translated fields
	public function getTagsBinding() : array {
		//return clone $this->tagsBinding; // Not possible on array
		return array_slice($this->tagsBinding, 0); // returns an explicit copy to prevent inner modifications
	}

	public function __construct($objects = [], array $options = [])
	{
		$this->tagsBinding = static::defaultTagFormat;

		// Get minimum required fields
		// Todo: grab `previewLabel` from props ?
        //$options['fields'] = TaxonomyHelper::parseTaxonomyStructureFields($options['fields']??[], '{{ field.label }}');
		parent::__construct($objects, $options);
	}

	// Pre-inject necessary fields
	protected function preparseFields(array $fields):array{
		// Note: fields are untranslated in this stage
		$fields = TaxonomyHelper::parseTaxonomyStructureFields($fields);
		$this->tagsBinding = TaxonomyHelper::parseTagsBindingFromFields($fields);
		return $fields;
	}

	// Export to tags Options
	// If $tags is null, returns all structure entries as options.
	// If $tags is an array, returns structure entries as options that match the IDs in $tags.
	// Uses $format (like tagsbindings) to map structure entries to options.
	// If $format is empty, uses the format parsed from the blueprint fields.
	public function toTags(?array $tags=null, array $format=[]) : array {
		// // Exit early ?
		// if(count($tags)==0) return Options::factory([]);


		// Defaults
		$restricted = null;
		if($tags===null){
			// $tags = [];
			// Use all entries
			$restricted = clone $this;
		}
		else {
			// Use filtered entries
			$restricted = $this->find($tags);
			// Alternative?
			// $restricted = $this->filter(function($item) use($tags) {
			// 	return in_array($item, $tags);
			// });
		}
		// Secure & exit early (`find` can return something else)
		if((!($restricted instanceof self)) || $restricted->isEmpty()){
			return [];//Options::factory([]);
		}

		// Default format ?
		if(count($format)==0){
			$format = $this->tagsBinding;
		}

		// Parse associative array format from KQL (cant create associative arrays: turn combos of 2 values into associative)
		if(!A::isAssociative($format)){
			foreach($format as $key => $queryStr){
				if(is_int($key) && count($queryStr)==2){
					$format[$queryStr[0]] = $queryStr[1];
					unset($format[$key]);
				}
			}
		}

		// Map / transform data ?
		if(!empty($format) && $model=$this->parent()){
			
			// Loop data items (with full access to the TranslatedStructureObject)
			$restricted = array_map(function(TranslatedStructureObject $structureItem) use ($model, $format){
				// Loop format items for the return data
				$queryResult = [];
				array_walk($format, function(string $itemQuery, string $key) use($structureItem, &$queryResult) {
					// Resolve query each entry
					//$queryResult[Str::slug($key)] = $itemQuery;
					$queryResult[Str::slug($key)] = Str::template(
						$itemQuery,
						['structureItem'=>$structureItem, 'item'=>$structureItem],
						['fallback' => $key=='icon'?'':'-'] // Use empty fallback for icon
					);
				});
				return $queryResult;
			}, $restricted->data());

			// Strip keys
			$restricted = array_values($restricted);
		}
		else{
			$restricted = $restricted->toArray();
		}

		$options = Options::factory($restricted);

		// Warning: Options::factory "normalises" `info` and `text` fields, transforming them into arrays. Render flattens them again
		$model = ($this->field()?$this->field()->model():null)??site();
		return $options->render($model);
	}
}
