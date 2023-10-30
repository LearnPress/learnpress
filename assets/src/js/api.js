/**
 * List API on backend
 */

if ( undefined === lpGlobalSettings ) {
	throw new Error( 'lpGlobalSettings is undefined' );
}

export default {
	admin: {
		apiAdminNotice: lpGlobalSettings.rest + 'lp/v1/admin/tools/admin-notices',
		apiAdminOrderStatic: lpGlobalSettings.rest + 'lp/v1/orders/statistic',
		apiAddons: lpGlobalSettings.rest + 'lp/v1/addon/all',
		apiAddonAction: lpGlobalSettings.rest + 'lp/v1/addon/action',
		apiSearchCourses: lpGlobalSettings.rest + 'lp/v1/admin/tools/search-course',
		apiAssignUserCourse: lpGlobalSettings.rest + 'lp/v1/admin/tools/assign-user-course',
	},
};
