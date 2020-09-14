const Quiz = {

	UPDATE_HEART_BEAT: function( state, status ) {
		state.heartbeat = !! status;
	},

	UPDATE_STATUS: function( state, status ) {
		state.status = status;
	},

	UPDATE_NEW_QUESTION_TYPE: function( state, type ) {
		state.default_new = type;
	},

	INCREASE_NUMBER_REQUEST: function( state ) {
		state.countCurrentRequest++;
	},

	DECREASE_NUMBER_REQUEST: function( state ) {
		state.countCurrentRequest--;
	},
};

export default Quiz;
