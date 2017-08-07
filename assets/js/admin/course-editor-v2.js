;

/**
 * Store
 */
(function (exports, Vue, Vuex, data) {
    var state = data;

    state.status = 'saved';

    var getters = {
        sections: function (state) {
            return state.sections || [];
        },
        id: function (state) {
            return state.course_id;
        },
        status: function () {
            return state.status || 'error';
        }
    };

    var mutations = {
        'SET_STATUS': function (state, status) {
            state.status = status;
        },

        'SET_SECTIONS': function (state, sections) {
            state.sections = sections;
        },
        'ADD_NEW_SECTION': function (state, section) {
            state.sections.push(section);
        },
        'REMOVE_SECTION': function (state, index) {
            state.sections.splice(index, 1);
        }
    };

    var actions = {
        updateStatus: function (context, status) {
            context.commit('SET_STATUS', status);
        },

        addNewSection: function (context) {
            context.commit('ADD_NEW_SECTION', {
                course_id: context.getters.id,
                title: '',
                description: '',
                items: [],
                id: -1
            });
        },
        removeSection: function (context, index) {
            context.commit('REMOVE_SECTION', index);
        },
        updateSections: function (context, sections) {
            Vue.http.post('', {sections: sections, course_id: context.getters.id})
                .then(
                    function (response) {
                        var result = response.body;

                        context.commit('SET_SECTIONS', result.data);
                    },
                    function (error) {
                        console.error(error);
                    }
                );
        }
    };

    /**
     * Vuex Store
     *
     * @type {Store}
     */
    exports.LP_Curriculum_Store = new Vuex.Store({
        state: state,
        getters: getters,
        mutations: mutations,
        actions: actions
    });

})(window, Vue, Vuex, lq_course_editor);


/**
 * HTTP
 */
(function (exports, Vue, $store) {
    Vue.http.options.root = $store.state.ajax;
    Vue.http.options.emulateJSON = true;

    Vue.http.interceptors.push(function (request, next) {
        request.params['lp-ajax'] = $store.state.action;
        request.params['nonce'] = $store.state.nonce;

        $store.dispatch('updateStatus', 'loading');

        next(function (response) {
            var body = response.body;
            var result = body.success || false;

            if (result) {
                $store.dispatch('updateStatus', 'saved');
            } else {
                $store.dispatch('updateStatus', 'error');
            }
        });
    });

})(window, Vue, LP_Curriculum_Store);

/**
 * Init app.
 */
(function ($, Vue) {
    $(document).ready(function () {
        window.LP_Course_Editor = new Vue({
            el: '#course-editor-v2',
            template: '<lp-course-editor></lp-course-editor>'
        });
    });
})(jQuery, window.Vue);
