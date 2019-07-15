const Mutations = {
    'TOGGLE': function (state) {
        state.open = !state.open;
    },
    'SET_SECTION': function (state, sectionId) {
        state.sectionId = sectionId;
    },
    'SET_LIST_ITEMS': function (state, items) {
        state.items = items;
    },
    'ADD_ITEM': function (state, item) {
        state.addedItems.push(item);
    },
    'REMOVE_ADDED_ITEM': function (state, item) {
        state.addedItems.forEach(function (_item, index) {
            if (_item.id === item.id) {
                state.addedItems.splice(index, 1);
            }
        });
    },
    'RESET': function (state) {
        state.addedItems = [];
        state.items = [];
    },
    'UPDATE_PAGINATION': function (state, pagination) {
        state.pagination = pagination;
    },
    'SEARCH_ITEMS_REQUEST': function (state) {
        state.status = 'loading';
    },
    'SEARCH_ITEMS_SUCCESS': function (state) {
        state.status = 'successful';
    },
    'SEARCH_ITEMS_FAILURE': function (state) {
        state.status = 'failed';
    }
};

export default Mutations;