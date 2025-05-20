import { Sortable } from 'sortablejs';
import {
	getCourseApi,
	updateOrderSectionItemApi,
	deleteItemApi,
	removeItemInSectionApi,
	addNewItemApi,
	updateSectionItemApi,
	updateOrderSectionApi,
	addSectionApi,
	deleteSectionApi,
	updateSectionApi,
	updateSectionWithPopupApi,
} from './apiRequests';
import { popupSelectItem } from '../popupSelectedItem';
import lplistAPI from '../../api';

function delay( ms ) {
	return new Promise( ( resolve ) => setTimeout( resolve, ms ) );
}

const getCourseId = () => {
	const urlParams = new URLSearchParams( window.location.search );
	let courseId = urlParams.get( 'post' ) ?? 0;
	if ( ! courseId ) {
		courseId = document.querySelector( 'input#post_ID' ).value;
	}
	return courseId;
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

const addSectionsWithDelay = async ( html, courseEditorEl ) => {
	if ( ! html.length ) {
		return;
	}

	if ( ! courseEditorEl ) {
		return;
	}

	const addNewSectionEl = courseEditorEl.querySelector( '.curriculum-sections .add-new-section' );
	if ( ! addNewSectionEl ) {
		return;
	}

	for ( const sectionHtml of html ) {
		addNewSectionEl.insertAdjacentHTML( 'beforebegin', sectionHtml );
		const newSection = addNewSectionEl.previousElementSibling;
		if ( ! newSection ) {
			return;
		}
		restoreSectionState( newSection, courseEditorEl );
		updateSingleSection( newSection, courseEditorEl );
		singleCollapseEvent( newSection, courseEditorEl );
		updateSingleSectionItem( newSection, courseEditorEl );
		sortableItemEvent( newSection, courseEditorEl );
		await delay( 100 );
	}
};

const updateStatus = ( status ) => {
	const statusEl = document.querySelector( '#course-editor-refactor .status' );
	if ( statusEl ) {
		statusEl.classList.remove( 'loading', 'success' );
		statusEl.classList.add( status );
	}
};

const handleEventSectionItem = ( sectionItemEl, sectionId, sectionEl, courseEditorEl ) => {
	const inputItem = sectionItemEl.querySelector( '.title input' );
	const itemId = sectionItemEl.dataset.itemId ?? null;
	const itemOrder = sectionItemEl.dataset.itemOrder ?? null;
	const previewEl = sectionItemEl.querySelector( '.preview-item' );
	const previewIconEl = previewEl?.querySelector( '.lp-btn-icon' );
	const removeItemInSectionEl = sectionItemEl.querySelector( '.delete-in-course' );
	const deleteItemEl = sectionItemEl.querySelector( '.delete-permanently' );
	const courseId = getCourseId();

	if ( inputItem ) {
		let previousValue = inputItem.value;
		inputItem.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Enter' ) {
				event.preventDefault();
				const currentValue = inputItem.value;
				if ( previousValue !== currentValue && currentValue !== '' ) {
					previousValue = currentValue;
					const data = {
						itemId,
						title: currentValue,
						courseId,
					};
					updateSectionItemApi( data );
				}
			}
		} );
		inputItem.addEventListener( 'blur', function () {
			const currentValue = inputItem.value;
			if ( previousValue !== currentValue && currentValue !== '' ) {
				previousValue = currentValue;
				const data = {
					itemId,
					title: currentValue,
					courseId,
				};
				updateSectionItemApi( data );
			}
		} );
	}

	if ( previewEl && previewIconEl ) {
		previewEl.addEventListener( 'click', () => {
			if ( previewIconEl.classList.contains( 'dashicons-visibility' ) ) {
				const preview = false;
				previewIconEl.classList.remove( 'dashicons-visibility' );
				previewIconEl.classList.add( 'dashicons-hidden' );
				const data = {
					itemId,
					preview,
					courseId,
				};
				updateSectionItemApi( data );
			} else {
				const preview = true;
				previewIconEl.classList.add( 'dashicons-visibility' );
				previewIconEl.classList.remove( 'dashicons-hidden' );
				const data = {
					itemId,
					preview,
					courseId,
				};
				updateSectionItemApi( data );
			}
		} );
	}

	if ( removeItemInSectionEl ) {
		removeItemInSectionEl.addEventListener( 'click', () => {
			if ( itemId && sectionId ) {
				const data = {
					itemId,
					sectionId,
					courseId,
				};
				removeItemInSectionApi( data, sectionItemEl, sectionEl, courseEditorEl );
			}
		} );
	}

	if ( deleteItemEl ) {
		deleteItemEl.addEventListener( 'click', () => {
			if ( itemId && sectionId ) {
				const data = {
					itemId,
					sectionId,
					courseId,
				};
				deleteItemApi( data, sectionItemEl, sectionEl, courseEditorEl );
			}
		} );
	}
};

const addNewSection = ( courseEditorEl ) => {
	if ( ! courseEditorEl ) {
		return;
	}

	const addNewSectionEl = courseEditorEl.querySelector( '.add-new-section' );

	if ( ! addNewSectionEl ) {
		return;
	}

	const input = addNewSectionEl.querySelector( 'input' );

	if ( ! input ) {
		return;
	}

	const courseId = getCourseId();
	const previousValue = input.value;
	input.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Enter' ) {
			event.preventDefault();
			const currentValue = input.value;
			if ( previousValue !== currentValue && currentValue !== '' ) {
				input.value = '';
				const data = {
					title: currentValue,
					courseId,
				};
				addSectionApi( data, courseEditorEl );
			}
		}
	} );
	input.addEventListener( 'blur', function () {
		const currentValue = input.value;
		if ( previousValue !== currentValue && currentValue !== '' ) {
			input.value = '';
			const data = {
				title: currentValue,
				courseId,
			};
			addSectionApi( data, courseEditorEl );
		}
	} );
};

const singleCollapseEvent = ( sectionEl, courseEditorEl ) => {
	const collapse = sectionEl.querySelector( '.collapse' );
	const collapseSectionsEl = courseEditorEl.querySelector( '.collapse-sections' );

	if ( collapse ) {
		collapse.addEventListener( 'click', () => {
			if ( sectionEl.classList.contains( 'close' ) ) {
				sectionEl.classList.add( 'open' );
				sectionEl.classList.remove( 'close' );
			} else {
				sectionEl.classList.add( 'close' );
				sectionEl.classList.remove( 'open' );
			}

			saveSectionState( courseEditorEl );
			if ( ! collapseSectionsEl ) {
				return;
			}

			const notAllClose = courseEditorEl.querySelector( '.curriculum-sections > .section.open' );

			if ( notAllClose ) {
				collapseSectionsEl.classList.remove( 'close' );
				collapseSectionsEl.classList.add( 'open' );
			} else {
				collapseSectionsEl.classList.remove( 'open' );
				collapseSectionsEl.classList.add( 'close' );
			}
		} );
	}
};

const collapseSectionsEvent = ( courseEditorEl ) => {
	const collapseSectionsEl = courseEditorEl.querySelector( '.collapse-sections' );

	if ( collapseSectionsEl ) {
		collapseSectionsEl.addEventListener( 'click', () => {
			const sectionEls = Array.prototype.slice.call(
				courseEditorEl.querySelectorAll( '.curriculum-sections > .section' )
			);
			if ( collapseSectionsEl.classList.contains( 'close' ) ) {
				collapseSectionsEl.classList.add( 'open' );
				collapseSectionsEl.classList.remove( 'close' );
				if ( sectionEls.length ) {
					sectionEls.map( ( sectionEl ) => {
						sectionEl.classList.add( 'open' );
						sectionEl.classList.remove( 'close' );
					} );
				}
			} else {
				collapseSectionsEl.classList.add( 'close' );
				collapseSectionsEl.classList.remove( 'open' );
				if ( sectionEls.length ) {
					sectionEls.map( ( sectionEl ) => {
						sectionEl.classList.add( 'close' );
						sectionEl.classList.remove( 'open' );
					} );
				}
			}
			saveSectionState( courseEditorEl );
		} );
	}
};

const actionSection = ( sectionEl, courseEditorEl ) => {
	if ( ! sectionEl ) {
		return;
	}

	const courseId = getCourseId();

	const btnSelectEl = sectionEl.querySelector( '.section-actions .select-items' );
	if ( btnSelectEl ) {
		btnSelectEl.addEventListener( 'click', () => {
			const selectId = sectionEl.dataset?.sectionId ?? '';
			const API_SEARCH_ITEMS_URL = lplistAPI.admin.apiSearchItems;
			popupSelectItem( selectId, API_SEARCH_ITEMS_URL );
		} );
	}

	const removeIconEl = sectionEl.querySelector( '.remove .icon' );
	const removeEl = sectionEl.querySelector( '.remove' );
	if ( removeIconEl ) {
		removeIconEl.addEventListener( 'click', () => {
			if ( removeEl ) {
				removeEl.classList.add( 'confirm' );

				setTimeout( () => {
					removeEl.classList.remove( 'confirm' );
				}, 2500 );
			}
		} );
	}

	const confirmEl = sectionEl.querySelector( '.remove .confirm' );
	if ( confirmEl ) {
		confirmEl.addEventListener( 'click', () => {
			const sectionId = sectionEl.dataset.sectionId;
			const data = {
				sectionId,
				courseId,
			};
			deleteSectionApi( data, sectionEl, courseEditorEl );
		} );
	}
};

const updateSingleSectionItem = ( sectionEl, courseEditorEl ) => {
	if ( ! sectionEl ) {
		return;
	}

	const listItemEl = sectionEl.querySelector( '.section-list-items' );
	const sectionOrder = sectionEl.dataset.sectionOrder ?? null;
	const sectionId = sectionEl.dataset.sectionId ?? null;

	if ( ! listItemEl ) {
		return;
	}

	const newSectionItemEl = listItemEl.querySelector( '.new-section-item' );
	if ( newSectionItemEl ) {
		const radioButtons = newSectionItemEl.querySelectorAll( 'input[name="lp-section-item-type"]' );
		radioButtons.forEach( ( radio ) => {
			radio.addEventListener( 'change', function () {
				newSectionItemEl
					.querySelectorAll( '.type' )
					.forEach( ( label ) => label.classList.remove( 'current' ) );
				this.closest( 'label' ).classList.add( 'current' );
			} );
		} );

		const newSectionItemInputEl = newSectionItemEl.querySelector( '.title input' );
		const btnAddLesson = newSectionItemEl.querySelector( '.btn-add-lesson' );

		newSectionItemInputEl.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Enter' ) {
				event.preventDefault();
				if ( ! newSectionItemInputEl.value ) {
					return;
				}
				const selectedValue = newSectionItemEl.querySelector( '.type.current input' )?.value ?? '';
				const courseId = getCourseId();
				const data = {
					sectionId,
					item: {
						title: newSectionItemInputEl.value,
						type: selectedValue,
					},
					courseId,
				};
				addNewItemApi( data, newSectionItemInputEl, sectionEl, courseEditorEl );
				newSectionItemInputEl.value = '';
			}
		} );

		if ( btnAddLesson && newSectionItemInputEl ) {
			btnAddLesson.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				if ( ! newSectionItemInputEl.value ) {
					return;
				}

				const selectedValue = newSectionItemEl.querySelector( '.type.current input' )?.value ?? '';
				const courseId = getCourseId();
				const data = {
					sectionId,
					item: {
						title: newSectionItemInputEl.value,
						type: selectedValue,
					},
					courseId,
				};
				addNewItemApi( data, newSectionItemInputEl, sectionEl, courseEditorEl );
				newSectionItemInputEl.value = '';
			} );
		}
	}

	const sectionItemEls = Array.prototype.slice.call(
		listItemEl.querySelectorAll( 'li.section-item' )
	);
	if ( ! sectionItemEls.length ) {
		return;
	}

	sectionItemEls.map( ( sectionItemEl ) => {
		handleEventSectionItem( sectionItemEl, sectionId, sectionEl, courseEditorEl );
	} );
};

const updateSingleSection = ( sectionEl, courseEditorEl ) => {
	if ( ! sectionEl ) {
		return;
	}

	const courseId = getCourseId();

	actionSection( sectionEl, courseEditorEl );
	const titleSection = sectionEl.querySelector( '.section-head input' );
	if ( titleSection ) {
		let previousValue = titleSection.value;
		titleSection.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Enter' ) {
				event.preventDefault();
				const currentValue = titleSection.value;
				if ( previousValue !== currentValue && currentValue !== '' ) {
					previousValue = currentValue;
					const sectionId = sectionEl?.dataset?.sectionId ?? 0;

					const data = {
						title: currentValue,
						sectionId,
						courseId,
					};
					updateSectionApi( data );
				}
			}
		} );
		titleSection.addEventListener( 'blur', function () {
			const currentValue = titleSection.value;
			if ( previousValue !== currentValue && currentValue !== '' ) {
				previousValue = currentValue;
				const sectionId = sectionEl?.dataset?.sectionId ?? 0;

				const data = {
					title: currentValue,
					sectionId,
					courseId,
				};
				updateSectionApi( data );
			}
		} );
	}

	const descSection = sectionEl.querySelector( '.details input' );
	if ( descSection ) {
		let previousValue = descSection.value;
		descSection.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Enter' ) {
				event.preventDefault();
				const currentValue = descSection.value;
				if ( previousValue !== currentValue && currentValue !== '' ) {
					previousValue = currentValue;
					const sectionId = sectionEl.dataset.sectionId;

					const data = {
						desc: currentValue,
						sectionId,
						courseId,
					};
					updateSectionApi( data );
				}
			}
		} );

		descSection.addEventListener( 'blur', function () {
			const currentValue = descSection.value;
			if ( previousValue !== currentValue && currentValue !== '' ) {
				previousValue = currentValue;
				const sectionId = sectionEl.dataset.sectionId;

				const data = {
					desc: currentValue,
					sectionId,
					courseId,
				};
				updateSectionApi( data );
			}
		} );
	}
};

const sortableItemEvent = ( sectionEl ) => {
	if ( ! sectionEl ) {
		return;
	}

	const sortableEl = sectionEl.querySelector( 'ul.ui-sortable' );

	if ( ! sortableEl ) {
		return;
	}

	const courseId = getCourseId();
	const sectionId = sectionEl.dataset.sectionId ?? null;

	new Sortable( sortableEl, {
		multiDrag: true,
		selectedClass: 'lp-selected',
		handle: '.lp-sortable-handle',
		group: 'lp-section-item',
		animation: 150,
		onRemove( evt ) {
			const changeSortableEl = evt.from;
			if ( ! changeSortableEl ) {
				return;
			}

			const changeSectionId = changeSortableEl.dataset.sectionId ?? null;
			const sectionItemEls = Array.prototype.slice.call(
				changeSortableEl.querySelectorAll( ':scope > .section-item' )
			);
			const sectionItems = sectionItemEls.map( ( sectionItemEl ) => {
				const data = {
					id: sectionItemEl.dataset.itemId ?? null,
					type: sectionItemEl.dataset.itemType ?? null,
				};
				return data;
			} );
			const itemRemove = evt.item;
			const itemIdRemove = itemRemove.dataset.itemId;
			const dataSort = {
				items: sectionItems,
				sectionId: changeSectionId,
				courseId,
			};
			const dataRemove = {
				itemId: itemIdRemove,
				sectionId: changeSectionId,
				courseId,
			};
			removeItemInSectionApi( dataRemove, '', sectionEl );
			updateOrderSectionItemApi( dataSort );
		},
		onUpdate( evt ) {
			const sectionItemEls = Array.prototype.slice.call(
				sortableEl.querySelectorAll( ':scope > .section-item' )
			);
			const sectionItems = sectionItemEls.map( ( sectionItemEl ) => {
				const data = {
					id: sectionItemEl.dataset.itemId ?? null,
					type: sectionItemEl.dataset.itemType ?? null,
				};
				return data;
			} );
			const dataSort = {
				items: sectionItems,
				sectionId,
				courseId,
			};
			updateOrderSectionItemApi( dataSort );
		},
		onAdd( evt ) {
			const changeSortableEl = evt.to;
			if ( ! changeSortableEl ) {
				return;
			}

			const changeSectionId = changeSortableEl.dataset.sectionId ?? null;
			const sectionItemEls = Array.prototype.slice.call(
				changeSortableEl.querySelectorAll( ':scope > .section-item' )
			);
			const sectionItems = sectionItemEls.map( ( sectionItemEl ) => {
				const data = {
					id: sectionItemEl.dataset.itemId ?? null,
					type: sectionItemEl.dataset.itemType ?? null,
				};
				return data;
			} );
			const data = {
				items: sectionItems,
				sectionId: changeSectionId,
				courseId,
			};
			updateOrderSectionItemApi( data );
			updateTotalItemSection( sectionEl, 1 );
		},
	} );
};

const sortableSection = ( courseEditorEl ) => {
	if ( ! courseEditorEl ) {
		return;
	}

	const sortableEl = courseEditorEl.querySelector( '.ui-sortable' );

	if ( ! sortableEl ) {
		return;
	}
	const courseId = getCourseId();

	new Sortable( sortableEl, {
		multiDrag: true,
		selectedClass: 'lp-selected',
		handle: '.lp-sortable-handle',
		animation: 150,
		onUpdate( evt ) {
			const sectionEls = Array.prototype.slice.call(
				sortableEl.querySelectorAll( ':scope > .section' )
			);
			const sectionIds = sectionEls.map( ( sectionEl ) => {
				return sectionEl.dataset.sectionId ?? null;
			} );

			const data = {
				sectionIds,
				courseId,
			};

			updateOrderSectionApi( data );
		},
	} );
};

function saveSectionState( courseEditorEl ) {
	const courseId = getCourseId();
	const sections = courseEditorEl.querySelectorAll( '.curriculum-sections > .section' );
	const sectionStatesStorage = JSON.parse( localStorage.getItem( 'lpSectionStates' ) ) || {};
	const sectionStates = {};
	sections.forEach( ( section ) => {
		const sectionId = section.getAttribute( 'data-section-id' );
		const isOpen = section.classList.contains( 'open' );
		sectionStates[ sectionId ] = isOpen;
	} );

	sectionStatesStorage[ courseId ] = sectionStates;
	localStorage.setItem( 'lpSectionStates', JSON.stringify( sectionStatesStorage ) );
}

function restoreSectionState( newSection, courseEditorEl ) {
	const courseId = getCourseId();
	const sectionStatesStorage = JSON.parse( localStorage.getItem( 'lpSectionStates' ) ) || {};
	const sectionStates = sectionStatesStorage[ courseId ];

	if ( ! sectionStates ) {
		newSection.classList.add( 'close' );
		return;
	}

	const sectionId = newSection.getAttribute( 'data-section-id' );
	if ( ! sectionStates[ sectionId ] ) {
		newSection.classList.add( 'close' );
		return;
	}

	const collapseSectionsEl = courseEditorEl.querySelector( '.collapse-sections' );
	collapseSectionsEl.classList.add( 'open' );
	collapseSectionsEl.classList.remove( 'close' );
	newSection.classList.add( 'open' );
}

const handleUpdateItem = () => {
	const popupModalSelectItemEl = document.querySelector( '#lp-modal-choose-items-refactor' );
	const listAddedEl = popupModalSelectItemEl?.querySelector( '.list-added-items' );
	const sectionId = popupModalSelectItemEl?.dataset?.id ?? '';
	if ( ! sectionId ) {
		return;
	}
	const selectEl = document.querySelector( `.section[data-section-id="${ sectionId }"]` );
	const listUiSortableEl = selectEl.querySelector( '.ui-sortable' );
	const sectionItemEls = Array.from(
		selectEl.querySelectorAll( '.section-list-items .ui-sortable	.section-item' )
	);
	const currentItemEl = sectionItemEls.map( ( sectionItemEl ) => {
		const data = {
			id: sectionItemEl.dataset.itemId ?? null,
			type: sectionItemEl.dataset.itemType ?? null,
		};
		return data;
	} );
	const sectionItemAddedEls = Array.from( listAddedEl.querySelectorAll( '.lp-result-item' ) );
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
		courseId: getCourseId(),
		sectionId,
		itemAddNew: selectedAddItem,
	};
	updateSectionWithPopupApi( data, listUiSortableEl, popupModalSelectItemEl );
};

export {
	getCourseId,
	updateTotalItemSection,
	addSectionsWithDelay,
	updateStatus,
	handleEventSectionItem,
	addNewSection,
	singleCollapseEvent,
	collapseSectionsEvent,
	actionSection,
	updateSingleSection,
	sortableItemEvent,
	sortableSection,
	saveSectionState,
	restoreSectionState,
	handleUpdateItem,
};
