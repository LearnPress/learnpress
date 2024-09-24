import { getQuestionId, singleQuestion } from './eventHandlers';

document.addEventListener( 'DOMContentLoaded', () => {
	const questionEditEls = Array.from( document.querySelectorAll( '.js-admin-editor-lp_question' ) );

	if ( ! questionEditEls.length ) {
		return;
	}

	questionEditEls.forEach( ( questionEditEl ) => {
		const questionId = getQuestionId( questionEditEl );
		singleQuestion( questionEditEl, questionId );
	} );
} );

export { singleQuestion };

