import lplistAPI from '../../api';
import { lpFetchAPI } from '../../utils';
import { addNewAnswer, changeCorrectAnswer, changeTitleAnswer, checkHiddenRemoveAnswer, deleteAnswer, sortableAnswer } from './eventHandlers';
import { changeContentAnswer, checkDisableAllAction, clearContent, insertNewBlank, removeAllBlank, renderBlank } from './fillBlank';

const abortControllers = {};

const updateStatus = ( status, questionEditEl ) => {
	if ( ! questionEditEl || ! status ) {
		return;
	}

	const statusEl = questionEditEl.querySelector( '.lp-box-data-head .status' );
	if ( statusEl ) {
		statusEl.classList.remove( 'loading', 'success' );
		statusEl.classList.add( status );
	}
};

const renderContent = ( el, html ) => {
	if ( ! el, ! html ) {
		return;
	}

	const outputEl = el.querySelector( '.lp-box-data-content' );

	outputEl.innerHTML = '';
	outputEl.insertAdjacentHTML( 'beforeend', html );
	addNewAnswer( el );
	deleteAnswer( el );
	checkHiddenRemoveAnswer( el );
	changeCorrectAnswer( el );
	changeTitleAnswer( el );
	sortableAnswer( el );
	insertNewBlank( el );
	renderBlank( el );
	checkDisableAllAction( el );
	removeAllBlank( el );
	clearContent( el );
	changeContentAnswer( el );
};

const renderOption = ( el, option ) => {
	if ( ! option || ! el ) {
		return;
	}

	const placeholderEl = el.querySelector( '.lp-place-holder' );
	const descEl = el.querySelector( '.question-description' );
	const pointEl = el.querySelector( '.question-points' );
	const hintEl = el.querySelector( '.question-hint' );
	const explanationEl = el.querySelector( '.question-explanation' );
	const contentEl = el.querySelector( '.postbox' );
	const toggleEl = el.querySelector( '.toggle' );

	if ( placeholderEl ) {
		placeholderEl.remove();
	}

	if ( toggleEl ) {
		toggleEl.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			el.classList.toggle( 'closed' );
		} );
	}

	if ( contentEl ) {
		contentEl.style.display = 'block';
	}

	if ( descEl ) {
		descEl.value = option.description;
	}

	if ( pointEl ) {
		pointEl.value = option.points;
	}

	if ( hintEl ) {
		hintEl.value = option.hint;
	}

	if ( explanationEl ) {
		explanationEl.value = option.explanation;
	}
};

const apiRequest = ( url, method = 'POST', data, callbacks = {}, questionEditEl, questionId ) => {
	if ( ! url ) {
		return;
	}

	if ( abortControllers[ questionId ] ) {
		abortControllers[ questionId ].abort();
	}

	const abortController = new AbortController();
	abortControllers[ questionId ] = abortController;

	let params = {
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': lpDataAdmin.nonce,
		},
		method,
		body: JSON.stringify( data ),

	};

	if ( questionId ) {
		params.signal = abortController.signal;
	}

	if ( method === 'GET' ) {
		params = {};
	}

	updateStatus( 'loading', questionEditEl );
	const { success, error, completed } = callbacks;
	lpFetchAPI( url, params, {
		success,
		error,
		completed: () => {
			if ( abortControllers[ questionId ]?.signal?.aborted ) {
				// console.log( 'API request was aborted' );
				return;
			}

			if ( completed ) {
				completed();
			}
			updateStatus( 'success', questionEditEl );
		},
	} );
};

const getHtmlQuestionApi = ( questionEditEl, questionId ) => {
	return new Promise( ( resolve, reject ) => {
		const URL = lplistAPI.admin.apiGetHtmlQuestion + '/' + questionId;
		const callBack = {
			success: ( response ) => {
				const html = response.data.html;
				if ( html.length ) {
					for ( const detail of html ) {
						renderContent( questionEditEl, detail );
					}
				}
				resolve();
			},
			error: ( error ) => {
				reject( error );
			},
		};

		apiRequest( URL, 'GET', {}, callBack, questionEditEl );
	} );
};

const getQuestionOptionApi = ( questionOptionEl, questionId ) => {
	return new Promise( ( resolve, reject ) => {
		const URL = lplistAPI.admin.apiGetQuestionOption;
		const callBack = {
			success: ( response ) => {
				const option = response.data.option;
				if ( option ) {
					renderOption( questionOptionEl, option );
				}
				resolve();
			},
			error: ( error ) => {
				reject( error );
			},
		};

		const data = {
			questionId,
		};

		apiRequest( URL, 'POST', data, callBack, questionOptionEl );
	} );
};

const changeQuestionTypeApi = ( data, questionEditEl, questionId ) => {
	const URL = lplistAPI.admin.apiChangeQuestionType;
	const callBack = {
		success: ( response ) => {
			const html = response.data.html;
			if ( html.length ) {
				for ( const detail of html ) {
					renderContent( questionEditEl, detail );
				}
			}
		},
	};

	apiRequest( URL, 'POST', data, callBack, questionEditEl, questionId );
};

const changeOptionApi = ( data, questionEditEl, questionId ) => {
	const URL = lplistAPI.admin.apiChangeOption;
	apiRequest( URL, 'POST', data, {}, questionEditEl, questionId );
};

const changeTitleAnswerApi = ( data, questionEditEl ) => {
	const URL = lplistAPI.admin.apiUpdateAnswerTitle;
	apiRequest( URL, 'POST', data, {}, questionEditEl );
};

const addNewAnswerApi = ( data, questionEditEl ) => {
	const URL = lplistAPI.admin.apiAddNewAnswer;
	const callBack = {
		success: ( response ) => {
			const htmls = response.data.html;
			if ( htmls.length ) {
				for ( const html of htmls ) {
					renderContent( questionEditEl, html );
				}
			}
		},
	};

	apiRequest( URL, 'POST', data, callBack, questionEditEl );
};

const changeCorrectAnswerApi = ( data, questionEditEl ) => {
	const URL = lplistAPI.admin.apiChangeCorrectAnswer;
	apiRequest( URL, 'POST', data, {}, questionEditEl );
};

const removeAnswerApi = ( data, questionEditEl ) => {
	const URL = lplistAPI.admin.apiDeleteAnswer;
	apiRequest( URL, 'POST', data, {}, questionEditEl );
};

const sortAnswerApi = ( data, questionEditEl ) => {
	const URL = lplistAPI.admin.apiSortAnswer;
	apiRequest( URL, 'POST', data, {}, questionEditEl );
};

export { getHtmlQuestionApi, changeQuestionTypeApi, changeTitleAnswerApi, addNewAnswerApi, changeCorrectAnswerApi, removeAnswerApi, sortAnswerApi, updateStatus, getQuestionOptionApi, changeOptionApi };
