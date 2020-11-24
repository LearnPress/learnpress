/**
 * Toogle form Comment for Lesson.
 *
 * @author Nhamdv.
 */

export const commentForm = () => {
	const btn = document.querySelector( '.lp-lesson-comment-btn' );

	if ( ! btn ) {
		return;
	}

	const btnOpen = btn.textContent;
	const btnClose = btn.dataset.close;
	const hashComment = window.location.hash;

	if ( hashComment.includes( 'comment' ) ) {
		btn.parentNode.classList.add( 'open-comments' );
	}

	const toogleText = ( btn, btnParent ) => {
		if ( btnParent.classList.contains( 'open-comments' ) ) {
			btn.textContent = btnClose;
		} else {
			btn.textContent = btnOpen;
		}
	};

	toogleText( btn, btn.parentNode );

	btn.addEventListener( 'click', ( e ) => {
		e.preventDefault();

		btn.parentNode.classList.toggle( 'open-comments' );
		toogleText( btn, btn.parentNode );
	} );

	// Use for Rest API.
	// const toogle = $( '#learn-press-item-comments-toggle' );

	// toogle.on( 'change', async function() {
	// 	if ( ! toogle[ 0 ].checked ) {
	// 		return;
	// 	}

	// 	const response = await wp.apiFetch( {
	// 		path: 'lp/v1/courses/339/item-comments/50',
	// 	} );

	// 	$( '.learn-press-comments' ).html( response.comments );
	// } );
};
