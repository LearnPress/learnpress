/**
 * List API on backend
 */

const lplistAPI = {};

if ( 'undefined' !== typeof lpDataAdmin ) {
	lplistAPI.admin = {
		apiAdminNotice: lpDataAdmin.rest + 'lp/v1/admin/tools/admin-notices',
		apiAdminOrderStatic: lpDataAdmin.rest + 'lp/v1/orders/statistic',
		apiAddons: lpDataAdmin.rest + 'lp/v1/addon/all',
		apiAddonAction: lpDataAdmin.rest + 'lp/v1/addon/action',
		apiSearchCourses: lpDataAdmin.rest + 'lp/v1/admin/tools/search-course',
		apiAssignUserCourse: lpDataAdmin.rest + 'lp/v1/admin/tools/assign-user-course',
	};
}

if ( 'undefined' !== typeof lpData ) {
	lplistAPI.frontend = {
		apiWidgets: lpData.lp_rest_url + 'lp/v1/widgets/api',
		apiCourses: lpData.lp_rest_url + 'lp/v1/courses/archive-course',
	};
}

export default lplistAPI;
