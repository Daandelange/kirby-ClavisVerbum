<script>

import { invertCustomAndNativeFunctions } from 'daans-kirby-helpers/modules';

// Todo: Snapping features ?
export default {
	name: 'k-taxonomystructure-field',
	extends: 'k-structure-field',
	//mixins: ['invertcustomandnativemethods'],
	props: {
		keyfieldname: {
			type: String,
			default: 'id',
		},
		allowremove: {
			type: Boolean,
			default: false,
		},
	},
	//template: "<p>Template: <strong>TextOne</strong><br/>Value: {{ value }}<br/>Label: {{ label }}</p>",
	// computed: {
	//     type: 'text',
	// },
	mounted: function(){
		// console.log('Component Options:', this.$options);
		// const allMixins = this.$options.mixins || [];
		// console.log('Local Mixins:', allMixins);
		// Helper k5:
		// Invert functions so ours are called
		// Note : Important to do this on mounted(), beforeCreate and created() both seem too early, some aren't correctly replaced.
		// this.chooseNative = this.choose; this.choose = this.chooseCustom;
		this.invertCustomAndNativeFunctions([
			//'add',
			'open',
			//'form',
			'options',
			'removeAll',
			'remove',
		]);
		//window.console.log(this.$refs.options);
		//window.console.log("OK!");
	},
	methods: {
		invertCustomAndNativeFunctions,

		// When a form item is opened, make key field editable depending on empty(value)
		// So it can be set on creation, but never changed afterwards
		openCustom(item, field, replace = false){
			//window.console.log("keyfieldname=", this.keyfieldname??'id', item);
			this.fields.id.disabled = !this.$helper.string.isEmpty(item[this.keyfieldname??'id']);
			return this.openNative(item, field, replace);
		},

		// Helper for replacing native methods on mount.
		// Before: Native functions : myFunc,		Custom functions : myFuncCustom.
		// After : Native functions : myFuncNative,	Custom functions : myFuncCustom & myFunc
		// So we replace the native functions, still being able to call them.
		// invertCustomAndNativeFunctions(funcNames){
		// 	for(const fn of funcNames){
		// 	if(true){ // Todo: make debug only ?
		// 		if( !this[fn] ){ // original doesn't exist !
		// 		window.console.log("Native function replacement hack: `"+fn+"` doesn't exist anymore. Please fix me.");
		// 		continue;
		// 		}
		// 		if( !this[fn + 'Custom'] ){ // Target
		// 		window.console.log("Native function replacement hack: `"+fn+"Custom` doesn't exist. Please implement it !");
		// 		continue;
		// 		}
		// 	}
		// 	if(this[fn + 'Native']) continue; // if Native is set, this has already been bound
		// 	this[fn + 'Native'] = this[fn]; this[fn] = this[fn + 'Custom'];
		// 	}
		// },

		// Disable remove functions
		removeCustom(item){ // Bcoz the drawer can still call it.
			if(this.allowremove || this.$helper.string.isEmpty(item[this.keyfieldname??'id'])){ // Allow removing empty key (new entry not yet saved)
				this.removeNative(item);
			}
			// Close the drawer
			else {
				this.close();
			}
		},
		removeAllCustom(){
			if(this.allowremove){
				this.removeAllNative();
			}
		},
		// Dropdown menu action callback
		optionsCustom(option, row){
			if(this.duplicate==false && option=="duplicate"){
				return;
			}
			this.optionNative(option, row);
		},
		// Manual copy of native.computed.options
		optionsNative(){
			if (this.disabled) {
				return [];
			}

			return [
			{
				icon: "edit",
				text: this.$t("edit"),
				click: "edit"
			},
			{
				disabled: !this.duplicate || !this.more,
				icon: "copy",
				text: this.$t("duplicate"),
				click: "duplicate"
			},
			"-",
			{
				icon: "trash",
				text: this.$t("delete"),
				click: "remove"
			}
			];
		},
		
	},
	computed: {
		// Remove duplicate & delete options
		options(){
			let options = this.optionsNative();
			if(!this.duplicate){
				options = options.filter((btn) => btn.icon !== 'copy');
			}
			if(this.allowremove == false){
				options = options.filter((btn) => btn.icon !== 'trash' && btn !== '-');
			}
			return options;
		},
	
	},
};

</script>


<style lang="scss">
// # Hide global actions menu
.k-field-type-taxonomystructure {
	.k-field-header {
		.k-button-group {
			.k-button {
				//display: none;

				// &:first-child {
				// 	display: flex;
				// }
				&:last-of-type {
					display: none;
				}
			}
		}
	}
}
</style>