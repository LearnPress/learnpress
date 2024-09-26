import lplistAPI from '../../api';
import { handleEventPopup } from '../popupSelectedItem';
import { addNewQuestion, collapseQuestion, handleActionQuestion, handleUpdateItem, restoreSectionState, sortableQuestion } from './eventHandlers';

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
	const API_SEARCH_ITEMS_URL = lplistAPI.admin.apiSearchQuestionItems;
	handleEventPopup( handleUpdateItem, API_SEARCH_ITEMS_URL );
	restoreSectionState( quizEditorEl );
} );
