import lpModalOverlay from '../../../../utils/lp-modal-overlay';
import handleAjax from '../../../../utils/handle-ajax-api';

const $ = jQuery;

const elToolUpgradeDB = $( '#lp-tool-upgrade-db' );

const upgradeDB = () => {
	let isUpgrading = 0;
	const elWrapperTermsUpgrade = elToolUpgradeDB.find( '.wrapper-terms-upgrade' );
	const elStatusUpgrade = elToolUpgradeDB.find( '.wrapper-lp-status-upgrade' );
	const elWrapperUpgradeMessage = elToolUpgradeDB.find( '.wrapper-lp-upgrade-message' );
	let checkValidBeforeUpgrade = null;

	if ( elWrapperTermsUpgrade.length ) { // Show Terms Upgrade.
		lpModalOverlay.setContentModal( elWrapperTermsUpgrade.html() );

		const elTermUpdate = lpModalOverlay.elLPOverlay.find( '.terms-upgrade' );
		const elLPAgreeTerm = elTermUpdate.find( 'input[name=lp-agree-term]' );
		const elTermMessage = elTermUpdate.find( '.error' );
		const elMessageUpgrading = $( 'input[name=message-when-upgrading]' ).val();

		checkValidBeforeUpgrade = function() {
			elTermMessage.hide();
			elTermMessage.removeClass( 'learn-press-message' );

			if ( elLPAgreeTerm.is( ':checked' ) ) {
				handleAjax( '/lp/v1/database/agree_terms', { agree_terms: 1 }, {} );

				lpModalOverlay.elFooter.find( '.learn-press-notice' ).remove();
				lpModalOverlay.elFooter.prepend( '<span class="learn-press-notice">' + elMessageUpgrading + '</span>' );
				lpModalOverlay.setContentModal( elStatusUpgrade.html() );

				return true;
			}

			elTermMessage.show();
			elTermMessage.addClass( 'learn-press-message' );
			lpModalOverlay.elMainContent.animate( {
				scrollTop: elTermMessage.offset().top,
			} );

			return false;
		};
	} else { // Show Steps Upgrade.
		lpModalOverlay.setContentModal( elStatusUpgrade.html() );
		checkValidBeforeUpgrade = function() {
			return true;
		};
	}

	lpModalOverlay.setTitleModal( elToolUpgradeDB.find( 'h2' ).html() );
	lpModalOverlay.elBtnYes.text( 'Upgrade' );
	lpModalOverlay.elBtnYes.show();
	lpModalOverlay.elBtnNo.text( 'close' );
	lpModalOverlay.callBackYes = function() {
		if ( ! checkValidBeforeUpgrade() ) {
			return;
		}

		isUpgrading = 1;

		lpModalOverlay.elBtnYes.hide();
		lpModalOverlay.elBtnNo.hide();

		const urlHandle = '/lp/v1/database/upgrade';
		const elGroupStep = lpModalOverlay.elLPOverlay.find( '.lp-group-step' );
		const elItemSteps = elToolUpgradeDB.find( '.lp-item-step' );

		// Get params.
		const steps = [];

		$.each( elItemSteps, function( i, el ) {
			const elItemStepsTmp = $( el );

			if ( ! elItemStepsTmp.hasClass( 'completed' ) ) {
				const step = elItemStepsTmp.find( 'input' ).val();
				steps.push( step );
			}
		} );

		const params = {
			steps,
			step: steps[ 0 ],
		};

		let elItemStepCurrent = null;

		// Show progress when upgrading.
		const showProgress = ( stepCurrent, percent ) => {
			elItemStepCurrent = elGroupStep.find( 'input[value=' + stepCurrent + ']' ).closest( '.lp-item-step' );
			elItemStepCurrent.addClass( 'running' );

			if ( 100 === percent ) {
				elItemStepCurrent.removeClass( 'running' ).addClass( 'completed' );
			}

			elItemStepCurrent.find( '.progress-bar' ).css( 'width', percent + '%' );
			elItemStepCurrent.find( '.percent' ).text( percent + '%' );
		};

		// Scroll to step current.
		const scrollToStepCurrent = ( stepCurrent ) => {
			elItemStepCurrent = elGroupStep.find( 'input[value=' + stepCurrent + ']' ).closest( '.lp-item-step' );

			if ( ! elItemStepCurrent.length ) {
				return;
			}

			const offset = elItemStepCurrent.offset().top - lpModalOverlay.elMainContent.offset().top +
				lpModalOverlay.elMainContent.scrollTop();

			lpModalOverlay.elMainContent.stop().animate( {
				scrollTop: offset,
			}, 600 );
		};

		showProgress( steps[ 0 ], 0.1 );

		const funcCallBack = {
			success: ( res ) => {
				showProgress( params.step, res.percent );

				if ( params.step !== res.name ) {
					showProgress( res.name, 0.1 );
				}

				scrollToStepCurrent( params.step );

				if ( 'success' === res.status ) {
					params.step = res.name;
					params.data = res.data;

					setTimeout( () => {
						handleAjax( urlHandle, params, funcCallBack );
					}, 800 );
				} else if ( 'finished' === res.status ) {
					isUpgrading = 0;
					elItemStepCurrent.removeClass( 'running' ).addClass( 'completed' );
					setTimeout( () => {
						lpModalOverlay.setContentModal( elWrapperUpgradeMessage.html() );
					}, 1000 );
					lpModalOverlay.elFooter.find( '.learn-press-notice' ).remove();
					lpModalOverlay.elBtnNo.show();
					lpModalOverlay.elBtnNo.on( 'click', () => {
						window.location.reload();
					} );
				} else {
					isUpgrading = 0;
					lpModalOverlay.elFooter.find( '.learn-press-notice' ).remove();
					elItemStepCurrent.removeClass( 'running' ).addClass( 'error' );
					lpModalOverlay.setContentModal( elWrapperUpgradeMessage.html(), function() {
						lpModalOverlay.elBtnYes.text( 'Retry' ).show();
						lpModalOverlay.callBackYes = () => {
							window.location.href = lpGlobalSettings.siteurl + '/wp-admin/admin.php?page=learn-press-tools&tab=database&action=upgrade-db';
						};
						lpModalOverlay.elBtnNo.show();

						if ( ! res.message ) {
							res.message = 'Upgrade not success! Please clear cache, restart sever then retry or contact to LP to help';
						}

						lpModalOverlay.elMainContent.find( '.learn-press-message' ).addClass( 'error' ).html( res.message );
					} );
				}
			},
			error: ( err ) => {
				isUpgrading = 0;
				lpModalOverlay.setContentModal( elWrapperUpgradeMessage.html(), function() {
					lpModalOverlay.elBtnYes.text( 'Retry' ).show();
					lpModalOverlay.callBackYes = () => {
						window.location.location = 'wp-admin/admin.php?page=learn-press-tools&tab=database&action=upgrade-db';
					};
					lpModalOverlay.elBtnNo.show();

					if ( ! err.message ) {
						err.message = 'Upgrade not success! Something wrong. Please clear cache, restart sever then retry or contact to LP to help';
					}

					lpModalOverlay.elMainContent.find( '.learn-press-message' ).addClass( 'error' ).html( err.message );
				} );
			},
			completed: () => {

			},
		};

		handleAjax( urlHandle, params, funcCallBack );
	};

	// Show confirm if, within upgrading, the user reload the page.
	window.onbeforeunload = function() {
		if ( isUpgrading ) {
			return 'LP is upgrading Database. Are you want to reload page?';
		}
	};

	// Show confirm if, within upgrading, the user close the page.
	window.onclose = function() {
		if ( isUpgrading ) {
			return 'LP is upgrading Database. Are you want to close page?';
		}
	};
};

const getStepsUpgradeStatus = () => {
	if ( ! elToolUpgradeDB.length ) {
		return;
	}

	// initial LP Modal Overlay
	if ( ! lpModalOverlay.init() ) {
		return;
	}

	const elWrapperStatusUpgrade = $( '.wrapper-lp-status-upgrade' );
	const urlHandle = '/lp/v1/database/get_steps';

	// Show dialog upgrade database.
	const queryString = window.location.search;
	const urlParams = new URLSearchParams( queryString );
	const action = urlParams.get( 'action' );

	if ( 'upgrade-db' === action ) {
		lpModalOverlay.elLPOverlay.show();
		lpModalOverlay.setTitleModal( elToolUpgradeDB.find( 'h2' ).html() );
		lpModalOverlay.setContentModal( $( '.wrapper-lp-loading' ).html() );
	}

	const funcCallBack = {
		success: ( res ) => {
			const { steps_completed, steps_default } = res;

			if ( undefined === steps_completed || undefined === steps_default ) {
				console.log( 'invalid steps_completed and steps_default' );
				return false;
			}

			// Render show Steps.
			let htmlStep = '';
			for ( const k_gr_steps in steps_default ) {
				const step_group = steps_default[ k_gr_steps ];
				const steps = step_group.steps;

				htmlStep = '<div class="lp-group-step">';
				htmlStep += '<h3>' + step_group.label + '</h3>';

				for ( const k_step in steps ) {
					const step = steps[ k_step ];
					let completed = '';

					if ( undefined !== steps_completed[ k_step ] ) {
						completed = 'completed';
					}

					htmlStep += '<div class="lp-item-step ' + completed + '">';
					htmlStep += '<div class="lp-item-step-left"><input type="hidden" name="lp_steps_upgrade_db[]" value="' + step.name + '"  /></div>';
					htmlStep += '<div class="lp-item-step-right">';
					htmlStep += '<label for=""><strong></strong>' + step.label + '</label>';
					htmlStep += '<div class="description">' + step.description + '</div>';
					htmlStep += '<div class="percent"></div>';
					htmlStep += '<span class="progress-bar"></span>';
					htmlStep += '</div>';
					htmlStep += '</div>';
				}

				htmlStep += '</div>';

				elWrapperStatusUpgrade.append( htmlStep );

				const elBtnUpgradeDB = $( '.lp-btn-upgrade-db' );

				if ( 'upgrade-db' === action ) {
					upgradeDB();
				}

				elBtnUpgradeDB.on( 'click', function() {
					lpModalOverlay.elLPOverlay.show();
					upgradeDB();
				} );
			}
		},
		error: ( err ) => {

		},
		completed: () => {

		},
	};

	handleAjax( urlHandle, {}, funcCallBack );
};

export default getStepsUpgradeStatus;
