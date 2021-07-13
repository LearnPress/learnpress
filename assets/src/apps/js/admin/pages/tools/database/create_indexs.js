import lpModalOverlay from '../../../../utils/lp-modal-overlay';
import handleAjax from '../../../../utils/handle-ajax-api';

const createIndexes = () => {
	const elCreateIndexTables = document.querySelector( '#lp-tool-create-indexes-tables' );

	if ( ! elCreateIndexTables ) {
		return;
	}

	const elBtnCreateIndexes = elCreateIndexTables.querySelector( '.lp-btn-create-indexes' );

	elBtnCreateIndexes.addEventListener( 'click', ( e ) => {
		e.preventDefault();
		const elLoading = elCreateIndexTables.querySelector( '.wrapper-lp-loading' );

		if ( ! lpModalOverlay.init() ) {
			return;
		}

		lpModalOverlay.elLPOverlay.show();
		lpModalOverlay.setContentModal( elLoading.innerHTML );
		lpModalOverlay.setTitleModal( elCreateIndexTables.querySelector( 'h2' ).textContent );
		lpModalOverlay.elBtnYes[ 0 ].style.display = 'inline-block';
		lpModalOverlay.elBtnYes[ 0 ].textContent = 'Run';
		lpModalOverlay.elBtnNo[ 0 ].textContent = 'Close';

		const url = '/lp/v1/admin/tools/list-tables-indexs';
		const params = {};
		const functions = {
			success: ( res ) => {
				const { status, message, data: { tables, table } } = res;
				const elSteps = document.querySelector( '.example-lp-group-step' );

				lpModalOverlay.setContentModal( elSteps.innerHTML );

				const elGroupStep = lpModalOverlay.elLPOverlay[ 0 ].querySelector( '.lp-group-step ' );

				// Show progress when upgrading.
				const showProgress = ( stepCurrent, percent ) => {
					const elItemStepCurrent = elGroupStep.querySelector( 'input[value=' + stepCurrent + ']' ).closest( '.lp-item-step' );
					elItemStepCurrent.classList.add( 'running' );

					if ( 100 === percent ) {
						elItemStepCurrent.classList.remove( 'running' );
						elItemStepCurrent.classList.add( 'completed' );
					}

					const progressBar = elItemStepCurrent.querySelector( '.progress-bar' );
					progressBar.style.width = percent;
				};

				// Scroll to step current.
				const scrollToStepCurrent = ( stepCurrent ) => {
					const elItemStepCurrent = elGroupStep.querySelector( 'input[value=' + stepCurrent + ']' ).closest( '.lp-item-step' );

					const offset = elItemStepCurrent.offsetTop - lpModalOverlay.elMainContent[ 0 ].offsetTop +
						lpModalOverlay.elMainContent[ 0 ].scrollTop;

					lpModalOverlay.elMainContent.stop().animate( {
						scrollTop: offset,
					}, 600 );
				};

				for ( const table in tables ) {
					const elItemStep = lpModalOverlay.elLPOverlay[ 0 ].querySelector( '.lp-item-step' ).cloneNode( true );
					const input = elItemStep.querySelector( 'input' );
					const label = elItemStep.querySelector( 'label' );

					label.textContent = `Table: ${ table }`;
					input.value = table;

					elGroupStep.append( elItemStep );
				}

				lpModalOverlay.callBackYes = () => {
					const url = '/lp/v1/admin/tools/create-indexs';
					const params = { tables, table };

					lpModalOverlay.elBtnNo[ 0 ].style.display = 'none';
					lpModalOverlay.elBtnYes[ 0 ].style.display = 'none';

					showProgress( table, 0.1 );

					const functions = {
						success: ( res ) => {
							const { status, message, data: { table, percent } } = res;

							showProgress( params.table, percent );

							if ( undefined !== table ) {
								if ( params.table !== table ) {
									showProgress( table, 0.1 );
									scrollToStepCurrent( table );
								}

								params.table = table;
							}

							if ( 'success' === status ) {
								setTimeout( () => {
									handleAjax( url, params, functions );
								}, 2000 );
							} else if ( 'finished' === status ) {
								console.log( 'finished' );
								lpModalOverlay.elBtnNo[ 0 ].style.display = 'inline-block';
								lpModalOverlay.elBtnNo[ 0 ].textContent = 'Finish';
							} else {
								console.log( message );
							}
						},
						error: ( err ) => {
							console.log( err );
						},
						completed: () => {

						},
					};
					handleAjax( url, params, functions );
				};
			},
			error: ( err ) => {

			},
			completed: () => {

			},
		};

		handleAjax( url, params, functions );
	} );
};

export default createIndexes;
