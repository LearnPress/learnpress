const $ = window.jQuery;
const CourseCurriculum = {
    toggleAllSections: function (context) {
        var hidden = context.getters['isHiddenAllSections'];

        if (hidden) {
            context.commit('OPEN_ALL_SECTIONS');
        } else {
            context.commit('CLOSE_ALL_SECTIONS');
        }

        LP.Request({
            type: 'hidden-sections',
            hidden: context.getters['hiddenSections']
        });
    },

    updateSectionsOrder: function (context, order) {
        LP.Request({
            type: 'sort-sections',
            order: JSON.stringify(order)
        }).then(
            function (response) {
                var result = response.body;
                var order_sections = result.data;
                context.commit('SORT_SECTION', order_sections);
            },
            function (error) {
                console.error(error);
            }
        );
    },

    toggleSection: function (context, section) {
        if (section.open) {
            context.commit('CLOSE_SECTION', section);
        } else {
            context.commit('OPEN_SECTION', section);
        }

        LP.Request({
            type: 'hidden-sections',
            hidden: context.getters['hiddenSections']
        });
    },

    updateSection: function (context, section) {
        context.commit('UPDATE_SECTION_REQUEST', section.id);

        LP.Request({
            type: 'update-section',
            section: JSON.stringify(section)
        })
            .then(function () {
                context.commit('UPDATE_SECTION_SUCCESS', section.id);
            })
            .catch(function () {
                context.commit('UPDATE_SECTION_FAILURE', section.id);
            })
    },

    removeSection: function (context, payload) {
        context.commit('REMOVE_SECTION', payload.index);

        LP.Request({
            type: 'remove-section',
            section_id: payload.section.id
        }).then(
            function (response) {
                var result = response.body;
            },
            function (error) {
                console.error(error);
            }
        );
    },

    newSection: function (context, name) {
        var newSection = {
            type: 'new-section',
            section_name: name,
            temp_id: LP.uniqueId()
        }
        context.commit('ADD_NEW_SECTION', {
            id: newSection.temp_id,
            items: [],
            open: false,
            title: newSection.section_name
        });

        LP.Request(newSection).then(
            function (response) {
                var result = response.body;

                if (result.success) {
                    var section = $.extend({}, result.data, {open: true});

                    // update course section
                    context.commit('ADD_NEW_SECTION', section);
                }
            },
            function (error) {
                console.error(error);
            }
        );
    },

    updateSectionItem: function (context, payload) {
        context.commit('UPDATE_SECTION_ITEM_REQUEST', payload.item.id);

        LP.Request({
            type: 'update-section-item',
            section_id: payload.section_id,
            item: JSON.stringify(payload.item)

        }).then(
            function (response) {
                context.commit('UPDATE_SECTION_ITEM_SUCCESS', payload.item.id);

                var result = response.body;
                if (result.success) {
                    var item = result.data;

                    context.commit('UPDATE_SECTION_ITEM', {section_id: payload.section_id, item: item});
                }
            },
            function (error) {
                context.commit('UPDATE_SECTION_ITEM_FAILURE', payload.item.id);
                console.error(error);
            }
        );
    },

    removeSectionItem: function (context, payload) {
        var id = payload.item.id;
        context.commit('REMOVE_SECTION_ITEM', payload);
        payload.item.temp_id = 0;
        LP.Request({
            type: 'remove-section-item',
            section_id: payload.section_id,
            item_id: id
        }).then(
            function () {
                context.commit('REMOVE_SECTION_ITEM', payload);
            }
        );
    },

    deleteSectionItem: function (context, payload) {
        var id = payload.item.id;
        context.commit('REMOVE_SECTION_ITEM', payload);
        payload.item.temp_id = 0;
        LP.Request({
            type: 'delete-section-item',
            section_id: payload.section_id,
            item_id: id
        }).then(
            function () {
                context.commit('REMOVE_SECTION_ITEM', payload);
            }
        );
    },

    newSectionItem: function (context, payload) {

        context.commit('APPEND_EMPTY_ITEM_TO_SECTION', payload)
        //context.commit('UPDATE_SECTION_ITEMS', {section_id: payload.section_id, items: result.data});
        LP.Request({
            type: 'new-section-item',
            section_id: payload.section_id,
            item: JSON.stringify(payload.item)
        }).then(
            function (response) {
                var result = response.body;

                if (result.success) {
                    // context.commit('UPDATE_SECTION_ITEMS', {section_id: payload.section_id, items: result.data});
                    var items = {};
                    $.each(result.data, function (i, a) {
                        items[a.old_id ? a.old_id : a.id] = a;
                    });
                    context.commit('UPDATE_ITEM_SECTION_BY_ID', {
                        section_id: payload.section_id,
                        items: items
                    });
                }
            },
            function (error) {
                console.error(error);
            }
        );
    },

    updateSectionItems: function ({state}, payload) {
        LP.Request({
            type: 'update-section-items',
            section_id: payload.section_id,
            items: JSON.stringify(payload.items),
            last_section: state.sections[state.sections.length - 1] === (payload.section_id)
        }).then(
            function (response) {
                var result = response.body;

                if (result.success) {
                    // console.log(result);
                }
            },
            function (error) {
                console.error(error);
            }
        );
    }
};

export default CourseCurriculum;