
import { lpFetchAPI } from '../../utils';
import lplistAPI from '../../api';
import { getCourseId, updateTotalItemSection, addSectionsWithDelay, updateStatus, handleEventSectionItem } from './eventHandlers';

const apiRequest = ( url, method = 'POST', data, callbacks = {} ) => {
	if ( ! url ) {
		return;
	}

	const params = {
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': lpDataAdmin.nonce,
		},
		method,
	};

	if ( method !== 'GET' ) {
		params.body = JSON.stringify( data );
	}

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

const updateSectionApi = ( data ) => {
	const url = lplistAPI.admin.apiUpdateSection;
	const method = 'PUT';
	apiRequest( url, method, data );
};

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

const getCourseApi = ( courseEditorEl ) => {
	const courseId = getCourseId();
	const url = lplistAPI.admin.apiCurriculumHTML + '/' + courseId;
	const callBack = {
		success: ( response ) => {
			const html = response.data.html ?? [];
			addSectionsWithDelay( html, courseEditorEl );
		},
	};

	apiRequest( url, 'GET', '', callBack );
};

export { getCourseApi, updateOrderSectionItemApi, deleteItemApi, removeItemInSectionApi, addNewItemApi, updateSectionItemApi, updateOrderSectionApi, addSectionApi, deleteSectionApi, updateSectionApi };
