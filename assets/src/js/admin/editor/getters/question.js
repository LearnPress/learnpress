const Question = {
    id: function (state) {
        return state.id;
    },
    type: function (state) {
        return state.type;
    },
    code: function (state) {
        return Date.now();
    }
    ,
    autoDraft: function (state) {
        return state.auto_draft;
    },
    answers: function (state) {
        return Object.values(state.answers) || [];
    },
    settings: function (state) {
        return state.setting;
    },
    types: function (state) {
        return state.questionTypes || [];
    },
    numberCorrect: function (state) {
        var correct = 0;
        Object.keys(state.answers).forEach(function (key) {
            if (state.answers[key].is_true === 'yes') {
                correct += 1;
            }
        });
        return correct;
    },
    status: function (state) {
        return state.status;
    },
    currentRequest: function (state) {
        return state.countCurrentRequest || 0;
    },
    action: function (state) {
        return state.action;
    },
    nonce: function (state) {
        return state.nonce;
    },
    externalComponent: function (state) {
        return state.externalComponent || [];
    },
    state: function (state) {
        return state;
    },
    i18n: function (state) {
        return state.i18n;
    }
};

export default Question;