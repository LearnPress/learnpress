( function( $ ) {
	const $doc = $( document );
	let isRunning = false;

	const installSampleCourse = function installSampleCourse( e ) {
		e.preventDefault();

		const $button = $( this );

		if ( isRunning ) {
			return;
		}

		if ( ! confirm( lpGlobalSettings.i18n.confirm_install_sample_data ) ) {
			return;
		}

		$button.addClass( 'disabled' ).html( $button.data( 'installing-text' ) );
		$( '.lp-install-sample__response' ).remove();
		isRunning = true;

		$.ajax( {
			url: $button.attr( 'href' ),
			data: $( '.lp-install-sample__options' ).serializeJSON(),
			success( response ) {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
				isRunning = false;
				$( response ).insertBefore( $button.parent() );
			},
			error() {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
				isRunning = false;
				$( response ).insertBefore( $button.parent() );
			},
		} );
	};

	const uninstallSampleCourse = function uninstallSampleCourse( e ) {
		e.preventDefault();

		const $button = $( this );

		if ( isRunning ) {
			return;
		}

		if ( ! confirm( lpGlobalSettings.i18n.confirm_uninstall_sample_data ) ) {
			return;
		}

		$button.addClass( 'disabled' ).html( $button.data( 'uninstalling-text' ) );
		isRunning = true;

		$.ajax( {
			url: $button.attr( 'href' ),
			success( response ) {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
				isRunning = false;
				$( response ).insertBefore( $button.parent() );
			},
			error() {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
				isRunning = false;
				$( response ).insertBefore( $button.parent() );
			},
		} );
	};

	const clearHardCache = function clearHardCache( e ) {
		e.preventDefault();
		const $button = $( this );

		if ( $button.hasClass( 'disabled' ) ) {
			return;
		}

		$button.addClass( 'disabled' ).html( $button.data( 'cleaning-text' ) );
		$.ajax( {
			url: $button.attr( 'href' ),
			data: {},
			success( response ) {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
			},
			error() {
				$button.removeClass( 'disabled' ).html( $button.data( 'text' ) );
			},
		} );
	};

	const toggleHardCache = function toggleHardCache() {
		$.ajax( {
			url: 'admin.php?page=lp-toggle-hard-cache-option',
			data: { v: this.checked ? 'yes' : 'no' },
			success( response ) {
			},
			error() {
			},
		} );
	};

	const toggleOptions = function toggleOptions( e ) {
		e.preventDefault();
		$( '.lp-install-sample__options' ).toggleClass( 'hide-if-js' );
	};

	let elLPOverlay;
	const lpModalOverlay = {
		elMainContent: null,
		elTitle: null,
		elBtnYes: null,
		elBtnNo: null,
		elCalledModal: null,
		callBackYes: null,
		init() {
			const lpModalOverlay = this;
			this.elMainContent = elLPOverlay.find( '.main-content' );
			this.elTitle = elLPOverlay.find( '.modal-title' );
			this.elBtnYes = elLPOverlay.find( '.btn-yes' );
			this.elBtnNo = elLPOverlay.find( '.btn-no' );

			$( document ).on( 'click', '.close, .btn-no', function() {
				elLPOverlay.hide();
			} );

			$( document ).on( 'click', '.btn-yes', function() {
				lpModalOverlay.callBackYes();
			} );
		},
		setElCalledModal( elCalledModal ) {
			this.elCalledModal = elCalledModal;
		},
		setContentModal( content, event ) {
			this.elMainContent.html( content );
			if ( 'function' === typeof event ) {
				event();
			}
		},
		setTitleModal( content ) {
			this.elTitle.html( content );
		},
	};

	const upgradeDB = function upgradeDB() {
		const queryString = window.location.search;
		const urlParams = new URLSearchParams( queryString );
		const action = urlParams.get( 'action' );

		if ( 'upgrade-db' === action ) {
			const elToolUpgradeDB = $( '#lp-tool-upgrade-db' );

			if ( ! elToolUpgradeDB.length ) {
				return;
			}

			lpModalOverlay.init();
			lpModalOverlay.setContentModal( elToolUpgradeDB.find( '.lp-wrapper-status-upgrade' ).html(), function() {
				$( document ).on( 'click', '.lp-item-step', function( e ) {
					const input = $( e.target ).find( 'input' );
					if ( input.attr( 'checked' ) ) {
						input.attr( 'checked', false );
					} else {
						input.attr( 'checked', true );
					}
				} );
			} );
			lpModalOverlay.setTitleModal( elToolUpgradeDB.find( 'h2' ).text() );
			lpModalOverlay.elBtnYes.text( 'Upgrade' );
			lpModalOverlay.callBackYes = function() {
				const urlHandle = '/lp/v1/database/upgrade';
				const elGroupStep = elLPOverlay.find( '.lp-group-step' );
				const elItemSteps = elLPOverlay.find( '.lp-item-step' );

				// Get params.
				const steps = [];

				$.each( elLPOverlay.find( 'input[name=\'lp_steps_upgrade_db[]\']' ), function( i, el ) {
					if ( $( el ).prop( 'checked' ) ) {
						steps.push( $( el ).val() );
					}
				} );

				const params = {
					steps,
					step: steps[ 0 ],
				};

				// Show progess when upgrading.
				const showProgress = ( stepCurrent, percent ) => {
					elItemStepCurrent = elGroupStep.find( 'input[value=' + stepCurrent + ']' ).closest( '.lp-item-step' );
					elItemStepCurrent.addClass( 'running' );

					if ( 100 === percent ) {
						elItemStepCurrent.addClass( 'completed' ).removeClass( 'running' );
					}

					elItemStepCurrent.find( '.progress-bar' ).css( 'width', percent + '%' );
				};

				// Set all
				elItemSteps.find( 'input' ).attr( 'disabled', 'disabled' );

				showProgress( steps[ 0 ], 1 );

				const funcCallBack = {
					success: ( res ) => {
						showProgress( params.step, res.percent );

						showProgress( res.name, 1 );

						if ( 'success' === res.status ) {
							params.step = res.name;
							params.data = res.data;

							setTimeout( () => {
								handleAjax( urlHandle, params, funcCallBack );
							}, 2000 );
						} else if ( 'finished' === res.status ) {

						} else {
							elItemStepCurrent.removeClass( 'running' ).addClass( 'error' );
						}
					},
					error: ( err ) => {
						console.log( err );
					},
					completed: () => {

					},
				};

				handleAjax( urlHandle, params, funcCallBack );
			};

			$( '.lp-overlay' ).css( 'display', 'block' );
		}
	};

	const handleAjax = function( url, params, functions ) {
		wp.apiFetch( {
			path: url,
			method: 'POST',
			data: params,
		} ).then( ( res ) => {
			if ( 'function' === typeof functions.success ) {
				functions.success( res );
			}
		} ).catch( ( err ) => {
			if ( 'function' === typeof functions.error ) {
				functions.error( err );
			}
		} ).then( () => {
			if ( 'function' === typeof functions.completed ) {
				functions.completed();
			}
		} );
	};

	$( function() {
		elLPOverlay = $( '.lp-overlay' );
		upgradeDB();

		$doc.on( 'click', '.lp-install-sample__install', installSampleCourse )
			.on( 'click', '.lp-install-sample__uninstall', uninstallSampleCourse )
			.on( 'click', '#learn-press-clear-cache', clearHardCache )
			.on( 'click', 'input[name="enable_hard_cache"]', toggleHardCache )
			.on( 'click', '.lp-install-sample__toggle-options', toggleOptions );
	} );
}( jQuery ) );
