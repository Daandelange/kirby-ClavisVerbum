<?php

namespace Daandelange\Taxonomy;

use Kirby\Exception\InvalidArgumentException;
use Kirby\Cms\Structure;
use Kirby\Cms\StructureObject;
use Kirby\Cms\App;

use \Daandelange\Helpers\BlueprintHelper;
use \Daandelange\Taxonomy\TranslationHelper;
use \Daandelange\Helpers\FieldHelper;
use \Daandelange\Taxonomy\TaxonomyStructureObject;
use \Daandelange\Taxonomy\TaxonomyHelper;
// use Kirby\Form\FieldClass;
use \Kirby\Content\Field as ContentField;

/**
 * TaxonomyCollection is a Structure with a method to .....
 *
 */
class TranslatedStructure extends Structure { // (which extends Collection)

	// Define our items
	public const ITEM_CLASS = TranslatedStructureObject::class;
	// public const ITEM_CLASS = StructureObject::class;

	protected array $originalFields;
	protected array $translatedFields;

	// Returns the translated fields
	public function getTransatedFields() : array {
		return array_slice($this->originalFields, 0); // returns an explicit copy to prevent inner modifications
	}
	// Returns the original fields
	public function getOriginalFields() : array {
		return array_slice($this->translatedFields, 0); // returns an explicit copy to prevent inner modifications
	}

	// Let childs override the pre-field-parsing stage
	protected function preparseFields(array $fields):array{
		return $fields;
	}
	
	// Parsefields function : only parses fields when not already set
	protected function parseFields(ContentField $field) : void {
		// Heavy operation. Ensure to only do this once ! (when not cached)
		if(empty($this->originalFields) || empty($this->transatedFields)){

			if(!$field){
				throw new InvalidArgumentException(
					message: 'You must provide a parenting field to TranslatedStructure!'
				);
			}
			if(!($field instanceof ContentField)){
				throw new InvalidArgumentException(message:'The parent field argument is not a field!');
			}

			// Grab field props
			$props = BlueprintHelper::getFieldBlueprint($field, false);
	
			// Check field type and data
			if(empty($props)){
				throw new InvalidArgumentException(message: 'Couldn\'t parse the field\'s blueprint props!');
			}
			if(!array_key_exists('type', $props) || !in_array($props['type'],['structure','taxonomystructure', 'translatedstructure'])){
				throw new InvalidArgumentException('TranslatedStructure can only be made from a translatedstructure field ! Used on a `'.$props['type'].'`.');
			}
			if(!is_array($props['fields'])){
				throw new InvalidArgumentException(message: 'Couldn\'t parse the field\'s `fields` prop ! (expecting an array of fields)');
			}
			
			// Let children (taxonomystructure) do stuff
			$fields = $this->preparseFields($props['fields']);
	
			// Extract fields
			$this->originalFields = BlueprintHelper::expandFieldsProps($fields);
			$this->translatedFields = TranslationHelper::expandTranslateableFields($this->originalFields);
	
			// Sanitize translated fields
			$this->originalFields = TaxonomyHelper::sanitizeTranslatedStructureFieldsProps($this->originalFields??[]);
			$this->translatedFields = TaxonomyHelper::sanitizeTranslatedStructureFieldsProps($this->translatedFields??[]);
		}
	}

	public function __construct($objects = [], array $options = [])
	{
		// Allow creation of null objects (created by Items::factory)
		if(empty($objects) && empty($options)){
			return parent::__construct($objects, $options);
		}

		$parent  = $options['parent'] ?? App::instance()->site();
		$field   = $options['field']  ?? null;

		$this->parseFields($field);

		parent::__construct($objects, $options);
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
	public function __set(string $id, $props): void
	{
		$existingFields = array_keys($this->translatedFields);

		if ($props instanceof TranslatedStructureObject || $props instanceof StructureObject) {
			$object = $props;
			// $props = $props->toArray();
			$sanitizedData = [];
			foreach($object->content()->data() as $key => $value){
				if(in_array($key, $existingFields)){
					$sanitizedData[$key]=$value;
				}
			}
			$object->content()->update($sanitizedData, true);
		// }
		// } else if ($props instanceof StructureObject) {
		// 	$object = TranslatedStructureObject::fromStructure($props);
		} else {
		// {
			if (is_array($props) === false) {
				throw new InvalidArgumentException('Invalid structure data');
			}

			// Limit data to available fields
			// $existingFields = array_column($fields, TaxonomyHelper::$taxonomyStructureKeyFieldName);
			// $existingFields = array_keys($this->translatedFields);

			// Limit data to available fields
			foreach($props as $field => $value){
				if(!in_array($field, $existingFields)){
					unset($props[$field]);
				}
			}
			// Ignore empty rows
			if(empty($props)){
				return;
			}

			$object = new TranslatedStructureObject([
				'content'    => $props,
				'id'         => $props['id'] ?? $id,
				'parent'     => $this->parent,
				'structure'  => $this
			]);
		}

		parent::__set($object->id(), $object);
	}
}
