
import lplistAPI from '../../api';
import { updateStatus } from '../question/apiRequests';
import { lpFetchAPI } from '../../utils';
import { renderQuestion, resetQuestionOrder, updateTotalItem } from './eventHandlers';
import { resetPopup } from '../popupSelectedItem';

const apiRequest = ( url, method = 'POST', data, callbacks = {}, el ) => {
	if ( ! url ) {
		return;
	}

	let params = {
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': lpDataAdmin.nonce,
		},
		method,
		body: JSON.stringify( data ),

	};

	if ( method === 'GET' ) {
		params = {};
	}

	updateStatus( 'loading', el );
	const { success, error, completed } = callbacks;
	lpFetchAPI( url, params, {
		success,
		error,
		completed: () => {
			if ( completed ) {
				completed();
			}
			updateStatus( 'success', el );
		},
	} );
};

const changeQuestionTitleApi = ( data, el ) => {
	const URL = lplistAPI.admin.apiChangeQuestionTitle;
	apiRequest( URL, 'POST', data, {}, el );
};

const removeQuestionApi = ( data, el ) => {
	const URL = lplistAPI.admin.apiRemoveQuestion;
	apiRequest( URL, 'POST', data, {}, el );
};

const deleteQuestionApi = ( data, el ) => {
	const URL = lplistAPI.admin.apiDeleteQuestion;
	apiRequest( URL, 'POST', data, {}, el );
};

const duplicateQuestionApi = ( data, el ) => {
	const URL = lplistAPI.admin.apiDuplicateQuestion;
	const callback = {
		success: ( response ) => {
			const htmls = response.data.html;
			if ( htmls.length ) {
				for ( const html of htmls ) {
					renderQuestion( html, el );
				}
			}
		},
	};
	apiRequest( URL, 'POST', data, callback, el );
};

const addNewQuestionApi = ( data, el ) => {
	const URL = lplistAPI.admin.apiAddNewQuestion;
	const callback = {
		success: ( response ) => {
			const htmls = response.data.html;
			if ( htmls.length ) {
				for ( const html of htmls ) {
					renderQuestion( html, el );
				}
			}
		},
	};

	apiRequest( URL, 'POST', data, callback, el );
};

const sortQuestionApi = ( data, el ) => {
	const URL = lplistAPI.admin.apiSortQuestion;
	apiRequest( URL, 'POST', data, {}, el );
};

const addQuestionToQuizApi = ( data, listUiSortableEl, popupModalSelectItemEl ) => {
	if ( ! data || ! popupModalSelectItemEl ) {
		return;
	}

	const quizEditorEl = document.querySelector( '#admin-editor-lp_quiz-refactor' );

	const URL = lplistAPI.admin.apiAddQuestionsToQuiz;
	const callback = {
		success: ( response ) => {
			const itemEls = response?.data?.html ?? [];
			if ( itemEls.length && listUiSortableEl ) {
				itemEls.map( ( itemEl ) => {
					renderQuestion( itemEl, quizEditorEl );
				} );

				if ( quizEditorEl ) {
					updateTotalItem( quizEditorEl, itemEls.length );
					resetQuestionOrder( quizEditorEl );
				}
			}
		},
		error: ( err ) => {
		// console.log( err );
		},
		completed: () => {
			resetPopup( popupModalSelectItemEl );
		},
	};
	apiRequest( URL, 'POST', data, callback, quizEditorEl );
};

export { apiRequest, changeQuestionTitleApi, removeQuestionApi, deleteQuestionApi, duplicateQuestionApi, addNewQuestionApi, sortQuestionApi, addQuestionToQuizApi };
