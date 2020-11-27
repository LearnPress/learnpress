const Quiz = {
	heartbeat: function( state ) {
		return state.heartbeat;
	},
	questionTypes: function( state ) {
		return state.types;
	},
	defaultNewQuestionType: function( state ) {
		return state.default_new;
	},
	action: function( state ) {
		return state.action;
	},
	id: function( state ) {
		return state.quiz_id;
	},
	status: function( state ) {
		return state.status || 'error';
	},
	currentRequest: function( state ) {
		return state.countCurrentRequest || 0;
	},
	nonce: function( state ) {
		return state.nonce;
	},
};

export default Quiz;
