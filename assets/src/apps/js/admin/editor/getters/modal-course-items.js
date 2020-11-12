
const Getters = {
	status: function( state ) {
		return state.status;
	},
	pagination: function( state ) {
		return state.pagination;
	},
	items: function( state, _getters ) {
		return state.items.map( function( item ) {
			var find = _getters.addedItems.find( function( _item ) {
				return item.id === _item.id;
			} );

			item.added = !! find;

			return item;
		} );
	},
	addedItems: function( state ) {
		return state.addedItems;
	},
	isOpen: function( state ) {
		return state.open;
	},
	types: function( state ) {
		return state.types;
	},
	section: function( state ) {
		return state.sectionId;
	},
};

export default Getters;
