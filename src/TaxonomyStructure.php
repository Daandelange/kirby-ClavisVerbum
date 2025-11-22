<?php

namespace Daandelange\Taxonomy;

use Kirby\Exception\InvalidArgumentException;
use Kirby\Cms\Structure;
use Kirby\Cms\StructureObject;
use Kirby\Cms\App;

use \Daandelange\Helpers\BlueprintHelper;
use \Daandelange\Taxonomy\TranslationHelper;
use Daandelange\Helpers\FieldHelper;
use \Daandelange\Taxonomy\TaxonomyHelper;
// use Kirby\Form\FieldClass;
use \Kirby\Content\Field as ContentField;
use \Daandelange\Taxonomy\TranslatedStructure;

/**
 * TaxonomyCollection is a Structure with a method to .....
 *
 */
class TaxonomyStructure extends TranslatedStructure { // (which extends Collection)

	public function __construct($objects = [], array $options = [])
	{
		// Get minimum required fields
		// Todo: grab `previewLabel` from props ?
        //$options['fields'] = TaxonomyHelper::parseTaxonomyStructureFields($options['fields']??[], '{{ field.label }}');
		parent::__construct($objects, $options);
	}

	// Pre-inject necessary fields
	protected function preparseFields(array $fields):array{
		return TaxonomyHelper::parseTaxonomyStructureFields($fields, '{{ field.label }}');
	}

	/**
	 * The internal setter for collection items.
	 * This makes sure that nothing unexpected ends
	 * up in the collection. You can pass arrays or
	 * StructureObjects
	 *
	 * @param string $id
	 * @param array|StructureObject $props
	 * @return void
	 *
	 * @throws \Kirby\Exception\InvalidArgumentException
	 */
	// public function __set(string $id, $props): void
	// {
	// 	if ($props instanceof TranslatedStructureObject) {
	// 		$object = $props;
	// 	// } else if ($props instanceof StructureObject) {
	// 	// 	$object = TranslatedStructureObject::fromStructure($props);
	// 	} else {
	// 		if (is_array($props) === false) {
	// 			throw new InvalidArgumentException('Invalid structure data');
	// 		}

	// 		$object = new TranslatedStructureObject([
	// 			'content'    => $props,
	// 			'id'         => $props['id'] ?? $id,
	// 			'parent'     => $this->parent,
	// 			'structure'  => $this
	// 		]);
	// 	}

	// 	parent::__set($object->id(), $object);
	// }



	// // Overridden _call to enable calling $collection->translatedField()
	// public function __call(string $key, $arguments)
	// {
	// 	// collection methods
	// 	if ($this->hasMethod($key) === true) {
	// 		return parent::__call($key, $arguments);
	// 	}

	// 	// Translations
	// 	if(kirby()->multilang()){
	// 		$field_in_curlang = $key.'_'.kirby()->language()->code();
	// 		if($this->hasField($field_in_curlang)){
	// 			return $this->{$field_in_curlang}();
	// 		}
	// 	}
	// }

	// Export to tags
	public function toTags() : array {
		return [];
	}
}
