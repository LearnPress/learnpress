import { handleEventPopup } from './popupQuiz';
import { addNewQuestion, collapseQuestion, handleActionQuestion, sortableQuestion } from './eventHandlers';

document.addEventListener( 'DOMContentLoaded', () => {
	const quizEditorEl = document.querySelector( '#admin-editor-lp_quiz-refactor' );
	if ( ! quizEditorEl ) {
		return;
	}

	const questionEls = Array.from( quizEditorEl.querySelectorAll( '.question-item' ) );

	if ( questionEls.length ) {
		questionEls.forEach( ( questionEl ) => {
			handleActionQuestion( questionEl, quizEditorEl );
		} );
	}

	collapseQuestion( quizEditorEl );
	addNewQuestion( quizEditorEl );
	sortableQuestion( quizEditorEl );
	handleEventPopup();
} );
