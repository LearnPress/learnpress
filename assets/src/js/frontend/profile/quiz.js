/**
 * Handle click tab call API
 *
 * @since 4.2.8.2
 * @version 1.0.0
 */
const profileQuizTab = () => {
	const handleClickTab = ( e, target ) => {
		if ( target.closest( 'span' ) ) {
			const elParent = target.closest( '#profile-content-quizzes' );
			if ( ! elParent ) {
				return;
			}

			const elLPTarget = target.closest( '.lp-target' );
			if ( ! elLPTarget ) {
				return;
			}

			window.lpAJAXG.showHideLoading( elLPTarget, 1 );
			const dataSendJson = elLPTarget?.dataset?.send || {};
			const dataSend = JSON.parse( dataSendJson );

			const elTabChoice = target?.dataset?.filter || 'all';

			const liActive = elParent.querySelector( 'li.active' );
			if ( liActive.classList.contains( elTabChoice ) ) {
				return;
			}

			liActive.classList.remove( 'active' );
			const liTarget = target.closest( 'li' );
			liTarget.classList.add( 'active' );

			dataSend.args.type = elTabChoice;

			// Load list courses by AJAX.
			const callBack = {
				success: ( response ) => {
					const { data, message, status } = response;

					if ( 'success' === status ) {
						elLPTarget.innerHTML = data.content || '';
					}
				},
				error: ( error ) => {
					console.log( error );
				},
				completed: () => {
					window.lpAJAXG.showHideLoading( elLPTarget, 0 );
				},
			};

			window.lpAJAXG.fetchAJAX( dataSend, callBack );
		}
	};

	document.addEventListener( 'click', ( e ) => {
		const target = e.target;

		handleClickTab( e, target );
	} );
};

export default profileQuizTab;
