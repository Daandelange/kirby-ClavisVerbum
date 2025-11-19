<template>
	<!-- Template from TagsField.vue -->
	<k-field
		v-bind="$props"
		:class="['k-tags-field', $attrs.class]"
		:counter="counterOptions"
		:input="id"
		:style="$attrs.style"
	>
		<k-empty v-if="hasNoOptions" :icon="icon" :text="$t('options.none')" />
		<k-input
			v-else
			v-bind="$props"
			:options="computedOptions"
			ref="input"
			type="tags"
			@input="$emit('input', $event)"
			@edit="edit(index, tag, $event)"
		/>

		<!-- Buttons (simplified) from StructureField.vue -->
		<template v-if="hasFields && !disabled" #options>
			<k-button-group layout="collapsed">
				<k-text class="k-text k-help"><p>Taxonomy: </p></k-text>
				<k-button
					:autofocus="autofocus"
					:disabled="!more"
					:responsive="true"
					:text="$t('add')"
					icon="add"
					variant="filled"
					size="xs"
					@click="add()"
				/>
				<!-- Extra "edit original" link -->
				<k-button
					v-if="taxonomyEditUrl && !taxonomyIsOnSameModel"
					:autofocus="false"
					:responsive="true"
					:text="$t('edit')"
					icon="edit"
					variant="filled"
					size="xs"
					@click="$panel.open(taxonomyEditUrl)"
				/>
			</k-button-group>
		</template>
	</k-field>
	
</template>

<script>
import NativeStructureField from "@/components/Forms/Field/StructureField.vue";

// Todo: Snapping features ?
export default {
	name: 'k-taxonomy-tags-field',
	extends: "k-tags-field",
	inheritAttrs: false,
	mixins: [
		{
			// Mixin hack to inject native method, renamed
			methods: { 
				getForm : NativeStructureField.methods.form,
			}
		},
	],
	inheritAttrs: true,
	created(){
		//console.log('created!', this.$refs.input);//.$refs.input);
	},
  	data(){
		return {
			isEditing : false,
			currentIndex: null,
			currentModel: null,
			newOptions: null, // Proxy for serving refreshed options via computed (until page reloads)
			newEntryValue: null,
	  	};
  	},
	props: {
		fields : Object,
		taxonomybindings : Object,
		taxonomyEndpoint : String,
		taxonomyEditUrl : String,
		taxonomyIsOnSameModel : Boolean,
		structureFieldName : String,
		keyfieldname: {
			type: String,
			default: 'id',
		},
	},
	methods: {
		// New k5 methods
		// hasNoOptions(){
		// 	//return more && hasTaxonomyBinding && !disabled && !isEditing;
		// 	return this.options.length === 0 && this.accept === "options";
		// }

		// Own method
		// Adds a new entry and opens the form 
		add(){
			// Fresh data
			this.newEntryValue = this.$helper.field.form(this.fields);
			// Open drawer
			this.open(this.newEntryValue);
		},

		// From StructureField.vue (modified)
		close() {
			this.$panel.drawer.close(this.id+'_drawer');
		},

		// From StructureField.vue (stripped)
		open(item, field=null, replace = false) {

			if (this.disabled === true) {
				return false;
			}

			// Force-enable key field
			this.fields.id.disabled = !this.$helper.string.isEmpty(item[this.keyfieldname??'id']);

			this.$panel.drawer.open({
				component: "k-structure-drawer",
				id: this.id+'_drawer',
				props: {
					icon: this.icon ?? "list-bullet",
					// next: this.items[index + 1],
					// prev: this.items[index - 1],
					tabs: {
						content: {
							fields: this.getForm(null)
						}
					},
					title: this.label,
					value: item
				},
				replace: replace,
				on: {
					submit: (formData) => {
						// Here, we add the tag to the correct model/field via the custom API
						// Then, the tag is added and field.save() is triggered to update the store/content data
						// The tags can then be saved on panel.save();
						// Then we check if other page content need to be updated (the tags struct)

						this.$api.post(
							this.endpoints.field + "/addTag",
							{newTag: formData},
							null,
							true
						).then( (result) => {

							// Valid response ?
							if(result.code==200 && result.status=="success" && result.data && result.data.newTag){
								// Update options
								// Todo: newOptions should be unset when the component reloads ? (with fresh content)
								if(result.data.options) this.newOptions = result.data.options;

								// Append new keyword
								this.$set(this.value, this.value.length, result.data.newTag.value);
								this.$emit('input', this.value);
								
								// Show success message
								this.$panel.notification.open(result);

								// Save
								//this.save();
								this.close();

								// Wait for reload (needed to update the structure data if on the same page)
								//await this.$panel.reload();

								// Update model keywords if on same model
								if(result.data.newStructureContent && this.taxonomyIsOnSameModel){
									// When the source struct is on the same page, it exists in the form-data of the page.
									// The page content needs to be updated not to be erased when the tagsfield change is saved (it would revert to original).
									// Otherwise we don't need to update the content, the page with the taxonomy has been updated
									let structureFieldName = result.data.structureFieldName??this.structureFieldName;
									if(structureFieldName && !this.$helper.string.isEmpty(structureFieldName)){
										// V1: use php/backend value
										//let isOnSameModel = result.data.structureIsOnSameModel ?? structureFieldName;
										// V2: use js/frontend value
										//let isOnSameModel = (this.$attrs["form-data"]?.structureFieldName) ? (this.$attrs["form-data"][structureFieldName]==structureFieldName):false;
										//window.console.log("isOnSameModel=",isOnSameModel, "structureFieldName=", structureFieldName);
										
										this.$panel.content.update({ [structureFieldName]: result.data.newStructureContent});
									}
								}
							}
							else {
								if(response.errors){
									this.$panel.error(response.errors, true);
								}
								else this.$panel.error(result, true);
							}

						} ).catch( (data) => {
							//this.submittingTag = false;
							//console.log('Api EDIT error=', data);
							if(data.details ){
								//this.$panel.error(data, true); // Warning! Stips off error details !
								// If errors : holds validation details per field:
								this.$panel.dialog.open({
									component: "k-error-dialog",
									props: data
								});
							}
							// Any other info, stripped to message only, forwarded to drawer
							else if(data.message){
								this.$panel.error(data, true);
							}
							else {
								// Probably a network error / other
								this.$panel.error({
									message: "There was an error adding the tag !",
									details: error,
								}, true);
							}
							//debugger;
							return false;
						});

						return true;
					},
					// Trash temporary item before it was saved
					remove: () => {
						this.close();
					}
				}
			});
		},

		// When a tag is selected
		edit(index, tag, event){
			window.console.log("Editing!=", index, tag, event);
		},

		// // Override from StructureField.vue (remove simply closes the window as no data was created)
		// remove(item) {
		// 	// this.$panel.dialog.close();
		// 	//this.close();
		// },

		// From StructureField.vue (original)
		hasFields() {
			return this.$helper.object.length(this.fields) > 0;
		},

		// Adds the tag to the tags, only in the GUI
		addTag(tag){
			// Next tick because isEditing got changed just now, $refs.input will be there next tick
			this.$nextTick(() => {
				this.$refs.input?.$refs.input?.addTag(tag);
				this.$refs.input?.$refs.input?.onInput(); // Ensures new values bubble up to the field.
				this.$refs.form?.focus(tag.value);
			});
		},
		
		// Todo: is this still useful ??
		async validate(model) {
			const errors = await this.$api.post(
				this.taxonomyEndpoint + "/validate",
				model
			);

			if (errors.length > 0) {
				throw errors;
			} else {
				return true;
			}
		},
	},
	computed: {
	    hasTaxonomyBinding(){
	      return !!this.taxonomybindings.field;
	    },
		
		/**from StructureField.Vue
		 * Config for the structure form
		 * @returns {Object}
		 */
		form() {
			return this.$helper.field.subfields(this, this.fields);
		},
		/** from StructureField.Vue
		 * Returns if new entries can be added
		 * @returns {bool}
		 */
		more() {
			if (this.disabled === true) {
				return false;
			}

			if (this.max && this.items.length >= this.max) {
				return false;
			}

			return true;
		},
		computedOptions(){
			// Fix tag icon : Kirby uses Image now, but it's still stripped by Options::factory()
			return (this.newOptions??this.options??[]).map(function(opt) {
				// enforce image from icon
				// if((!opt.image) && thisField.$helper.string.isEmpty(opt.icon)){
				if((!opt.image) && !window.panel.app.$helper.string.isEmpty(opt.icon)){
					opt.image = { type: 'icon', icon: opt.icon};
				} 
				return opt;
			});
			//return this.newOptions??this.options; // before ktags icon/image bug
		}
  },
};
</script>

<style lang="scss">

</style>
