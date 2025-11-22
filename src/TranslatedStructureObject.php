<?php

namespace Daandelange\Taxonomy;

use Kirby\Cms\StructureObject;

class TranslatedStructureObject extends StructureObject {
	// Enable constructing from StructureObject
	// public function __construct(array $params = [])
	// {
	// 	parent::__construct($params);

	// 	$this->content = new Content(
	// 		$params['content'] ?? $params['params'] ?? [],
	// 		$this->parent
	// 	);
	// }

	// public static function fromStructureObject(\Kirby\Cms\StructureObject $other){
	// 	return new self([
	// 		'content'    => $other->content()->toArray(),
	// 		'id'         => $other->id(),
	// 		'parent'     => $other->parent(),
	// 		'structure'  => $this,
	// 	]);
	// }

	// Overridden _call to enable calling $collection->translatedField()
	public function __call(string $method, array $args = []): mixed {

		// Fallback to translated field ?
		if(kirby()->multilang() && !$this->hasField($method)){
            $defaultLang = kirby()->defaultLanguage()->code();
            $langcode = (count($args)>0 && is_string($args[0]) && !empty($args[0])) ? $args[0] : kirby()->language()->code();
			$field_key_in_lang = $method.'_'.$langcode;
            $field_key_in_default = $method.'_'.$defaultLang;
			if($this->hasField($field_key_in_lang) || $this->hasField($field_key_in_default)){
                $translated = $this->content()->get($field_key_in_lang);
                // Fallback to default language
                if($langcode!=$defaultLang && $translated->isEmpty()){
                    $default = $this->content()->get($field_key_in_default);
                    if($default->isNotEmpty()){
                        $translated = $default;
                    }
                }
				return $translated;
			}
		}

		return parent::__call($method, $args);
	}

	// Hasfield
	public function hasField($field) : bool {
		return $this->content()->has($field);
	}
};

?>