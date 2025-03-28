/**
 * List API on backend
 *
 * @since 4.2.6
 * @version 1.0.1
 */

const lplistAPI = {};
let lp_rest_url;

if ( 'undefined' !== typeof lpDataAdmin ) {
	lp_rest_url = lpDataAdmin.lp_rest_url;
	lplistAPI.admin = {
		apiAdminNotice: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/admin-notices',
		apiAdminOrderStatic: lpDataAdmin.lp_rest_url + 'lp/v1/orders/statistic',
		apiAddons: lpDataAdmin.lp_rest_url + 'lp/v1/addon/all',
		apiAddonAction: lpDataAdmin.lp_rest_url + 'lp/v1/addon/action-n',
		apiAddonsPurchase: lpDataAdmin.lp_rest_url + 'lp/v1/addon/info-addons-purchase',
		apiSearchCourses: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/search-course',
		apiSearchUsers: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/search-user',
		apiAssignUserCourse: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/assign-user-course',
		apiUnAssignUserCourse: lpDataAdmin.lp_rest_url + 'lp/v1/admin/tools/unassign-user-course',
	};
}

if ( 'undefined' !== typeof lpData ) {
	lp_rest_url = lpData.lp_rest_url;
	lplistAPI.frontend = {
		apiWidgets: lpData.lp_rest_url + 'lp/v1/widgets/api',
		apiCourses: lpData.lp_rest_url + 'lp/v1/courses/archive-course',
		apiAJAX: lpData.lp_rest_url + 'lp/v1/load_content_via_ajax/',
		apiProfileCoverImage: lpData.lp_rest_url + 'lp/v1/profile/cover-image',
	};
}

if ( lp_rest_url ) {
	lplistAPI.apiAJAX = lp_rest_url + 'lp/v1/load_content_via_ajax/';
	lplistAPI.apiCourses = lp_rest_url + 'lp/v1/courses/';
}

export default lplistAPI;
