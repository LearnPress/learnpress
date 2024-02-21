import getters from '../getters/question-list';
import mutations from '../mutations/question-list';
import actions from '../actions/question-list';

const $ = window.jQuery || jQuery;

const QuestionList = function QuestionList( data ) {
	const listQuestions = data.listQuestions;
	const state = $.extend( {
		statusUpdateQuestions: {},
		statusUpdateQuestionItem: {},
		statusUpdateQuestionAnswer: {},
		questions: listQuestions.questions.map( function( question ) {
			const hiddenQuestions = listQuestions.hidden_questions;
			const ArrQuestionIds = Object.keys( hiddenQuestions );
			const find = ArrQuestionIds.find( function( questionId ) {
				return parseInt( question.id ) === parseInt( questionId );
			} );

			question.open = ! find;

			return question;
		} ),
	}, listQuestions );

	return {
		namespaced: true,
		state,
		getters,
		mutations,
		actions,
	};
};

export default QuestionList;
