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

		const mode = elSubmit.dataset.mode || 'create';
		const elUser = document.getElementById( 'lp-mcp-key-user' );
		const elDescription = document.getElementById( 'lp-mcp-key-description' );
		const elPermissions = document.getElementById( 'lp-mcp-key-permissions' );
		const elKeyId = document.getElementById( 'lp-mcp-key-id' );

		const dataSend = {
			action: actions.create || 'mcp_create_api_key',
			user_id: elUser ? elUser.value : '',
			description: elDescription ? elDescription.value : '',
			permissions: elPermissions ? elPermissions.value : 'read',
		};

		if ( 'update' === mode ) {
			dataSend.action = actions.update || 'mcp_update_api_key';
			dataSend.key_id = elKeyId ? elKeyId.value : '';
		}

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
			},
			error: () => setStatus( i18n.request_failed || 'Request failed.', true ),
			completed: () => setLoadingState( elSubmit, false ),
		} );
	};

	const onRegenerate = ( e, elRegenerate ) => {
		e.preventDefault();

		if ( ! elRegenerate || elRegenerate.classList.contains( 'disabled' ) ) {
			return;
		}

		if ( ! window.confirm( i18n.confirm_regen || 'Regenerate this API key?' ) ) {
			return;
		}

		const keyId = elRegenerate.dataset.keyId || '';
		if ( ! keyId ) {
			setStatus( i18n.request_failed || 'Request failed.', true );
			return;
		}

		elRegenerate.classList.add( 'disabled' );
		setStatus( i18n.processing || 'Processing...', false );

		runRequest(
			{
				action: actions.regenerate || 'mcp_regenerate_api_key',
				key_id: keyId,
			},
			{
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
				},
				error: () => setStatus( i18n.request_failed || 'Request failed.', true ),
				completed: () => elRegenerate.classList.remove( 'disabled' ),
			}
		);
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

		const elRegenerate = target.closest( '.lp-mcp-regenerate-key' );
		if ( elRegenerate ) {
			onRegenerate( e, elRegenerate );
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
