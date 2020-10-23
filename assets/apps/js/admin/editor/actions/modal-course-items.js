const ModalCourseItems = {

    toggle: function (context) {
        context.commit('TOGGLE');
    },

    open: function (context, sectionId) {
        context.commit('SET_SECTION', sectionId);
        context.commit('RESET');
        context.commit('TOGGLE');
    },

    searchItems: function (context, payload) {
        context.commit('SEARCH_ITEMS_REQUEST');

        LP.Request({
            type: 'search-items',
            query: payload.query,
            item_type: payload.type,
            page: payload.page,
            exclude: JSON.stringify([])
        }).then(
            function (response) {
                var result = response.body;

                if (!result.success) {
                    return;
                }

                var data = result.data;

                context.commit('SET_LIST_ITEMS', data.items);
                context.commit('UPDATE_PAGINATION', data.pagination);
                context.commit('SEARCH_ITEMS_SUCCESS');
            },
            function (error) {
                context.commit('SEARCH_ITEMS_FAILURE');

                console.error(error);
            }
        );
    },

    addItem: function (context, item) {
        context.commit('ADD_ITEM', item);
    },

    removeItem: function (context, index) {
        context.commit('REMOVE_ADDED_ITEM', index);
    },

    addItemsToSection: function (context) {
        var items = context.getters.addedItems;

        if (items.length > 0) {
            LP.Request({
                type: 'add-items-to-section',
                section_id: context.getters.section,
                items: JSON.stringify(items)
            }).then(
                function (response) {
                    var result = response.body;

                    if (result.success) {
                        context.commit('TOGGLE');

                        var items = result.data;
                        context.commit('ss/UPDATE_SECTION_ITEMS', {
                            section_id: context.getters.section,
                            items: items
                        }, {root: true});
                    }
                },
                function (error) {
                    console.error(error);
                }
            );
        }
    }
};

export default ModalCourseItems;