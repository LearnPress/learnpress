import { select } from '@wordpress/data';
const { get, set, isArray } = lodash;

/**
 * Get property of store data.
 *
 * @param state - Store data
 * @param prop - Optional. NULL will return all data.
 * @return {*}
 */
export function getData( state, prop ) {
	if ( prop ) {
		if ( isArray( prop ) ) {
			const ret = {};

			for ( let i = 0; i < prop.length; i++ ) {
				set( ret, prop, get( state, prop ) );
			}

			return ret;
		}

		return get( state, prop );
	}

	return state;
}

export function getDefaultRestArgs( state ) {
	const { userQuiz } = state;

	return {
		item_id: userQuiz.id,
		course_id: userQuiz.courseId,
	};
}
