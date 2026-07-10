import SweetAlert from 'sweetalert2';
import * as lpUtils from '../utils.js';

( function() {
	'use strict';

	const cfg = window.lpWebhooksSettings || {};
	if ( ! cfg.is_webhook_section ) {
		return;
	}

	const ajaxHandle = window.lpAJAXG;
	if ( ! ajaxHandle || typeof ajaxHandle.fetchAJAX !== 'function' ) {
		return;
	}

	const actions = cfg.actions || {};
	const i18n = cfg.i18n || {};
	const elId = document.querySelector( '#lp-webhook-id' );
	const elName = document.querySelector( '#lp-webhook-name' );
	const elUrl = document.querySelector( '#lp-webhook-delivery-url' );
	const elSecret = document.querySelector( '#lp-webhook-secret' );
	const elWebhookStatus = document.querySelector( '#lp-webhook-status' );
	const elEvents = document.querySelector( '#lp-webhook-events' );
	const elSubmit = document.querySelector( '#lp-webhook-submit' );
	const elCancel = document.querySelector( '#lp-webhook-cancel' );
	const elRegenerate = document.querySelector( '#lp-webhook-regenerate-editor' );
	const elEditorTitle = document.querySelector( '#lp-webhook-editor-title' );
	const elEditorHolder = document.querySelector( '#lp-webhook-editor-holder' );
	const elEditor = document.querySelector( '#lp-webhook-editor' );
	const elEditorFields = document.querySelector( '#lp-webhook-editor-fields' );
	const elEditorActions = document.querySelector( '#lp-webhook-editor-actions' );
	const elStatusMessage = document.querySelector( '#lp-webhook-status-message' );
	const elSecretReveal = document.querySelector( '#lp-webhook-secret-reveal' );
	const elSecretValue = document.querySelector( '#lp-webhook-secret-value' );
	let isEditorDisabled = true;

	const getEventsTomSelect = () => {
		return elEvents?.tomselect || elEvents?.tomSelectInstance || null;
	};

	const initEventsTomSelect = () => {
		if ( ! elEvents || getEventsTomSelect() ) {
			return;
		}

		if ( typeof window.lpFindTomSelect === 'function' ) {
			window.lpFindTomSelect();
		}
	};

	const setStatus = ( message = '', isError = false ) => {
		if ( ! elStatusMessage ) {
			return;
		}

		elStatusMessage.textContent = message;
		elStatusMessage.style.color = isError ? '#b32d2e' : '#1e1e1e';
	};

	const setLoading = ( isLoading ) => {
		if ( ! elSubmit ) {
			return;
		}

		elSubmit.disabled = !! isLoading || isEditorDisabled;
		elSubmit.classList.toggle( 'loading', !! isLoading );
	};

	const setEditorDisabled = ( isDisabled ) => {
		isEditorDisabled = !! isDisabled;

		[
			elName,
			elUrl,
			elSecret,
			elWebhookStatus,
			elEvents,
			elSubmit,
			elCancel,
			elRegenerate,
		].forEach( ( el ) => {
			if ( ! el ) {
				return;
			}

			el.disabled = isEditorDisabled;
		} );

		const eventsTomSelect = getEventsTomSelect();
		if ( eventsTomSelect ) {
			if ( isEditorDisabled && typeof eventsTomSelect.disable === 'function' ) {
				eventsTomSelect.disable();
			} else if ( typeof eventsTomSelect.enable === 'function' ) {
				eventsTomSelect.enable();
			}
		}

		if ( elSubmit?.classList.contains( 'loading' ) ) {
			elSubmit.disabled = true;
		}
	};

	const setSecretPlaceholder = ( isEditing = false ) => {
		if ( ! elSecret ) {
			return;
		}

		elSecret.placeholder = isEditing
			? i18n.secret_edit_placeholder || 'Leave blank to keep the current secret.'
			: i18n.secret_create_placeholder || 'Leave blank to auto-generate a secret.';
	};

	const setEditorFormVisible = ( isVisible = true ) => {
		if ( elEditorFields ) {
			elEditorFields.style.display = isVisible ? '' : 'none';
		}

		if ( elEditorActions ) {
			elEditorActions.style.display = '';
		}

		if ( ! isVisible ) {
			[ elSubmit, elCancel, elRegenerate ].forEach( ( el ) => {
				if ( el ) {
					el.style.display = 'none';
				}
			} );
		}
	};

	const hideSecret = () => {
		if ( elSecretReveal ) {
			elSecretReveal.style.display = 'none';
		}
		if ( elSecretValue ) {
			elSecretValue.value = '';
		}
	};

	const revealSecret = ( secret ) => {
		if ( ! secret || ! elSecretReveal || ! elSecretValue ) {
			return;
		}

		elSecretValue.value = secret;
		elSecretReveal.style.display = 'block';
	};

	const openEditorPopup = () => {
		if ( ! elEditor || ! elEditorHolder ) {
			return;
		}

		SweetAlert.fire( {
			html: '<div id="lp-webhook-editor-popup"></div>',
			width: 860,
			showConfirmButton: false,
			showCloseButton: true,
			showCancelButton: false,
			focusConfirm: false,
			customClass: {
				popup: 'lp-webhook-editor-popup',
			},
			didOpen: () => {
				const popup =
					typeof SweetAlert.getPopup === 'function' ? SweetAlert.getPopup() : null;
				const mount = popup?.querySelector( '#lp-webhook-editor-popup' );
				if ( ! mount ) {
					return;
				}

				elEditor.style.display = 'block';
				mount.appendChild( elEditor );
				initEventsTomSelect();
				setEditorDisabled( false );
				elName?.focus();
			},
			willClose: () => {
				setEditorDisabled( true );
				elEditor.style.display = 'none';
				elEditorHolder.appendChild( elEditor );
			},
		} );
	};

	const closeEditorPopup = () => {
		if ( typeof SweetAlert.close === 'function' ) {
			SweetAlert.close();
		}
	};

	const setSelectedEvents = ( eventKeys = [] ) => {
		if ( ! elEvents ) {
			return;
		}

		const eventsTomSelect = getEventsTomSelect();
		if ( eventsTomSelect ) {
			eventsTomSelect.setValue( eventKeys, true );
			return;
		}

		elEvents.querySelectorAll( 'option' ).forEach( ( option ) => {
			option.selected = eventKeys.includes( option.value );
		} );
	};

	const getSelectedEvents = () => {
		if ( ! elEvents ) {
			return [];
		}

		const eventsTomSelect = getEventsTomSelect();
		if ( eventsTomSelect ) {
			const value = eventsTomSelect.getValue();

			return Array.isArray( value ) ? value : [ value ].filter( Boolean );
		}

		return Array.from(
			elEvents.querySelectorAll( 'option:checked' )
		).map( ( option ) => option.value );
	};

	const resetEditor = () => {
		if ( elId ) {
			elId.value = '0';
		}
		if ( elName ) {
			elName.value = '';
		}
		if ( elUrl ) {
			elUrl.value = '';
		}
		if ( elSecret ) {
			elSecret.value = '';
		}
		setSecretPlaceholder();
		if ( elWebhookStatus ) {
			elWebhookStatus.value = 'active';
		}
		if ( elEditorTitle ) {
			elEditorTitle.textContent = i18n.create_title || 'Create Webhook';
		}
		if ( elSubmit ) {
			elSubmit.textContent = i18n.create_button || 'Create Webhook';
			elSubmit.style.display = '';
		}
		if ( elCancel ) {
			elCancel.style.display = 'none';
		}
		if ( elRegenerate ) {
			elRegenerate.style.display = 'none';
		}

		setEditorFormVisible();
		setSelectedEvents();
		hideSecret();
		setStatus();
	};

	const populateEditor = ( webhook ) => {
		if ( ! webhook ) {
			return;
		}

		if ( elId ) {
			elId.value = webhook.webhook_id || 0;
		}
		if ( elName ) {
			elName.value = webhook.name || '';
		}
		if ( elUrl ) {
			elUrl.value = webhook.delivery_url || '';
		}
		if ( elSecret ) {
			elSecret.value = '';
		}
		setSecretPlaceholder( true );
		if ( elWebhookStatus ) {
			elWebhookStatus.value = webhook.status || 'active';
		}
		if ( elEditorTitle ) {
			elEditorTitle.textContent = i18n.edit_title || 'Edit Webhook';
		}
		if ( elSubmit ) {
			elSubmit.textContent = i18n.update_button || 'Update Webhook';
			elSubmit.style.display = '';
		}
		if ( elCancel ) {
			elCancel.style.display = '';
		}
		if ( elRegenerate ) {
			elRegenerate.style.display = '';
		}

		setEditorFormVisible();
		setSelectedEvents( Array.isArray( webhook.events ) ? webhook.events : [] );
		hideSecret();
		setStatus();
		openEditorPopup();
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

	const refreshList = async () => {
		const currentList = document.querySelector( '.lp-webhook-list' );
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
			const doc = new DOMParser().parseFromString( html, 'text/html' );
			const newList = doc.querySelector( '.lp-webhook-list' );
			if ( newList ) {
				currentList.replaceWith( newList );
			}
		} catch {
			// Keep the current table when refresh fails.
		}
	};

	const onSubmit = () => {
		if ( ! elSubmit || ! elName || ! elUrl ) {
			return;
		}

		if ( ! elName.reportValidity() || ! elUrl.reportValidity() ) {
			return;
		}

		const webhookId = elId ? parseInt( elId.value, 10 ) || 0 : 0;
		const isUpdate = webhookId > 0;
		const dataSend = {
			action: isUpdate ? actions.update || 'update_webhook' : actions.create || 'create_webhook',
			webhook_id: webhookId,
			name: elName.value,
			delivery_url: elUrl.value,
			secret: elSecret ? elSecret.value : '',
			status: elWebhookStatus ? elWebhookStatus.value : 'active',
			events: getSelectedEvents(),
		};

		setLoading( true );
		setStatus( i18n.processing || 'Processing...' );

		runRequest( dataSend, {
			success: ( response ) => {
				const message = response?.message || i18n.request_failed || 'Request failed.';
				if ( response?.status !== 'success' ) {
					setStatus( message, true );
					return;
				}

				refreshList();

				if ( isUpdate ) {
					closeEditorPopup();
					resetEditor();
					return;
				}

				const createdSecret = response?.data?.webhook?.secret || '';
				resetEditor();
				if ( elEditorTitle ) {
					elEditorTitle.textContent = i18n.created_title || 'Webhook Created';
				}
				setEditorFormVisible( false );
				revealSecret( createdSecret );
				setStatus( message );
				setEditorDisabled( true );
			},
			error: () => setStatus( i18n.request_failed || 'Request failed.', true ),
			completed: () => setLoading( false ),
		} );
	};

	const onDelete = ( webhookId ) => {
		if ( ! webhookId || ! window.confirm( i18n.confirm_delete || 'Delete this webhook?' ) ) {
			return;
		}

		setStatus( i18n.processing || 'Processing...' );
		runRequest(
			{
				action: actions.delete || 'delete_webhook',
				webhook_id: webhookId,
			},
			{
				success: ( response ) => {
					const message = response?.message || i18n.request_failed || 'Request failed.';
					setStatus( message, response?.status !== 'success' );
					if ( response?.status === 'success' ) {
						if ( elId && parseInt( elId.value, 10 ) === webhookId ) {
							resetEditor();
						}
						refreshList();
					}
				},
				error: () => setStatus( i18n.request_failed || 'Request failed.', true ),
			}
		);
	};

	const onRegenerate = ( webhookId ) => {
		if ( ! webhookId || ! window.confirm( i18n.confirm_regenerate || 'Regenerate this webhook secret?' ) ) {
			return;
		}

		setStatus( i18n.processing || 'Processing...' );
		runRequest(
			{
				action: actions.regenerate || 'regenerate_webhook_secret',
				webhook_id: webhookId,
			},
			{
				success: ( response ) => {
					const message = response?.message || i18n.request_failed || 'Request failed.';
					setStatus( message, response?.status !== 'success' );
					if ( response?.status === 'success' ) {
						revealSecret( response?.data?.secret || '' );
						refreshList();
					}
				},
				error: () => setStatus( i18n.request_failed || 'Request failed.', true ),
			}
		);
	};

	const onCopySecret = async () => {
		if ( ! elSecretValue ) {
			return;
		}

		try {
			if ( navigator.clipboard?.writeText ) {
				await navigator.clipboard.writeText( elSecretValue.value );
			} else {
				elSecretValue.select();
				document.execCommand( 'copy' );
			}
			setStatus( i18n.copy_success || 'Copied.' );
		} catch {
			setStatus( i18n.copy_fallback || 'Copy this value manually.' );
		}
	};

	lpUtils.eventHandlers( 'click', [
		{
			selector: '#lp-webhook-open-create',
			callBack: ( args ) => {
				const { e } = args;
				e.preventDefault();
				resetEditor();
				openEditorPopup();
			},
		},
		{
			selector: '.lp-webhook-edit',
			callBack: ( args ) => {
				const { e, target } = args;
				e.preventDefault();
				const edit = target.closest( '.lp-webhook-edit' );
				try {
					populateEditor( JSON.parse( edit.dataset.webhook || '{}' ) );
				} catch {
					setStatus( i18n.request_failed || 'Request failed.', true );
				}
			},
		},
		{
			selector: '.lp-webhook-delete',
			callBack: ( args ) => {
				const { e, target } = args;
				e.preventDefault();
				const deleteLink = target.closest( '.lp-webhook-delete' );
				onDelete( parseInt( deleteLink.dataset.webhookId, 10 ) || 0 );
			},
		},
		{
			selector: '.lp-webhook-regenerate',
			callBack: ( args ) => {
				const { e, target } = args;
				e.preventDefault();
				const regenerateLink = target.closest( '.lp-webhook-regenerate' );
				onRegenerate( parseInt( regenerateLink.dataset.webhookId, 10 ) || 0 );
			},
		},
		{
			selector: '#lp-webhook-submit',
			callBack: () => {
				onSubmit();
			},
		},
		{
			selector: '#lp-webhook-cancel',
			callBack: () => {
				resetEditor();
				closeEditorPopup();
			},
		},
		{
			selector: '#lp-webhook-regenerate-editor',
			callBack: () => {
				onRegenerate( elId ? parseInt( elId.value, 10 ) || 0 : 0 );
			},
		},
		{
			selector: '#lp-webhook-copy-secret',
			callBack: () => {
				onCopySecret();
			},
		},
	] );

	setSecretPlaceholder();
	setEditorDisabled( true );
} )();
