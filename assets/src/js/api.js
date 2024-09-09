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
