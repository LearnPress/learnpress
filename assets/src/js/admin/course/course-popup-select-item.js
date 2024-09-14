import lplistAPI from '../../api';
import { lpFetchAPI } from '../../utils';

const popupModalSelectItemEl = document.querySelector( '#lp-modal-choose-items-refactor' );
const urlParams = new URLSearchParams( window.location.search );
const courseId = urlParams.get( 'post' ) ?? 0;
const listAddedEl = popupModalSelectItemEl?.querySelector( '.list-added-items' );
const API_SEARCH_ITEMS_URL = lplistAPI.admin.apiSearchItems;
const tabs = Array.prototype.slice.call( popupModalSelectItemEl?.querySelectorAll( '.tabs .tab' ) );
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

const handleTabClick = ( tabs, tab, courseId ) => {
	const itemType = tab.dataset.type ?? '';
	if ( ! itemType ) {
		return;
	}
	popupModalSelectItemEl.dataset.type = itemType;
	tabs.forEach( ( tab ) => {
		tab.classList.remove( 'active' );
		tab.classList.add( 'inactive' );
	} );

	tab.classList.remove( 'inactive' );
	tab.classList.add( 'active' );
	const data = {
		courseId,
		itemType,
	};
	getSectionItem( data, popupModalSelectItemEl );
	return itemType;
};

const handlePageChange = ( page ) => {
	const itemType = popupModalSelectItemEl.dataset.type ?? '';
	if ( ! itemType ) {
		return;
	}
	let query = '';
	const searchEl = popupModalSelectItemEl.querySelector( '.search input' );
	if ( searchEl ) {
		query = searchEl.value;
	}
	const data = { courseId, itemType, page, query };
	getSectionItem( data, popupModalSelectItemEl );
};

const handlePagination = ( paginationEl, paginationHtml ) => {
	const itemType = popupModalSelectItemEl?.dataset?.type ?? '';
	if ( ! itemType || ! popupModalSelectItemEl ) {
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

	if ( tabs.length > 0 ) {
		tabs.forEach( ( tab, index ) => {
			if ( index === 0 ) {
				tab.classList.remove( 'inactive' );
				tab.classList.add( 'active' );
			} else {
				tab.classList.remove( 'active' );
				tab.classList.add( 'inactive' );
			}
		} );
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
};

const getSectionItem = ( data ) => {
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

const updateSectionApi = ( data, listUiSortableEl ) => {
	if ( ! data || ! popupModalSelectItemEl ) {
		return;
	}
	const sectionId = popupModalSelectItemEl.dataset.selectId ?? '';
	const selectEl = document.querySelector( `.section[data-section-id="${ sectionId }"]` );
	const courseEditorEl = document.querySelector( '#course-editor-refactor' );

	const url = lplistAPI.admin.apiUpdateSectionItemOrder;
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
					listUiSortableEl.insertAdjacentHTML( 'beforeend', itemEl );
				} );

				if ( selectEl && courseEditorEl ) {
					updateTotalItemSection( selectEl, itemEls.length );
					updateTotalItemSection( courseEditorEl, itemEls.length );
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
	if ( tabs.length > 0 ) {
		tabs.map( ( tab ) => {
			attachPaginationListeners( tab, ( e ) => {
				e.preventDefault();
				handleTabClick( tabs, tab, courseId );
			} );
		} );
	}

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
			} else {
				listAddedEl.classList.add( 'show' );
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
			const sectionId = popupModalSelectItemEl.dataset.selectId ?? '';
			if ( ! sectionId ) {
				return;
			}
			const selectEl = document.querySelector( `.section[data-section-id="${ sectionId }"]` );
			const listUiSortableEl = selectEl.querySelector( '.ui-sortable' );
			const sectionItemEls = Array.prototype.slice.call( selectEl.querySelectorAll( '.section-list-items .ui-sortable	.section-item' ) );
			const currentItemEl = sectionItemEls.map( ( sectionItemEl ) => {
				const data = {
					id: sectionItemEl.dataset.itemId ?? null,
					type: sectionItemEl.dataset.itemType ?? null,
				};
				return data;
			} );
			const sectionItemAddedEls = Array.prototype.slice.call( listAddedEl.querySelectorAll( '.lp-result-item' ) );
			const selectedAddItem = sectionItemAddedEls.map( ( sectionItemAddedEl ) => {
				const data = {
					id: sectionItemAddedEl.dataset.id ?? null,
					type: sectionItemAddedEl.dataset.type ?? null,
					title: sectionItemAddedEl.dataset.text ?? null,
				};
				return data;
			} );

			const dataUpdateItem = currentItemEl.concat( selectedAddItem );
			const data = {
				items: dataUpdateItem,
				courseId,
				sectionId,
				itemAddNew: selectedAddItem,
			};
			updateSectionApi( data, listUiSortableEl );
		} );
	}

	const searchEl = popupModalSelectItemEl.querySelector( '.search input' );
	if ( searchEl ) {
		let previousValue = searchEl.value;
		searchEl.addEventListener( 'keydown', function( event ) {
			if ( event.key === 'Enter' ) {
				event.preventDefault();
				const currentValue = searchEl.value;
				if ( previousValue !== currentValue && currentValue !== '' ) {
					previousValue = currentValue;
					const itemType = popupModalSelectItemEl.dataset.type ?? '';
					const data = {
						query: currentValue,
						courseId,
						itemType,
					};
					getSectionItem( data );
				}
			}
		} );
		searchEl.addEventListener( 'blur', function() {
			const currentValue = searchEl.value;
			if ( previousValue !== currentValue && currentValue !== '' ) {
				previousValue = currentValue;
				const itemType = popupModalSelectItemEl.dataset.type ?? '';
				const data = {
					query: currentValue,
					courseId,
					itemType,
				};
				getSectionItem( data );
			}
		} );
	}
};

const popupSelectItem = ( selectEl ) => {
	const selectId = selectEl.dataset?.sectionId ?? '';
	if ( ! popupModalSelectItemEl || ! selectId ) {
		return;
	}

	popupModalSelectItemEl.dataset.selectId = selectId;

	if ( tabs.length > 0 ) {
		const itemType = tabs[ 0 ].dataset.type ?? '';
		popupModalSelectItemEl.dataset.type = itemType;
		const data = {
			courseId,
			itemType,
		};

		getSectionItem( data, popupModalSelectItemEl );
	}
};

export { popupSelectItem, handleEventPopup };
