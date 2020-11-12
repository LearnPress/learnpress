const Quiz = {
    heartbeat: function (context) {
        LP.Request({
                type: 'heartbeat'
            }
        )
            .then(
                function (response) {
                    var result = response.body;
                    context.commit('UPDATE_HEART_BEAT', !!result.success);
                },
                function (error) {
                    context.commit('UPDATE_HEART_BEAT', false);
                }
            );
    },

    newRequest: function (context) {
        context.commit('INCREASE_NUMBER_REQUEST');
        context.commit('UPDATE_STATUS', 'loading');

        window.onbeforeunload = function () {
            return '';
        }
    },

    requestCompleted: function (context, status) {
        context.commit('DECREASE_NUMBER_REQUEST');

        if (context.getters.currentRequest === 0) {
            context.commit('UPDATE_STATUS', status);
            window.onbeforeunload = null;
        }
    }
};

export default Quiz;