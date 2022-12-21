import getters from '../getters/modal-course-items';
import mutations from '../mutations/modal-course-items';
import actions from '../actions/modal-course-items';

const $ = window.jQuery || jQuery;

export default function( data ) {
	var state = $.extend( {}, data.chooseItems );
	state.sectionId = false;
	state.pagination = '';
	state.status = '';

	return {
		namespaced: true,
		state: state,
		getters: getters,
		mutations: mutations,
		actions: actions,
	};
}
