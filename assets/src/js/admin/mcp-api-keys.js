( function() {
	'use strict';

	const cfg = window.lpMcpApiKeysSettings || {};
	if ( ! cfg.is_mcp_keys_section ) {
		return;
	}

	const ajaxHandle = window.lpAJAXG;
	if ( ! ajaxHandle || typeof ajaxHandle.fetchAJAX !== 'function' ) {
		return;
	}

	const elSubmit = document.getElementById( 'lp-mcp-key-submit' );
	const elStatus = document.getElementById( 'lp-mcp-key-status' );
	const elReveal = document.getElementById( 'lp-mcp-key-reveal' );
	const elConsumerKey = document.getElementById( 'lp-mcp-consumer-key' );
	const elConsumerSecret = document.getElementById( 'lp-mcp-consumer-secret' );

	const lpDataAdmin = window.lpDataAdmin || {};
	const i18n = cfg.i18n || lpDataAdmin.i18n || {};
	const actions = cfg.actions || {};

	const setStatus = ( message = '', isError = false ) => {
		if ( ! elStatus ) {
			return;
		}

		elStatus.textContent = message;
		elStatus.style.color = isError ? '#b32d2e' : '#1e1e1e';
	};

	const setLoadingState = ( el, isLoading ) => {
		if ( ! el ) {
			return;
		}

		el.disabled = !! isLoading;
		el.classList.toggle( 'loading', !! isLoading );
	};

	const refreshKeysTable = async () => {
		const currentList = document.querySelector( '.lp-mcp-key-list' );
		if ( ! currentList ) {
			return;
		}

		try {
			const response = await fetch( window.location.href, {
				method: 'GET',
				credentials: 'same-origin',
				cache: 'no-store',
			} );
			if ( ! response.ok ) {
				return;
			}

			const html = await response.text();
			const parser = new DOMParser();
			const doc = parser.parseFromString( html, 'text/html' );
			const newList = doc.querySelector( '.lp-mcp-key-list' );

			if ( newList && currentList.parentNode ) {
				currentList.replaceWith( newList );
			}
		} catch ( e ) {
			// Keep current UI state when table refresh fails.
		}
	};

	const renderCredentials = ( keyData ) => {
		if (
			! keyData ||
			! keyData.consumer_key ||
			! keyData.consumer_secret ||
			! elConsumerKey ||
			! elConsumerSecret ||
			! elReveal
		) {
			return;
		}

		elConsumerKey.value = keyData.consumer_key;
		elConsumerSecret.value = keyData.consumer_secret;
		elReveal.style.display = 'block';
	};

	const runRequest = ( dataSend, callbacks = {} ) => {
		ajaxHandle.fetchAJAX( dataSend, {
			success: ( response ) => {
				if ( typeof callbacks.success === 'function' ) {
					callbacks.success( response );
				}
			},
			error: ( error ) => {
				if ( typeof callbacks.error === 'function' ) {
					callbacks.error( error );
				}
			},
			completed: () => {
				if ( typeof callbacks.completed === 'function' ) {
					callbacks.completed();
				}
			},
		} );
	};

	const onSubmitKey = () => {
		if ( ! elSubmit ) {
			return;
		}

		const elUser = document.getElementById( 'lp-mcp-key-user' );
		const elDescription = document.getElementById( 'lp-mcp-key-description' );
		const elPermissions = document.getElementById( 'lp-mcp-key-permissions' );

		const dataSend = {
			action: actions.create || 'mcp_create_api_key',
			user_id: elUser ? elUser.value : '',
			description: elDescription ? elDescription.value : '',
			permissions: elPermissions ? elPermissions.value : 'read',
		};

		setLoadingState( elSubmit, true );
		setStatus( i18n.processing || 'Processing...', false );

		runRequest( dataSend, {
			success: ( response ) => {
				const status = response && response.status ? response.status : '';
				const message =
					response && response.message
						? response.message
						: i18n.request_failed || 'Request failed.';

				if ( status !== 'success' ) {
					setStatus( message, true );
					return;
				}

				setStatus( message, false );
				renderCredentials(
					response && response.data && response.data.key
						? response.data.key
						: null
				);

				refreshKeysTable();
			},
			error: () => setStatus( i18n.request_failed || 'Request failed.', true ),
			completed: () => setLoadingState( elSubmit, false ),
		} );
	};

	const onCopy = async ( elCopy ) => {
		const targetId =
			elCopy && elCopy.dataset && elCopy.dataset.target
				? elCopy.dataset.target
				: '';
		if ( ! targetId ) {
			return;
		}

		const input = document.getElementById( targetId );
		if ( ! input ) {
			return;
		}

		try {
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				await navigator.clipboard.writeText( input.value );
			} else {
				input.select();
				input.setSelectionRange( 0, 99999 );
				document.execCommand( 'copy' );
			}

			setStatus( i18n.copy_success || 'Copied.', false );
		} catch ( e ) {
			setStatus( i18n.copy_fallback || 'Copy this value manually.', false );
		}
	};

	document.addEventListener( 'click', ( e ) => {
		const target = e.target;
		if ( ! target ) {
			return;
		}

		const elCopy = target.closest( '.lp-mcp-copy' );
		if ( elCopy ) {
			onCopy( elCopy );
			return;
		}

		if ( elSubmit && target.closest( '#lp-mcp-key-submit' ) ) {
			onSubmitKey();
		}
	} );
} )();
