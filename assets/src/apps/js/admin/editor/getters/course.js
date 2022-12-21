const Course = {
	heartbeat: function( state ) {
		return state.heartbeat;
	},
	action: function( state ) {
		return state.action;
	},
	id: function( state ) {
		return state.course_id;
	},
	autoDraft: function( state ) {
		return state.auto_draft;
	},
	disable_curriculum: function( state ) {
		return state.disable_curriculum;
	},
	status: function( state ) {
		return state.status || 'error';
	},
	currentRequest: function( state ) {
		return state.countCurrentRequest || 0;
	},
	urlAjax: function( state ) {
		return state.ajax;
	},
	nonce: function( state ) {
		return state.nonce;
	},
};

export default Course;
