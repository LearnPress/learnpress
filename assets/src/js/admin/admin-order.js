import export_invoice from './order/export_invoice';
//import modalSearchCourses from './order/modal-search-courses';
import addCoursesToOrder from './order/add-courses-to-order';

document.addEventListener( 'DOMContentLoaded', ( event ) => {
	export_invoice();
} );

addCoursesToOrder();
