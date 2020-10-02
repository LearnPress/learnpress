/**
 * Toogle form Comment for Lesison.
 */
const $ = window.jQuery || jQuery;

export const commentForm = () => {
	const toogle = $( '#learn-press-item-comments-toggle' );

	toogle.on( 'change', async function() {
		if ( ! toogle[ 0 ].checked ) {
			return;
		}

		const response = await wp.apiFetch( {
			path: 'lp/v1/courses/339/item-comments/50',
		} );

		$( '.learn-press-comments' ).html( response.comments );
	} );
};
