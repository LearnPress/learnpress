const ModalQuizItems = {

    toggle: function (context) {
        context.commit('TOGGLE');
    },

    // open modal
    open: function (context, quizId) {
        context.commit('SET_QUIZ', quizId);
        context.commit('RESET');
        context.commit('TOGGLE');
    },

    // query available question
    searchItems: function (context, payload) {
        context.commit('SEARCH_ITEM_REQUEST');

        LP.Request({
            type: 'search-items',
            query: payload.query,
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
                context.commit('SEARCH_ITEM_SUCCESS');
            },
            function (error) {
                context.commit('SEARCH_ITEMS_FAIL');

                console.log(error);
            }
        );
    },

    // add question
    addItem: function (context, item) {
        context.commit('ADD_ITEM', item);
    },

    // remove question
    removeItem: function (context, index) {
        context.commit('REMOVE_ADDED_ITEM', index);
    },

    addQuestionsToQuiz: function (context, quiz) {
        var items = context.getters.addedItems;
        if (items.length > 0) {
            LP.Request({
                type: 'add-questions-to-quiz',
                items: JSON.stringify(items),
                draft_quiz: JSON.stringify(quiz)
            }).then(
                function (response) {
                    var result = response.body;

                    if (result.success) {
                        var questions = result.data;

                        // update quiz list questions
                        context.commit('lqs/SET_QUESTIONS', questions, {root: true});
                        context.commit('TOGGLE');
                    }
                },
                function (error) {
                    console.log(error);
                }
            )
        }
    }
};

export default ModalQuizItems;