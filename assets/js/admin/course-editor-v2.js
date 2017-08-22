;

/**
 * Helpers
 *
 * @since 3.0.0
 */
(function (exports) {
    function cloneObject(object) {
        return JSON.parse(JSON.stringify(object));
    }

    exports.LP_Helpers = {
        cloneObject: cloneObject
    };
})(window);

/**
 * Choose Item Modal Store
 *
 * @since 3.0.0
 *
 * @type {{namespaced, state, getters, mutations, actions}}
 */
var LP_Choose_Items_Modal_Store = (function (exports, Vue, helpers, data) {
    var state = helpers.cloneObject(data.chooseItems);
    state.sectionId = false;
    state.pagination = '';

    var getters = {
        pagination: function (state) {
            return state.pagination;
        },
        items: function (state, _getters) {
            return state.items.filter(function (item) {
                var find = _getters.addedItems.find(function (_item) {
                    return item.id === _item.id;
                });

                return !find;
            });
        },
        addedItems: function (state) {
            return state.addedItems;
        },
        isOpen: function (state) {
            return state.open;
        },
        types: function (state) {
            return state.types;
        },
        section: function () {
            return state.sectionId;
        }
    };

    var mutations = {
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
        'REMOVE_ADDED_ITEM': function (state, index) {
            state.addedItems.splice(index, 1);
        },
        'RESET': function (state) {
            state.addedItems = [];
            state.items = [];
        },
        'UPDATE_PAGINATION': function (state, pagination) {
            state.pagination = pagination;
        }
    };

    var actions = {
        toggle: function (context) {
            context.commit('TOGGLE');
        },

        open: function (context, sectionId) {
            context.commit('SET_SECTION', sectionId);
            context.commit('RESET');
            context.commit('TOGGLE');
        },

        addItem: function (context, item) {
            context.commit('ADD_ITEM', item);
        },

        removeItem: function (context, index) {
            context.commit('REMOVE_ADDED_ITEM', index);
        },

        searchItems: function (context, payload) {
            Vue.http.LPRequest({
                type: 'search-items',
                query: payload.query,
                'item-type': payload.type,
                page: payload.page,
                exclude: JSON.stringify(context.getters.addedItems)
            }).then(
                function (response) {
                    var result = response.body;

                    if (!result.success) {
                        return;
                    }

                    var data = result.data;

                    context.commit('SET_LIST_ITEMS', data.items);
                    context.commit('UPDATE_PAGINATION', data.pagination);
                },
                function (error) {
                    console.error(error);
                }
            );
        },

        addItemsToSection: function (context) {
            var items = context.getters.addedItems;

            if (items.length > 0) {
                Vue.http.LPRequest({
                    type: 'add-items-to-section',
                    'section-id': context.getters.section,
                    items: JSON.stringify(items)
                }).then(
                    function (response) {
                        var result = response.body;

                        if (result.success) {
                            context.commit('TOGGLE');

                            var items = result.data;
                            context.commit('UPDATE_SECTION_ITEMS', {
                                sectionId: context.getters.section,
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

    return {
        namespaced: true,
        state: state,
        getters: getters,
        mutations: mutations,
        actions: actions
    }
})(window, Vue, LP_Helpers, lq_course_editor);

/**
 * Root Store
 *
 * @since 3.0.0
 */
(function (exports, Vue, Vuex, helpers, data) {
    var state = helpers.cloneObject(data.root);

    state.status = 'success';
    state.countCurrentRequest = 0;

    var getters = {
        action: function (state) {
            return state.action;
        },
        sections: function (state) {
            return state.sections || [];
        },
        id: function (state) {
            return state.course_id;
        },
        status: function (state) {
            return state.status || 'error';
        },
        currentRequest: function (state) {
            return state.countCurrentRequest || 0;
        },
        urlEdit: function (state) {
            return state.urlEdit;
        }
    };

    var mutations = {
        'UPDATE_STATUS': function (state, status) {
            state.status = status;
        },
        'INCREASE_NUMBER_REQUEST': function (state) {
            state.countCurrentRequest++;
        },
        'DECREASE_NUMBER_REQUEST': function (state) {
            state.countCurrentRequest--;
        },
        'SORT_SECTION': function (state, orders) {
            state.sections = state.sections.map(function (section) {
                section.order = orders[section.id];

                return section;
            });
        },
        'SET_SECTIONS': function (state, sections) {
            state.sections = sections;
        },
        'ADD_NEW_SECTION': function (state, section) {
            state.sections.push(section);
        },
        'REMOVE_SECTION': function (state, index) {
            state.sections.splice(index, 1);
        },
        'REMOVE_SECTION_ITEM': function (state, payload) {
            var section = state.sections.find(function (section) {
                return (section.id === payload.sectionId);
            });

            var items = section.items || [];
            var index = -1;
            items.forEach(function (item, i) {
                if (item.id === payload.itemId) {
                    index = i;
                }
            });

            if (index !== -1) {
                items.splice(index, 1);
            }
        },
        'UPDATE_SECTION_ITEMS': function (state, payload) {
            var section = state.sections.find(function (section) {
                return parseInt(section.id) === payload.sectionId;
            });

            if (!section) {
                return;
            }
            section.items = payload.items;
        }
    };

    var actions = {
        newRequest: function (context) {
            context.commit('INCREASE_NUMBER_REQUEST');
            context.commit('UPDATE_STATUS', 'loading');
        },

        requestComplete: function (context, status) {
            context.commit('DECREASE_NUMBER_REQUEST');

            if (context.getters.currentRequest === 0) {
                context.commit('UPDATE_STATUS', status);
            }
        },

        addNewSection: function (context, section) {
            Vue.http
                .LPRequest({
                    type: 'new-section',
                    section: section
                })
                .then(
                    function (response) {
                        var result = response.body;

                        if (result.success) {
                            context.commit('ADD_NEW_SECTION', result.data);
                        }
                    },
                    function (error) {
                        console.error(error);
                    }
                );
        },

        removeSection: function (context, payload) {
            context.commit('REMOVE_SECTION', payload.index);

            Vue.http.LPRequest({
                type: 'remove-section',
                'section-id': payload.section.id
            }).then(
                function (response) {
                    var result = response.body;
                },
                function (error) {
                    console.error(error);
                }
            );
        },

        updateSection: function (context, section) {
            Vue.http.LPRequest({
                type: 'update-section',
                section: section
            }).then(
                function (response) {
                    var result = response.body;
                },
                function (error) {
                    console.error(error);
                }
            );
        },

        updateSortSections: function (context, orders) {
            Vue.http
                .LPRequest({
                    type: 'sort-sections',
                    orders: JSON.stringify(orders)
                })
                .then(
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

        syncSections: function (context) {
            Vue.http.LPRequest({type: 'sync-sections'})
                .then(
                    function (response) {
                        var result = response.body;

                        if (result.success && result.data) {
                            context.commit('SET_SECTIONS', result.data);
                        }
                    },
                    function (error) {
                        console.error(error);
                    }
                );
        },

        removeSectionItem: function (context, payload) {
            Vue.http
                .LPRequest({
                    type: 'remove-section-item',
                    'item-id': payload.itemId,
                    'section-id': payload.sectionId
                })
                .then(
                    function (response) {
                        var result = response.body;

                        if (result.success) {
                            context.commit('REMOVE_SECTION_ITEM', payload);
                        }
                    },
                    function (error) {
                        console.error(error);
                    }
                );
        },

        updateSectionItems: function (context, payload) {
            Vue.http
                .LPRequest({
                    type: 'update-section-items',
                    'items': JSON.stringify(payload.items),
                    'section-id': payload.sectionId
                })
                .then(
                    function (response) {
                        var result = response.body;

                        if (result.success) {
                            console.log(result);
                        }
                    },
                    function (error) {
                        console.error(error);
                    }
                );
        },

        updateSectionItem: function(context, payload) {
            Vue.http
                .LPRequest({
                    type: 'update-section-item',
                    'item': payload.item,
                    'section-id': payload.sectionId
                })
                .then(
                    function (response) {
                        var result = response.body;

                        if (result.success) {
                            console.log(result);
                        }
                    },
                    function (error) {
                        console.error(error);
                    }
                );
        }
    };

    exports.LP_Curriculum_Store = new Vuex.Store({
        state: state,
        getters: getters,
        mutations: mutations,
        actions: actions,
        modules: {
            ci: LP_Choose_Items_Modal_Store
        }
    });

})(window, Vue, Vuex, LP_Helpers, lq_course_editor);

/**
 * HTTP
 *
 * @since 3.0.0
 */
(function (exports, Vue, $store) {
    Vue.http.LPRequest = function (payload) {
        payload['nonce'] = $store.state.nonce;
        payload['lp-ajax'] = $store.state.action;
        payload['course-id'] = $store.getters.id;

        return Vue.http.post($store.state.ajax,
            payload,
            {
                emulateJSON: true,
                params: {
                    namespace: 'LPCurriculumRequest'
                }
            });
    };

    Vue.http.interceptors.push(function (request, next) {
        if (request.params['namespace'] !== 'LPCurriculumRequest') {
            next();
            return;
        }

        $store.dispatch('newRequest');

        next(function (response) {
            var body = response.body;
            var result = body.success || false;

            if (result) {
                $store.dispatch('requestComplete', 'success');
            } else {
                $store.dispatch('requestComplete', 'failed');
            }
        });
    });
})(window, Vue, LP_Curriculum_Store);

/**
 * Init app.
 *
 * @since 3.0.0
 */
(function ($, Vue) {
    $(document).ready(function () {
        window.LP_Course_Editor = new Vue({
            el: '#course-editor-v2',
            template: '<lp-course-editor></lp-course-editor>'
        });
    });
})(jQuery, window.Vue);
