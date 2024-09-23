import lplistAPI from '../../api';
import { lpFetchAPI } from '../../utils';
import { getQuestionId, singleQuestion } from '../question/eventHandlers';
import { handleActionQuestion, renderQuestion } from './quiz-edit';

const popupModalSelectItemEl = document.querySelector( '#lp-modal-choose-items-refactor' );
const chooseItemsEl = popupModalSelectItemEl.querySelector( '.lp-choose-items' );
const urlParams = new URLSearchParams( window.location.search );
const quizId = urlParams.get( 'post' ) ?? 0;
const listAddedEl = popupModalSelectItemEl?.querySelector( '.list-added-items' );
const API_SEARCH_ITEMS_URL = lplistAPI.admin.apiSearchQuestionItems;
let currentAbortController = null;

const attachPaginationListeners = ( el, handler ) => {
	el.removeEventListener( 'click', handler );
	el.addEventListener( 'click', handler );
};

const updateTotalItemSection = ( el, value, elRemove ) => {
	if ( ! el ) {
		return;
	}

	let changeValue = 0;
	if ( value ) {
		changeValue = value;
	}

	if ( elRemove ) {
		const countEl = elRemove.querySelector( '.section-item-counts span' );
		const contentRemove = countEl.textContent;
		const numberRemove = parseInt( contentRemove );
		changeValue = -numberRemove;
	}

	const sectionItemCounts = el.querySelector( '.section-item-counts span' );
	const content = sectionItemCounts.textContent;
	const number = parseInt( content );
	const words = content.replace( /[0-9]/g, '' ).trim();
	const total = number + changeValue;
	const result = total + ' ' + words;
	sectionItemCounts.textContent = result;
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

const handlePagination = ( paginationEl, paginationHtml ) => {
	if ( ! popupModalSelectItemEl ) {
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

	if ( lastEl ) {
		if ( currentPage < total ) {
			lastEl.disabled = false;
		} else {
			lastEl.disabled = true;
		}
		lastEl.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			handlePageChange( total );
		} );
	}

	if ( nextEl ) {
		if ( currentPage < total ) {
			nextEl.disabled = false;
		} else {
			nextEl.disabled = true;
		}
		attachPaginationListeners( nextEl, ( e ) => {
			e.preventDefault();
			handlePageChange( currentPage + 1 );
		} );
	}

	if ( firstEl ) {
		if ( currentPage > 1 ) {
			firstEl.disabled = false;
		} else {
			firstEl.disabled = true;
		}
		attachPaginationListeners( firstEl, ( e ) => {
			e.preventDefault();
			handlePageChange( 1 );
		} );
	}

	if ( prevEl ) {
		if ( currentPage > 1 ) {
			prevEl.disabled = false;
		} else {
			prevEl.disabled = true;
		}
		attachPaginationListeners( prevEl, ( e ) => {
			e.preventDefault();
			handlePageChange( currentPage - 1 );
		} );
	}
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

	const listResultItemEls = Array.prototype.slice.call( listItemEl.querySelectorAll( '.lp-result-item' ) );

	if ( listResultItemEls.length > 0 ) {
		const itemAddedEls = Array.prototype.slice.call( listAddedEl.querySelectorAll( '.lp-result-item' ) );
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

	const itemSelectedEls = Array.prototype.slice.call( listAddedEl.querySelectorAll( '.lp-result-item' ) );
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
					updateTotalItemSection( quizEditorEl, itemEls.length );
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
			const sectionItemAddedEls = Array.prototype.slice.call( listAddedEl.querySelectorAll( '.lp-result-item' ) );
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
				if ( previousValue !== currentValue && currentValue !== '' ) {
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
