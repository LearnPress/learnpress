;(function ($, Vue, data) {
    Vue.component('lp-course-editor', {
        template: '#tmpl-lp-course-editor',
        data: function () {
            return data;
        }
    });

    Vue.component('lp-curriculum', {
        template: '#tmpl-lp-course-curriculum',
        props: ['sections']
    });

    Vue.component('lp-list-sections', {
        template: '#tmpl-lp-list-sections',
        props: ['sections'],
        methods: {
            clickNewSection: function () {
                console.log('add new');
            }
        }
    });

    Vue.component('lp-section', {
        template: '#tmpl-lp-section',
        props: ['section'],
        data: function () {
            return {
                isOpen: true
            };
        },
        methods: {
            toggle: function (e) {
                e.preventDefault();
                this.isOpen = !this.isOpen;
            }
        }
    });

    Vue.component('lp-section-item', {
        template: '#tmpl-lp-section-item',
        props: ['item']
    });

    $(document).ready(function () {
        window.LP_Course_Editor = new Vue({
            el: '#course-editor-v2',
            template: '<lp-course-editor></lp-course-editor>'
        });
    });
})(jQuery, window.Vue, lq_course_editor);