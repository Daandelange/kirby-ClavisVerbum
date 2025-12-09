import { usePanel } from "kirbyuse";

import TaxonomyStructureField from "./TaxonomyStructureField.vue"
import TaxonomyTagsField from "./TaxonomyTagsField.vue"

const panel = usePanel();
panel.plugin("daandelange/clavisverbum", {
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