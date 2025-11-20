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


## Requirements

A Kirby 5 multilanguage website. *Using this plugin in a single language setup is not tested.*

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
                - myfieldname
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

For now, you have to use `$field->toStucture()` and fetch the translated fields manually.

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
            fields:
                # The id field is automatically added, but you can change its props
                id:
                    label: Unique ID
                name:
                    type: text
                    translate: true
                    required: true
                description:
                    type: text
                    translate: true
````

#### Frontend template

````php

````

You can also use `$field->toTaxonomyQuery()` to feed a native `tags` field with options.

````yml
fields:
    nativetags:
        type: tags
        label: Native Tags
        help: These are populated from a taxonomystructure
        accept: options
        # Choose either one options prop : toTaxonomyQuery or toStructure
        options:
          type: query
          query: site.keywordstaxonomy.toTaxonomyQuery('name')
          value: '{{ structureItem.value }}'
          text: '{{ structureItem.text }}'
          info: '{{ structureItem.info }}'
          icon: tag
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
            # The text key (default=name)
            textkey: name
            # The id key (optiodefault=id)
            valuekey: id
            # The description key (optional)
            infokey: description # (todo)
````

#### Frontend template

NotYetAvailable: `$field->toTranslatedStructure()` : Sanitize and get the structure in the correct language. Returns a `Kirby/Cms/Structure`.

Note: This plugin is also `$field->toStructure()` compatible, you'll get all the fields from your blueprint including all translations. Note that this doesn't sanitize any data. [More information on the native structure field](https://getkirby.com/docs/reference/panel/fields/structure).

````php
<?php
foreach($field->toTaxonomyStructure() as $entry){
    
}
?>
````

- - - -

## Alternatives & Similar

- Vote for [implementing the feature natively on Nolt.io](https://feedback.getkirby.com/146).

- [sylvainjule/Kirby-categories](https://github.com/sylvainjule/kirby-categories) : A similar plugin that uses another approach : Syncs all taxonomy changes down to their respective content files, heavily relying on panel hooks to sync/apply changes.

- [hananils/Kirby-choice](https://github.com/hananils/kirby-choice) : A fontend tool to sanitize data from fields with `options`. (no multilanguage support)

- [https://github.com/bvdputte/kirby-taxonomy](https://github.com/bvdputte/kirby-taxonomy) : Another taxonomy plugin (probably for older Kirby versions). 

- [lukaskleinschmidt/synced-structure](https://gist.github.com/lukaskleinschmidt/1c0b94ffab51d650b7c7605a4d25c213) : Very simple but powerful translateable structure by syncing on save.

- - - -

## License

[MIT](./LICENSE.md)

Let's make this great together : Specially for commercial usage, try to contribute back some improvements.

## Credits

- [Daan de Lange](https://daandelange.com/)
