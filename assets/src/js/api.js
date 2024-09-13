/**
 * List API on backend
 *
 * @since 4.2.6
 * @version 1.0.2
 */

const lplistAPI = {};
let lp_rest_url;

if ( 'undefined' !== typeof lpDataAdmin ) {
	lp_rest_url = lpDataAdmin.lp_rest_url;
	lplistAPI.admin = {
		apiAdminNotice: lp_rest_url + 'lp/v1/admin/tools/admin-notices',
		apiAdminOrderStatic: lp_rest_url + 'lp/v1/orders/statistic',
		apiAddons: lp_rest_url + 'lp/v1/addon/all',
		apiAddonAction: lp_rest_url + 'lp/v1/addon/action-n',
		apiAddonsPurchase: lp_rest_url + 'lp/v1/addon/info-addons-purchase',
		apiSearchCourses: lp_rest_url + 'lp/v1/admin/tools/search-course',
		apiSearchUsers: lp_rest_url + 'lp/v1/admin/tools/search-user',
		apiAssignUserCourse: lp_rest_url + 'lp/v1/admin/tools/assign-user-course',
		apiUnAssignUserCourse: lp_rest_url + 'lp/v1/admin/tools/unassign-user-course',
		apiCurriculumHTML: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/course/html-curriculum',
		apiUpdateSection: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/course/update-section',
		apiDeleteSection: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/course/delete-section',
		apiAddSection: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/course/add-section',
		apiUpdateSectionOrder: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/course/update-order-section',
		apiUpdateSectionItem: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/course/update-section-item',
		apiRemoveItemInSection: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/course/remove-item-in-section',
		apiDeleteSectionItem: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/course/delete-section-item',
		apiAddNewSectionItem: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/course/add-new-section-item',
		apiUpdateSectionItemOrder: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/course/update-order-section-item',
		apiSearchItems: lpDataAdmin.lp_rest_url + 'lp/v1/admin/edit/course/search-items',
	};
}

if ( 'undefined' !== typeof lpData ) {
	lp_rest_url = lpData.lp_rest_url;
	lplistAPI.frontend = {
		apiWidgets: lp_rest_url + 'lp/v1/widgets/api',
		apiCourses: lp_rest_url + 'lp/v1/courses/archive-course',
		apiAJAX: lp_rest_url + 'lp/v1/load_content_via_ajax/',
		apiProfileCoverImage: lp_rest_url + 'lp/v1/profile/cover-image',
	};
}

if ( lp_rest_url ) {
	lplistAPI.apiCourses = lp_rest_url + 'lp/v1/courses/';
}

export default lplistAPI;
