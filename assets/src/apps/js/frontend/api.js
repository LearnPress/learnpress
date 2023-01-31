/**
 * List API on backend
 */
if ( undefined === lpGlobalSettings ) {
	throw new Error( 'lpGlobalSettings is undefined' );
}

export default {
	apiCourses: lpGlobalSettings.lp_rest_url + 'lp/v1/courses/archive-course',
};
