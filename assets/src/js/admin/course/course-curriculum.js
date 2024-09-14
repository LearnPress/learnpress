import { Sortable } from 'sortablejs';
import { lpFetchAPI } from '../../utils';
import lplistAPI from '../../api';
import { handleEventPopup, popupSelectItem } from './course-popup-select-item';

function delay( ms ) {
	return new Promise( ( resolve ) => setTimeout( resolve, ms ) );
}

const getCourseId = () => {
	const urlParams = new URLSearchParams( window.location.search );
	const courseId = urlParams.get( 'post' ) ?? 0;
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

// Add html with js
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

const apiRequest = ( url, method = 'POST', data, callbacks = {} ) => {
	if ( ! data || ! url ) {
		return;
	}

	const params = {
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': lpDataAdmin.nonce,
		},
		method,
		body: JSON.stringify( data ),
	};
	const { success, error, completed } = callbacks;
	updateStatus( 'loading' );
	lpFetchAPI( url, params, {
		success,
		error,
		completed: () => {
			if ( completed ) {
				completed();
			}
			updateStatus( 'success' );
		},
	} );
};

// Handle Update section with api
const updateSectionApi = ( data ) => {
	const url = lplistAPI.admin.apiUpdateSection;
	const method = 'PUT';
	apiRequest( url, method, data );
};

// Handle Delete section with api
const deleteSectionApi = ( data, sectionEl, courseEditorEl ) => {
	const url = lplistAPI.admin.apiDeleteSection;
	const method = 'DELETE';
	const callBack = {
		success: ( response ) => {
			if ( sectionEl ) {
				updateTotalItemSection( courseEditorEl, '', sectionEl );
				sectionEl.remove();
			}
		},
	};

	apiRequest( url, method, data, callBack );
};

// Handle Add section with api
const addSectionApi = ( data, courseEditorEl ) => {
	const url = lplistAPI.admin.apiAddSection;
	const method = 'POST';

	const callBack = {
		success: ( response ) => {
			const html = response.data.html ?? [];
			addSectionsWithDelay( html, courseEditorEl );
		},
	};

	apiRequest( url, method, data, callBack );
};

const updateOrderSectionApi = ( data ) => {
	const url = lplistAPI.admin.apiUpdateSectionOrder;
	const method = 'POST';
	apiRequest( url, method, data );
};

const updateSectionItemApi = ( data ) => {
	const url = lplistAPI.admin.apiUpdateSectionItem;
	const method = 'PUT';
	apiRequest( url, method, data );
};

const addNewItemApi = ( data, newSectionItemInputEl, sectionEl, courseEditorEl ) => {
	const url = lplistAPI.admin.apiAddNewSectionItem;
	const method = 'POST';
	const callBack = {
		success: ( response ) => {
			if ( newSectionItemInputEl ) {
				newSectionItemInputEl.value = '';
			}

			const html = response?.data?.html ?? '';
			if ( ! sectionEl || ! html ) {
				return;
			}

			const listUiEl = sectionEl.querySelector( '.ui-sortable' );
			if ( ! listUiEl ) {
				return;
			}

			listUiEl.insertAdjacentHTML( 'beforeend', html );
			const sectionItemEl = listUiEl.lastElementChild;
			const sectionId = sectionEl?.dataset?.sectionId ?? 0;
			handleEventSectionItem( sectionItemEl, sectionId, sectionEl, courseEditorEl );
			updateTotalItemSection( sectionEl, 1 );
			updateTotalItemSection( courseEditorEl, 1 );
		},
	};

	apiRequest( url, method, data, callBack );
};

const removeItemInSectionApi = ( data, itemRemoveEl, sectionEl, courseEditorEl ) => {
	const url = lplistAPI.admin.apiRemoveItemInSection;
	const method = 'DELETE';
	const callBack = {
		completed: () => {
			if ( itemRemoveEl ) {
				itemRemoveEl.remove();
			}
			if ( sectionEl ) {
				updateTotalItemSection( sectionEl, -1 );
				updateTotalItemSection( courseEditorEl, -1 );
			}
		},
	};

	apiRequest( url, method, data, callBack );
};

const deleteItemApi = ( data, itemRemoveEl, sectionEl, courseEditorEl ) => {
	const url = lplistAPI.admin.apiDeleteSectionItem;
	const method = 'DELETE';
	const callBack = {
		completed: () => {
			if ( itemRemoveEl ) {
				itemRemoveEl.remove();
			}
			if ( sectionEl && courseEditorEl ) {
				updateTotalItemSection( sectionEl, -1 );
				updateTotalItemSection( courseEditorEl, -1 );
			}
		},
	};
	apiRequest( url, method, data, callBack );
};

const updateOrderSectionItemApi = ( data ) => {
	const url = lplistAPI.admin.apiUpdateSectionItemOrder;
	const method = 'POST';
	apiRequest( url, method, data );
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
		inputItem.addEventListener( 'blur', function() {
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
	input.addEventListener( 'keydown', function( event ) {
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
	input.addEventListener( 'blur', function() {
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
			const sectionEls = Array.prototype.slice.call( courseEditorEl.querySelectorAll( '.curriculum-sections > .section' ) );
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
			popupSelectItem( sectionEl );
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
			radio.addEventListener( 'change', function() {
				newSectionItemEl.querySelectorAll( '.type' ).forEach( ( label ) => label.classList.remove( 'current' ) );
				this.closest( 'label' ).classList.add( 'current' );
			} );
		} );

		const newSectionItemInputEl = newSectionItemEl.querySelector( '.title input' );
		if ( newSectionItemInputEl ) {
			let previousValue = newSectionItemInputEl?.value;
			newSectionItemInputEl.addEventListener( 'keydown', function( event ) {
				if ( event.key === 'Enter' ) {
					event.preventDefault();
					const currentValue = newSectionItemInputEl.value;
					if ( previousValue !== currentValue && currentValue !== '' ) {
						previousValue = currentValue;
						const selectedValue = newSectionItemEl.querySelector( '.type.current input' )?.value ?? '';
						const courseId = getCourseId();
						const data = {
							sectionId,
							item: {
								title: currentValue,
								type: selectedValue,
							},
							courseId,
						};
						addNewItemApi( data, newSectionItemInputEl, sectionEl, courseEditorEl );
					}
				}
			} );
			newSectionItemInputEl.addEventListener( 'blur', function() {
				const currentValue = newSectionItemInputEl.value;
				if ( previousValue !== currentValue && currentValue !== '' ) {
					previousValue = currentValue;
					const selectedValue = newSectionItemEl.querySelector( '.type.current input' )?.value ?? '';
					const courseId = getCourseId();
					const data = {
						sectionId,
						item: {
							title: currentValue,
							type: selectedValue,
						},
						courseId,
					};
					addNewItemApi( data, newSectionItemInputEl, sectionEl, courseEditorEl );
				}
			} );
		}
	}

	const sectionItemEls = Array.prototype.slice.call( listItemEl.querySelectorAll( 'li.section-item' ) );
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
		titleSection.addEventListener( 'blur', function() {
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
		descSection.addEventListener( 'blur', function() {
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
			const sectionItemEls = Array.prototype.slice.call( changeSortableEl.querySelectorAll( ':scope > .section-item' ) );
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
			updateTotalItemSection( sectionEl, -1 );
		},
		onUpdate( evt ) {
			const sectionItemEls = Array.prototype.slice.call( sortableEl.querySelectorAll( ':scope > .section-item' ) );
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
			const sectionItemEls = Array.prototype.slice.call( changeSortableEl.querySelectorAll( ':scope > .section-item' ) );
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
			const sectionEls = Array.prototype.slice.call( sortableEl.querySelectorAll( ':scope > .section' ) );
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

const getHtmlCurriculum = ( courseEditorEl ) => {
	const courseId = getCourseId();
	const url = lplistAPI.admin.apiCurriculumHTML + '/' + courseId;
	const callBack = {
		success: ( response ) => {
			const html = response.data.html ?? [];
			addSectionsWithDelay( html, courseEditorEl );
		},
	};

	lpFetchAPI( url, {}, callBack );
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

document.addEventListener( 'DOMContentLoaded', () => {
	const courseEditorEl = document.querySelector( '#course-editor-refactor' );
	if ( ! courseEditorEl ) {
		return;
	}

	getHtmlCurriculum( courseEditorEl );
	collapseSectionsEvent( courseEditorEl );
	addNewSection( courseEditorEl );
	sortableSection( courseEditorEl );
	handleEventPopup();
} );
