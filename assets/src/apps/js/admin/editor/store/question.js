import getters from '../getters/question';
import mutations from '../mutations/question';
import actions from '../actions/question';

const $ = window.jQuery || jQuery;

const Question = function Question( data ) {
	var state = $.extend( {
		status: 'successful',
		countCurrentRequest: 0,
		i18n: $.extend( {}, data.i18n ),
	}, data.root );

	return {
		state: state,
		getters: getters,
		mutations: mutations,
		actions: actions,
	};
};

export default Question;
