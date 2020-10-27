const CourseCurriculum = {
    sections: function (state) {
        return state.sections || [];
    },
    urlEdit: function (state) {
        return state.urlEdit;
    },
    hiddenSections: function (state) {
        return state.sections
            .filter(function (section) {
                return !section.open;
            })
            .map(function (section) {
                return parseInt(section.id);
            });
    },
    isHiddenAllSections: function (state, getters) {
        var sections = getters['sections'];
        var hiddenSections = getters['hiddenSections'];

        return hiddenSections.length === sections.length;
    },
    statusUpdateSection: function (state) {
        return state.statusUpdateSection;
    },
    statusUpdateSectionItem: function (state) {
        return state.statusUpdateSectionItem;
    }
};

export default CourseCurriculum;