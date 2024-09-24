import { handleEventPopup } from './popupCourse';
import { getCourseApi } from './apiRequests';
import { addNewSection, collapseSectionsEvent, sortableSection } from './eventHandlers';

document.addEventListener( 'DOMContentLoaded', () => {
	const courseEditorEl = document.querySelector( '#course-editor-refactor' );
	if ( ! courseEditorEl ) {
		return;
	}

	getCourseApi( courseEditorEl );
	collapseSectionsEvent( courseEditorEl );
	addNewSection( courseEditorEl );
	sortableSection( courseEditorEl );
	handleEventPopup();
} );
