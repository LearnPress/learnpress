import lplistAPI from '../../api';
import { lpFetchAPI } from '../../utils';
import { renderQuestion, updateTotalItem } from './eventHandlers';

const popupModalSelectItemEl = document.querySelector( '#lp-modal-choose-items-refactor' );
const chooseItemsEl = popupModalSelectItemEl?.querySelector( '.lp-choose-items' );
const urlParams = new URLSearchParams( window.location.search );
const quizId = urlParams?.get( 'post' ) ?? 0;
const listAddedEl = popupModalSelectItemEl?.querySelector( '.list-added-items' );
const API_SEARCH_ITEMS_URL = lplistAPI.admin.apiSearchQuestionItems;
let currentAbortController = null;

const attachPaginationListeners = ( el, handler ) => {
	el.removeEventListener( 'click', handler );
	el.addEventListener( 'click', handler );
};

const handlePageChange = ( page ) => {
	let query = '';
	const searchEl = popupModalSelectItemEl.querySelector( '.search input' );
	if ( searchEl ) {
		query = searchEl.value;
	}
	const data = { quizId, page, query };
	getQuestionItem( data, popupModalSelectItemEl );
};

const updateButtonState = ( el, condition = false, page ) => {
	if ( el ) {
		el.disabled = ! condition;
		attachPaginationListeners( el, ( e ) => {
			e.preventDefault();
			handlePageChange( page );
		} );
	}
};

const handlePagination = ( paginationEl, paginationHtml ) => {
	if ( ! popupModalSelectItemEl || ! paginationEl || ! paginationHtml ) {
		return;
	}

	paginationEl.style.display = 'block';
	const currentPage = paginationHtml.current ?? 1;
	const total = paginationHtml.total ?? 1;
	const nextEl = paginationEl.querySelector( '.next' );
	const prevEl = paginationEl.querySelector( '.previous' );
	const firstEl = paginationEl.querySelector( '.first' );
	const lastEl = paginationEl.querySelector( '.last' );
	const indexEl = paginationEl.querySelector( '.index' );

	if ( indexEl ) {
		indexEl.innerText = `${ currentPage }/${ total } `;
	}

	updateButtonState( nextEl, currentPage < total, currentPage + 1 );
	updateButtonState( firstEl, currentPage > 1, 1 );
	updateButtonState( lastEl, currentPage < total, total );
	updateButtonState( prevEl, currentPage > 1, currentPage - 1 );
};

const resetPopup = () => {
	if ( ! popupModalSelectItemEl ) {
		return;
	}

	popupModalSelectItemEl.classList.remove( 'show', 'loading' );
	const searchInputEl = popupModalSelectItemEl.querySelector( '.search input' );
	const listItemEl = popupModalSelectItemEl.querySelector( '.list-items' );
	const selectedTotalEl = popupModalSelectItemEl.querySelector( '.footer .total-selected' );
	const addSelectedEl = popupModalSelectItemEl.querySelector( '.footer .checkout' );
	const editSelectedBtnEl = popupModalSelectItemEl.querySelector( '.edit-selected' );
	const editSelectedBtnShowEl = editSelectedBtnEl.querySelector( '.show' );
	const editSelectedBtnBackEl = editSelectedBtnEl.querySelector( '.back' );
	const listAddedPreviewEl = popupModalSelectItemEl.querySelector( '.lp-added-items-preview' );

	if ( listItemEl ) {
		listItemEl.innerText = '';
	}

	if ( listAddedEl ) {
		listAddedEl.innerText = '';
	}

	if ( selectedTotalEl ) {
		selectedTotalEl.innerText = '';
	}

	if ( searchInputEl ) {
		searchInputEl.value = '';
	}

	if ( addSelectedEl ) {
		addSelectedEl.disabled = true;
	}

	if ( addSelectedEl ) {
		addSelectedEl.disabled = true;
	}

	if ( editSelectedBtnEl ) {
		editSelectedBtnEl.disabled = true;
	}

	if ( listAddedPreviewEl ) {
		listAddedPreviewEl.classList.remove( 'show' );
	}

	if ( editSelectedBtnShowEl ) {
		editSelectedBtnShowEl.style.display = 'inline-block';
	}

	if ( editSelectedBtnBackEl ) {
		editSelectedBtnBackEl.style.display = 'none';
	}

	if ( currentAbortController ) {
		currentAbortController.abort();
	}

	if ( chooseItemsEl ) {
		chooseItemsEl.classList.remove( 'show-preview' );
	}
};

const renderPopup = ( popupModalSelectItemEl, itemsHtml, paginationHtml ) => {
	if ( ! popupModalSelectItemEl || ! itemsHtml ) {
		return;
	}

	const listItemEl = popupModalSelectItemEl.querySelector( '.list-items' );

	if ( ! listItemEl ) {
		return;
	}
	listItemEl.innerText = '';
	listItemEl.insertAdjacentHTML( 'beforeend', itemsHtml );

	const listResultItemEls = Array.from( listItemEl.querySelectorAll( '.lp-result-item' ) );

	if ( listResultItemEls.length > 0 ) {
		const itemAddedEls = Array.from( listAddedEl.querySelectorAll( '.lp-result-item' ) );
		const addedIds = itemAddedEls.map( ( itemAddedEl ) => {
			return itemAddedEl?.dataset?.id ?? '';
		} );
		listResultItemEls.map( ( listResultItemEl ) => {
			const idItem = listResultItemEl.dataset?.id;
			const checkboxItem = listResultItemEl.querySelector( 'input' );
			if ( ! checkboxItem ) {
				return;
			}

			if ( idItem && addedIds && addedIds.includes( idItem ) ) {
				listResultItemEl.classList.add( 'added' );
				checkboxItem.checked = true;
			} else {
				listResultItemEl.classList.add( 'addable' );
			}

			if ( ! listAddedEl ) {
				return;
			}

			checkboxItem.addEventListener( 'click', ( ) => {
				if ( checkboxItem.checked ) {
					const cloneEl = listResultItemEl.cloneNode( true );
					listAddedEl.appendChild( cloneEl );
					cloneEl.addEventListener( 'click', () => {
						const idItemClone = cloneEl.dataset?.id;
						const inputCheckEl = listItemEl.querySelector( `.lp-result-item[data-id="${ idItemClone }"] input` );
						if ( inputCheckEl ) {
							inputCheckEl.checked = false;
						}
						listAddedEl.removeChild( cloneEl );
						updateTotalSelected();
					} );
				} else {
					const itemToRemove = listAddedEl.querySelector( `[data-id="${ idItem }"]` );
					listAddedEl.removeChild( itemToRemove );
				}

				updateTotalSelected();
			} );
		} );
	}

	const paginationEl = popupModalSelectItemEl.querySelector( '.pagination' );

	if ( paginationEl ) {
		if ( paginationHtml ) {
			handlePagination( paginationEl, paginationHtml );
		}
	}
};

const updateTotalSelected = () => {
	if ( ! popupModalSelectItemEl || ! listAddedEl ) {
		return;
	}

	const itemSelectedEls = Array.from( listAddedEl.querySelectorAll( '.lp-result-item' ) );
	const addSelectedEl = popupModalSelectItemEl.querySelector( '.footer .checkout' );
	const editSelectedBtnEl = popupModalSelectItemEl.querySelector( '.edit-selected' );
	const selectedTotalEl = popupModalSelectItemEl.querySelector( '.footer .total-selected' );
	const selectedHeaderTotalEl = popupModalSelectItemEl.querySelector( '.header .total-selected' );
	if ( selectedTotalEl ) {
		if ( itemSelectedEls.length > 0 ) {
			selectedTotalEl.innerText = `(${ itemSelectedEls.length })`;
			addSelectedEl.disabled = false;
			editSelectedBtnEl.disabled = false;
		} else {
			selectedTotalEl.innerText = '';
			addSelectedEl.disabled = true;
			const backBtnEl = editSelectedBtnEl.querySelector( '.back' );
			if ( backBtnEl && backBtnEl.style.display === 'none' ) {
				editSelectedBtnEl.disabled = true;
			}
		}
	}

	if ( selectedHeaderTotalEl ) {
		if ( itemSelectedEls.length > 0 ) {
			selectedHeaderTotalEl.innerText = `(${ itemSelectedEls.length })`;
		} else {
			selectedHeaderTotalEl.innerText = '';
		}
	}
};

const resetQuestionOrder = ( el ) => {
	const questionItemEls = Array.from( el.querySelectorAll( '.ui-sortable > .question-item' ) );
	if ( ! questionItemEls.length ) {
		return;
	}

	questionItemEls.forEach( ( questionItemEl, index ) => {
		const oderEl = questionItemEl.querySelector( '.question-actions .order' );
		if ( ! oderEl ) {
			return;
		}
		oderEl.innerText = index + 1;
	} );
};

const getQuestionItem = ( data ) => {
	const paginationEl = popupModalSelectItemEl.querySelector( '.pagination' );

	if ( currentAbortController ) {
		currentAbortController.abort();
	}

	currentAbortController = new AbortController();
	const { signal } = currentAbortController;

	if ( paginationEl ) {
		paginationEl.style.display = 'none';
	}

	const params = {
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': lpDataAdmin.nonce,
		},
		method: 'POST',
		body: JSON.stringify( data ),
		signal,
	};

	popupModalSelectItemEl.classList.add( 'show', 'loading' );
	lpFetchAPI( API_SEARCH_ITEMS_URL, params, {
		success: ( response ) => {
			const itemHtml = response.data?.html?.items ?? '';
			const paginationHtml = response.data?.html?.pagination ?? '';
			renderPopup( popupModalSelectItemEl, itemHtml, paginationHtml );
			popupModalSelectItemEl.classList.remove( 'loading' );
		},
		error: ( err ) => {
			// console.log( err );
		},
		completed: () => {
		},
	} );
};

const addQuestionToQuizApi = ( data, listUiSortableEl ) => {
	if ( ! data || ! popupModalSelectItemEl ) {
		return;
	}
	const quizEditorEl = document.querySelector( '#admin-editor-lp_quiz-refactor' );

	const url = lplistAPI.admin.apiAddQuestionsToQuiz;
	const method = 'POST';
	const params = {
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': lpDataAdmin.nonce,
		},
		method,
		body: JSON.stringify( data ),
	};

	lpFetchAPI( url, params, {
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
	} );
};

const handleEventPopup = () => {
	const closeEl = popupModalSelectItemEl.querySelector( '.header .close' );
	if ( popupModalSelectItemEl ) {
		closeEl.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			resetPopup( popupModalSelectItemEl );
		} );
	}

	const editSelectedBtnEl = popupModalSelectItemEl.querySelector( '.edit-selected' );
	const listAddedEl = popupModalSelectItemEl.querySelector( '.lp-added-items-preview' );
	if ( editSelectedBtnEl ) {
		if ( ! listAddedEl ) {
			return;
		}

		const contentEditEl = editSelectedBtnEl.querySelector( '.show' );
		const contentBackEl = editSelectedBtnEl.querySelector( '.back' );
		const selectedTotalEl = popupModalSelectItemEl.querySelector( '.footer .total-selected' );

		editSelectedBtnEl.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			if ( listAddedEl.classList.contains( 'show' ) ) {
				listAddedEl.classList.remove( 'show' );
				contentEditEl.style.display = 'inline-block';
				contentBackEl.style.display = 'none';
				if ( selectedTotalEl && selectedTotalEl.innerText === '' ) {
					editSelectedBtnEl.disabled = true;
				}

				if ( chooseItemsEl ) {
					chooseItemsEl.classList.remove( 'show-preview' );
				}
			} else {
				listAddedEl.classList.add( 'show' );
				if ( chooseItemsEl ) {
					chooseItemsEl.classList.add( 'show-preview' );
				}
				contentEditEl.style.display = 'none';
				contentBackEl.style.display = 'inline-block';
			}
		} );
	}

	const addSelectedEl = popupModalSelectItemEl.querySelector( '.footer .checkout' );
	if ( addSelectedEl && listAddedEl ) {
		addSelectedEl.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			e.stopPropagation();
			const quizEditEl = document.querySelector( '#admin-editor-lp_quiz-refactor' );
			const listUiSortableEl = quizEditEl.querySelector( '.lp-list-questions .ui-sortable' );
			const sectionItemAddedEls = Array.from( listAddedEl.querySelectorAll( '.lp-result-item' ) );
			const selectedAddItem = sectionItemAddedEls.map( ( sectionItemAddedEl ) => {
				const data = {
					id: sectionItemAddedEl.dataset.id ?? null,
					title: sectionItemAddedEl.dataset.text ?? null,
				};
				return data;
			} );

			const data = {
				quizId,
				items: selectedAddItem,
			};
			addQuestionToQuizApi( data, listUiSortableEl );
		} );
	}

	const searchEl = popupModalSelectItemEl.querySelector( '.modal-search-input' );
	if ( searchEl ) {
		let previousValue = searchEl.value;
		searchEl.addEventListener( 'keydown', function( event ) {
			if ( event.key === 'Enter' ) {
				event.preventDefault();
				const currentValue = searchEl.value;
				if ( previousValue !== currentValue ) {
					previousValue = currentValue;
					const data = {
						query: currentValue,
						quizId,
					};
					getQuestionItem( data );
				}
			}
		} );
		searchEl.addEventListener( 'blur', function() {
			const currentValue = searchEl.value;
			if ( previousValue !== currentValue ) {
				previousValue = currentValue;
				const data = {
					query: currentValue,
					quizId,
				};
				getQuestionItem( data );
			}
		} );
	}
};

const popupSelectItem = () => {
	if ( ! popupModalSelectItemEl ) {
		return;
	}

	const data = {
		quizId,
	};
	getQuestionItem( data );
};

export { popupSelectItem, handleEventPopup };
