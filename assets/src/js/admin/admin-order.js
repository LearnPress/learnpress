import export_invoice from './order/export_invoice';
import modalSearchCourses from './order/modal-search-courses';

document.addEventListener( 'DOMContentLoaded', ( event ) => {
	modalSearchCourses();
	export_invoice();
} );
