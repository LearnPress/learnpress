/**
 * List API on backend
 *
 * @since 4.2.6
 * @version 1.0.0
 */

const lplistAPI = {};

if ( 'undefined' !== typeof lpDataAdmin ) {
	lplistAPI.admin = {
		apiAdminNotice: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/admin-notices',
		apiAdminOrderStatic: lpDataAdmin.lp_rest_url + 'lp/v1/orders/statistic',
		apiAddons: lpDataAdmin.lp_rest_url + 'lp/v1/addon/all',
		apiAddonAction: lpDataAdmin.lp_rest_url + 'lp/v1/addon/action',
		apiAddonsPurchase: lpDataAdmin.lp_rest_url + 'lp/v1/addon/info-addons-purchase',
		apiSearchCourses: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/search-course',
		apiSearchUsers: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/search-user',
		apiAssignUserCourse: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/assign-user-course',
		apiUnAssignUserCourse: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/unassign-user-course',
		apiAJAX: lpDataAdmin.lp_rest_url + 'lp/v1/load_content_via_ajax/',
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
