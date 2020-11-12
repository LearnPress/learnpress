const ModalQuizItems = {
	TOGGLE: function( state ) {
		state.open = ! state.open;
	},
	SET_QUIZ: function( state, quizId ) {
		state.quizId = quizId;
	},
	SET_LIST_ITEMS: function( state, items ) {
		state.items = items;
	},
	ADD_ITEM: function( state, item ) {
		state.addedItems.push( item );
	},
	REMOVE_ADDED_ITEM: function( state, item ) {
		state.addedItems.forEach( function( _item, index ) {
			if ( _item.id === item.id ) {
				state.addedItems.splice( index, 1 );
			}
		} );
	},
	RESET: function( state ) {
		state.addedItems = [];
		state.items = [];
	},
	UPDATE_PAGINATION: function( state, pagination ) {
		state.pagination = pagination;
	},
	SEARCH_ITEM_REQUEST: function( state ) {
		state.status = 'loading';
	},
	SEARCH_ITEM_SUCCESS: function( state ) {
		state.status = 'successful';
	},
	SEARCH_ITEM_FAIL: function( state ) {
		state.status = 'fail';
	},
};

export default ModalQuizItems;
