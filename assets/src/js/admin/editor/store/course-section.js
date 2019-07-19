import Actions from '../actions/course-section';
import Mutations from '../mutations/course-section';
import Getters from '../getters/course-section';

const $ = jQuery;

export default function (data) {
    var state = $.extend({}, data.sections);

    state.statusUpdateSection = {};
    state.statusUpdateSectionItem = {};

    state.sections = state.sections.map(function (section) {
        var hiddenSections = state.hidden_sections;
        var find = hiddenSections.find(function (sectionId) {
            return parseInt(section.id) === parseInt(sectionId);
        });

        section.open = !find;

        return section;
    });

    return {
        namespaced: true,
        state: state,
        getters: Getters,
        mutations: Mutations,
        actions: Actions
    };
}