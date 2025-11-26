# Clavis Verbum

A duo of 2 fields for managing your multilanguage keywords (and other simple taxonomy).  
_Clavis verbum is the literal latin translation of "key-word"._

Benefits compared to the native structure and tag fields :

- Multilanguage support.
- Centralised taxonomy storage.
- Easily modify your taxonomy.

## Provided

Fields: 

- `translatedstructure` : An extended structure field with translateable entries.  
  All content is stored in the default language and translateable fields are automatically cloned for each language and. 
- `taxonomystructure` : A field for handling your multilanguage keywords and other taxonomy.
- `taxonomytags` : An extended tags field for selecting and creating keywords.

Frontend tools:

- Utilities to sanitize and translate a list of taxonomy.
- Utilities to bind a field to another field.


## Requirements

A Kirby 5 multilanguage website. *Using this plugin in a single language setup is not tested.*
This plugin depends on [daandelange/kirby-helpers](https://github.com/Daandelange/kirby-helpers).

## Install

- Composer :
  `composer require daandelange/clavisverbum`
- GIT :
  `git submodule add https://github.com/Daandelange/kirby-ClavisVerbum.git ./site/plugins/clavisverbum`
  `cd /site/plugins/clavisverbum && composer install`
  `git submodule add https://github.com/Daandelange/kirby-helpers.git ./site/plugins/daans-helpers`
  `cd /site/plugins/daans-helpers && composer install`

- - - - 

## Usage

### Plugin options

You can change the plugin behaviour using the following options :

````php
return [
    // Field names to hide from the preview columns
    'preview.hideFields' => false, // false | array
    // The label of a field preview. Template args: `field` and `language`. Global scope, can be overridden by blueprint
    'preview.label' => '{{ field.label }} / {{ language.code }}',
    // The label of a newly duplicated field. Template args: `field` and `language`. Global scope, can be overridden by blueprint
    'field.duplicationLabel' => '{{ field.label }} / {{ language.name }}',
];
````

- - - -

### Field : Translated Structure

This field tries to solve translation issues often encountered with the native structure field.  
It's simply a structure field with translateable field entries. All content is stored in the default language and translateable fields are automatically cloned for each language.

This field is used by the `taxonomystructure` field, but you can also use it standalone.


#### Blueprint Properties

Blueprint options are inherited from the native structure field, except the following ones :  
Note: _Some defaults have been changed for convenience._

```yml
    # Section fields...
    fields:
        myfield:
            # A translated structure
            type: translatedstructure
            # To validate any unique fields
            validate: noduplicates
            # Field names to remove from the preview column
            hiddenpreviewfields: # default=[]
                - myfieldname_fr # hide a french field
                - myfieldname # hide a non translated field
            # Show all languages of a field on one line ?
            spreadlangsoverwidth: true # default=false
            # Show default language fields only in preview columns.
            previewShowDefaultOnly: true # default=false
            # Show current language fields only in preview columns.
            previewShowCurrentOnly: false # default=true
            # The label of a newly duplicated fields. Template args: `field` and `language`.
            previewLabel: '{{ field.label }} / {{ language.code }}' # default=as-example
            # Your regular fields with extra props
            fields:
                somefield:
                    # Set either to translate this field or not
                    translate: true
                    # The required prop can be set to `defaultlang`
                    # which will require a value only for the default language
                    required: defaultlang # default=false
                    # Don't allow duplicate entries for this field (for the panel field validator on save)
                    unique: true #default=false
                    # Todo:
                    #translationfallback: defaultLang # a field name or '' to use if no translation is available.
                    # The label can be array, static strings or even a translation variable. (see also options.field.duplicationLabel)
                    label:
                        en: Some field
                        fr: Un champ


```

#### Frontend template

You can use `toTranslatedStructure()` to fetch the data; usage is like `toStucture()` but it sanitizes the data, restricting the data fields to the available fields. Also, you can call `$translatedStructureObject->mytranslatedfieldname($lang=null)` to automatically grab the current language.

````php
<?php
$translatedStructure = $translatedStructureField->toTranslatedStructure();
// You can loop the data like this. As a Structure, it's a Kirby Collection.
foreach($translatedStructure as $item){
    // All calls below return a Kirby\Content\Field
    // Empty translations will fallback to the default language translation
    $item->normalfieldname();           // Non-translated value
    $item->mytranslatedfieldname();     // Translated in current lang
    
    $item->mytranslatedfieldname('en'); // explict english translation
    $item->mytranslatedfieldname_en();  // explict english translation (alt)
}

// Or use the data as an array :
$data = $translatedStructure->toArray();

// There's also a way to loop your data fields as defined in the blueprint
$untranslatedFields = $translatedStructure->getTransatedFields(); // Retuns array
foreach($translatedStructure->getTransatedFields() as $id => $field){
    echo(\Kirby\Cms\Html::a('./tags/'.$id, $field['label']));
}
?>
````

Note: You can also use the native `$translatedStructureField->toStucture()` and fetch the translated fields manually.
Note that this doesn't sanitize any data. [More information on the native structure field](https://getkirby.com/docs/reference/panel/fields/structure).

- - - -

### Field: Taxonomy Structure

A multilanguage taxonomy structure field for managing your keywords and other simple taxonomy.
Put this field in a centralised place on your website; in `site.yml` for example.

#### Blueprint properties

Blueprint options are inherited from the `translatedstructure` field, except the following ones :  

````yml
    # In your fields section...
    fields:
        myfield:
            # A taxonomy structure
            type: taxonomystructure
            # Hide the keys from de preview columns
            hidepkeyspreview: true # bool, default=false
            # To allow removing taxonomy entries (not recomended, will break content links)
            allowremove: true # bool, default=false
            # Your regular fields (with some extra props)
            # This is a template but you can add as many fields as you wish
            # For now, it's recommended to use a description + name + id field
            # You may also add extra fields
            fields:
                # The id field is automatically added, but you can change some of its props
                id:
                    label: Unique ID
                name:
                    type: text
                    translate: true
                    required: true
                    # When converting to tags, make this field the text field
                    tagsbinding: text
                    # Custom tabs binding
                    tagsbinding: 
                        text: '{{ structureItem.name }}'
                description:
                    type: text
                    translate: true
````

The `tabsbinding` lets you map structure fields to option fields.
This is automatically set if you use the recommended fields `id`,`name` and `description`. For custom fields, use the extra field prop to set this right.

#### Frontend template

````php
<?php
// Returns a TaxonomyStructure
// Which also is a TranslatedStructure (see usage above)
$taxonomy = $field->toTaxonomyStructure();

// Extra TaxonomyStructure features
$options = $taxonomy->toTags(); // All structure items as a rendered Options array

$tags = ['hello', 'world']; // <-- typically $tagsField->split()
$selection = $taxonomy->toTags($tags); // Selected structure items as a rendered Options array

// The tagsbinding generated from your fields
$tagsbinding = $taxonomy->getTagsBinding(); // Array with field->tag mappings
// It's an array with Options keys and KQL strings with the target value.
// Available arguments: `structureItem`.
$tagsbinding = [
    'value' => '{{ structureItem.id }}',
    'text' => '{{ structureItem.name }}',
    'info' => '{{ structureItem.description }}',
    'icon' => 'tag',
];
$selection = $taxonomy->toTags($tags, $tagsbinding); // Usage with a custom binding
?>
````

You can also use `$taxonomyStructure->toTags()` to feed a native `tags` field (and other fields with options). Also see your fields' `tagsbinding` props for usage with custom fields.

````yml
fields:
    nativetags:
        type: tags
        label: Native Tags
        help: These are populated from a taxonomystructure
        accept: options
        # Method 1 : toTaxonomyTags. (recommended) Uses tagsbinding from blueprint fields.
        options:
          type: query
          query: site.keywordstaxonomy.toTaxonomyStructure.toTags
        # Method 1 : custom toTaxonomyTags (a bit verbose)
        # Note: If you need to provide a custom tags mapping, toTags can be used like this:
        options:
          type: query
          # Note: As we can't build associative arrays from blueprints/KQL, a helper turns this into an associative array
          query: 'site.keywordstaxonomy.toTaxonomyStructure().toTags(null, [ ["value","{{ structureItem.id }}"],["text","{{ structureItem.id }}"],["info","{{ structureItem.id }}"],["icon","tag"] ])',
        # Method 2 : toTaxonomyStructure
        options:
          type: query
          query: site.keywordstaxonomy.toTaxonomyStructure
          value: '{{ structureItem.id }}'
          text: '{{ structureItem.name }}'
          info: '{{ structureItem.description }}'
          icon: tag
        # Method 3: toStructure (and more advanced query strings)
        options:
          type: query
          query: site.keywordstaxonomy.toStructure
          value: '{{ structureItem.id }}'
          text: '{{ structureItem.indexOf }} : {{ structureItem.name_fr }} / {{ structureItem.name_en }}'
          info: '{{ structureItem.description_fr }} / {{ structureItem.description_en }}'
          icon: tag
````


- - - -

### Field: taxonomytags

An extended tags field to select from your centralised `taxonomystructure` field.
It also allows to add new entries to your taxonomy.  
The fields is "bound" to the `taxonomystructure` field using the Kirby query language.

#### Blueprint properties

````yml
fields:
    keywords:
        type: taxonomytags
        label: Keywords
        # The taxonomy binding that links the field to a taxonomystructure
        taxonomybindings:
            # The taxonomystructure target field
            field: site.keywordstaxonomy
````

#### Frontend template

````php
<?php
foreach($field->toTaxonomyTags() as $entry){
    
}
?>
````

- - - -

## Alternatives & Similar

- Vote for [implementing the feature natively on Nolt.io](https://feedback.getkirby.com/146).

- [sylvainjule/Kirby-categories](https://github.com/sylvainjule/kirby-categories) : A similar plugin that uses another approach : Syncs all taxonomy changes down to their respective content files, heavily relying on panel hooks to sync/apply changes.

- [hananils/Kirby-choice](https://github.com/hananils/kirby-choice) : A fontend tool to sanitize data from fields with `options`. (no true multilanguage support)

- [bvdputte/kirby-taxonomy](https://github.com/bvdputte/kirby-taxonomy) : Another taxonomy plugin (probably for older Kirby versions). 

- [lukaskleinschmidt/synced-structure](https://gist.github.com/lukaskleinschmidt/1c0b94ffab51d650b7c7605a4d25c213) : Very simple but powerful translateable structure by syncing on save.

- - - -

## License

[MIT](./LICENSE.md)

Let's make this great together : Specially for commercial usage, try to contribute back some improvements.

## Credits

- [Daan de Lange](https://daandelange.com/)
