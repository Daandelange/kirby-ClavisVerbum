
import TaxonomyStructureField from "./TaxonomyStructureField.vue"
import TaxonomyTagsField from "./TaxonomyTagsField.vue"
// import KTaxonomyInput from "./TaxonomyInput.vue";

panel.plugin("daandelange/taxonomy", {
  components: {
    'k-structure-field-mixin' : {
      extends: "k-structure-field",
    },
  },
  fields: {
    taxonomystructure: TaxonomyStructureField,
    taxonomytags: TaxonomyTagsField,
  },
});