/**
 * List API on backend
 */

const lplistAPI = {};

if ( 'undefined' !== typeof lpDataAdmin ) {
	lplistAPI.admin = {
		apiAdminNotice: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/admin-notices',
		apiAdminOrderStatic: lpDataAdmin.lp_rest_url + 'lp/v1/orders/statistic',
		apiAddons: lpDataAdmin.lp_rest_url + 'lp/v1/addon/all',
		apiAddonAction: lpDataAdmin.lp_rest_url + 'lp/v1/addon/action',
		apiSearchCourses: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/search-course',
		apiSearchUsers: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/search-user',
		apiAssignUserCourse: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/assign-user-course',
		apiUnAssignUserCourse: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/unassign-user-course',
	};
}

if ( 'undefined' !== typeof lpData ) {
	lplistAPI.frontend = {
		apiWidgets: lpData.lp_rest_url + 'lp/v1/widgets/api',
		apiCourses: lpData.lp_rest_url + 'lp/v1/courses/archive-course',
		apiAJAX: lpData.lp_rest_url + 'lp/v1/load_content_via_ajax/',
	};
}

export default lplistAPI;
