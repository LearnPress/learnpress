/**
 * Register block list courses.
 */
import { registerBlockType, registerBlockVariation } from '@wordpress/blocks';

const MY_VARIATION_NAME = 'learnpress/list-courses';
const LP_COURSE_CPT = 'lp_course';

registerBlockVariation( 'core/query', {
	name: MY_VARIATION_NAME,
	title: 'List courses',
	description: '',
	isActive: ( { namespace, query } ) => {
		return (
			namespace === MY_VARIATION_NAME &&
				query.postType === LP_COURSE_CPT
		);
	},
	category: 'learnpress-category',
	attributes: {
		namespace: MY_VARIATION_NAME,
		query: {
			perPage: 6,
			pages: 0,
			offset: 0,
			postType: LP_COURSE_CPT,
			order: 'desc',
			orderBy: 'date',
			author: '',
			search: '',
			exclude: [],
			sticky: '',
			inherit: false,
		},
	},
	innerBlocks: [
		[
			'learnpress/course-item',
			{},
			[ [ 'learnpress/course-title' ], [ 'core/post-excerpt' ] ],
		],
	],
}
);
