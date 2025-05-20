import { getCourseApi } from './apiRequests';
import {
	addNewSection,
	collapseSectionsEvent,
	handleUpdateItem,
	sortableSection,
} from './eventHandlers';
import { handleEventPopup } from '../popupSelectedItem';
import lplistAPI from '../../api';

const editCourse = () => {
	const courseEditorEl = document.querySelector( '#course-editor-refactor' );
	if ( ! courseEditorEl ) {
		return;
	}

	getCourseApi( courseEditorEl );
	collapseSectionsEvent( courseEditorEl );
	addNewSection( courseEditorEl );
	sortableSection( courseEditorEl );
	const API_SEARCH_ITEMS_URL = lplistAPI.admin.apiSearchItems;
	handleEventPopup( handleUpdateItem, API_SEARCH_ITEMS_URL );
};

export { editCourse };
