;(function (exports, Vue, Vuex, data) {
    var state = data;

    var getters = {
        sections: function (state) {
            return state.sections || [];
        },
        id: function (state) {
            return state.course_id;
        }
    };

    var mutations = {
        'ADD_NEW_SECTION': function (state, section) {
            state.sections.push(section);
        },
        'REMOVE_SECTION': function (state, index) {
            state.sections.splice(index, 1);
        }
    };

    var actions = {
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

(function ($, Vue) {
    $(document).ready(function () {
        window.LP_Course_Editor = new Vue({
            el: '#course-editor-v2',
            template: '<lp-course-editor></lp-course-editor>'
        });
    });
})(jQuery, window.Vue);