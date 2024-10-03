import { getQuestionId, singleQuestion, singleQuestionOption } from './eventHandlers';

document.addEventListener( 'DOMContentLoaded', () => {
	const questionEditEls = Array.from( document.querySelectorAll( '.js-admin-editor-lp_question' ) );

	if ( questionEditEls.length ) {
		questionEditEls.forEach( ( questionEditEl ) => {
			const questionId = getQuestionId( questionEditEl );
			singleQuestion( questionEditEl, questionId );
		} );
	}

	const questionOptionEls = Array.from( document.querySelectorAll( '.js-question-options' ) );
	if ( questionOptionEls.length ) {
		questionOptionEls.forEach( ( questionOptionEl ) => {
			const questionId = getQuestionId( questionOptionEl );
			singleQuestionOption( questionOptionEl, questionId );
		} );
	}
} );

export { singleQuestion };

