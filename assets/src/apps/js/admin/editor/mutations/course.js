const Course = {

	UPDATE_HEART_BEAT: function( state, status ) {
		state.heartbeat = !! status;
	},

	UPDATE_AUTO_DRAFT_STATUS: function( state, status ) {
		state.auto_draft = status;
	},

	UPDATE_STATUS: function( state, status ) {
		state.status = status;
	},

	INCREASE_NUMBER_REQUEST: function( state ) {
		state.countCurrentRequest++;
	},

	DECREASE_NUMBER_REQUEST: function( state ) {
		state.countCurrentRequest--;
	},
};

export default Course;
