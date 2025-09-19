/**
 * Reset User Progress Tool
 * Nhamdv at September 2025
 *
 */
import * as AdminUtils from '../utils-admin.js';

export default function resetUserProgress() {
	const FORM_ID = 'lp-reset-user-progress-form';
	const CONFIRM_MESSAGE = 'Are you sure you want to Reset User Progress?';
	const MESSAGE_TIMEOUT = 3000;
	const SELECTORS = {
		submitButton: '.lp-button-reset-user-progress',
		message: '.message',
		progress: '.percent',
		autocompleteInput: '#lp-user-autocomplete',
		autocompleteDropdown: '#lp-autocomplete-dropdown',
		autocompleteResults: '.lp-autocomplete-results',
		selectedUsersList: '#lp-selected-users-list',
	};

	const elements = {};
	let messageTimeout = null;
	let searchTimeout = null;
	let selectedUsers = [];

	const init = () => {
		elements.form = document.querySelector( `#${ FORM_ID }` );
		if ( ! elements.form ) {
			return;
		}

		cacheElements();
		bindEvents();
	};

	const cacheElements = () => {
		elements.submitButton = elements.form.querySelector( SELECTORS.submitButton );
		elements.message = elements.form.querySelector( SELECTORS.message );
		elements.progress = elements.form.querySelector( SELECTORS.progress );
		elements.autocompleteInput = elements.form.querySelector( SELECTORS.autocompleteInput );
		elements.autocompleteDropdown = elements.form.querySelector( SELECTORS.autocompleteDropdown );
		elements.autocompleteResults = elements.form.querySelector( SELECTORS.autocompleteResults );
		elements.selectedUsersList = elements.form.querySelector( SELECTORS.selectedUsersList );
	};

	const bindEvents = () => {
		elements.form.addEventListener( 'submit', handleFormSubmit );

		if ( elements.autocompleteInput ) {
			elements.autocompleteInput.addEventListener( 'input', handleSearchInput );
			elements.autocompleteInput.addEventListener( 'keydown', handleSearchKeydown );
			elements.autocompleteInput.addEventListener( 'focus', handleInputFocus );
			elements.autocompleteInput.addEventListener( 'blur', handleInputBlur );
		}

		// Close dropdown when clicking outside
		document.addEventListener( 'click', handleDocumentClick );
	};

	const handleSearchInput = ( event ) => {
		const searchTerm = event.target.value.trim();

		if ( searchTimeout ) {
			clearTimeout( searchTimeout );
		}

		if ( searchTerm.length >= 2 ) {
			searchTimeout = setTimeout( () => {
				searchUsers( searchTerm );
			}, 300 );
		} else if ( searchTerm.length === 0 ) {
			hideDropdown();
		}
	};

	const handleSearchKeydown = ( event ) => {
		if ( event.key === 'Enter' ) {
			event.preventDefault();
			const searchTerm = event.target.value.trim();
			if ( searchTerm.length >= 2 ) {
				searchUsers( searchTerm );
			}
		} else if ( event.key === 'Escape' ) {
			hideDropdown();
			elements.autocompleteInput.blur();
		}
	};

	const handleInputFocus = () => {
		if ( elements.autocompleteInput.value.trim().length >= 2 ) {
			showDropdown();
		}
	};

	const handleInputBlur = () => {
		// Delay hiding to allow click events on dropdown items
		setTimeout( () => {
			hideDropdown();
		}, 200 );
	};

	const handleDocumentClick = ( event ) => {
		if ( ! elements.autocompleteDropdown.contains( event.target ) &&
			! elements.autocompleteInput.contains( event.target ) ) {
			hideDropdown();
		}
	};

	const searchUsers = async ( searchTerm ) => {
		try {
			const response = await fetch( `${ lpDataAdmin.lp_rest_url }wp/v2/users?search=${ encodeURIComponent( searchTerm ) }&per_page=10`, {
				headers: {
					'X-WP-Nonce': lpDataAdmin.nonce,
				},
			} );

			if ( ! response.ok ) {
				throw new Error( 'Failed to fetch users' );
			}

			const users = await response.json();
			displaySearchResults( users );
		} catch ( error ) {
			console.error( 'Error searching users:', error );
			showMessage( 'Error searching users. Please try again.', 'error' );
		}
	};

	const displaySearchResults = ( users ) => {
		if ( ! elements.autocompleteResults ) {
			return;
		}

		if ( users.length === 0 ) {
			elements.autocompleteResults.innerHTML = '<div class="lp-autocomplete-item">No users found</div>';
			showDropdown();
			return;
		}

		const resultsHtml = users.map( ( user ) => {
			const isSelected = selectedUsers.some( ( selected ) => selected.id === user.id );
			const selectedClass = isSelected ? ' selected' : '';
			const userName = user.name || user.display_name || user.login;

			return `
				<div class="lp-autocomplete-item${ selectedClass }" data-user-id="${ user.id }" data-user-name="${ userName }">
					<span class="user-name">${ userName }</span>
					<span class="user-id">(ID: ${ user.id })</span>
					<span class="user-email">${ user.email || '' }</span>
					${ isSelected ? '<span class="selected-indicator">✓</span>' : '' }
				</div>
			`;
		} ).join( '' );

		elements.autocompleteResults.innerHTML = resultsHtml;

		// Bind click events to results
		const resultItems = elements.autocompleteResults.querySelectorAll( '.lp-autocomplete-item' );
		resultItems.forEach( ( item ) => {
			item.addEventListener( 'click', handleUserSelect );
		} );

		showDropdown();
	};

	const handleUserSelect = ( event ) => {
		const userId = parseInt( event.currentTarget.dataset.userId );
		const userName = event.currentTarget.dataset.userName;

		// Check if user is already selected
		const existingUser = selectedUsers.find( ( user ) => user.id === userId );
		if ( existingUser ) {
			// Remove user if already selected
			removeUser( userId );
		} else {
			// Add user to selection
			selectedUsers.push( { id: userId, name: userName } );
			updateSelectedUsersDisplay();
		}

		// Clear input and hide dropdown
		elements.autocompleteInput.value = '';
		hideDropdown();
	};

	const removeUser = ( userId ) => {
		selectedUsers = selectedUsers.filter( ( user ) => user.id !== userId );
		updateSelectedUsersDisplay();
	};

	const updateSelectedUsersDisplay = () => {
		if ( ! elements.selectedUsersList ) {
			return;
		}

		if ( selectedUsers.length === 0 ) {
			elements.selectedUsersList.innerHTML = '<p class="no-users">No users selected yet.</p>';
			return;
		}

		const usersHtml = selectedUsers.map( ( user ) => `
			<div class="lp-selected-user-item" data-user-id="${ user.id }">
				${ user.name }
				<span class="remove-user" onclick="removeUser(${ user.id })" title="Remove user">×</span>
			</div>
		` ).join( '' );

		elements.selectedUsersList.innerHTML = usersHtml;
	};

	const showDropdown = () => {
		if ( elements.autocompleteDropdown ) {
			elements.autocompleteDropdown.style.display = 'block';
		}
	};

	const hideDropdown = () => {
		if ( elements.autocompleteDropdown ) {
			elements.autocompleteDropdown.style.display = 'none';
		}
	};

	// Global function for removing users
	window.removeUser = ( userId ) => {
		removeUser( parseInt( userId ) );
	};

	const handleFormSubmit = ( event ) => {
		event.preventDefault();

		if ( ! window.confirm( CONFIRM_MESSAGE ) ) {
			return;
		}

		if ( selectedUsers.length === 0 ) {
			showMessage( 'Please select at least one user to reset.', 'error' );
			return;
		}

		processResetRequest();
	};

	const processResetRequest = () => {
		const url = AdminUtils.Api.admin.apiResetUserProgress;
		const requestData = {
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': lpDataAdmin.nonce,
			},
			method: 'POST',
			body: JSON.stringify( {
				user_ids: selectedUsers.map( ( user ) => user.id ),
			} ),
		};

		setLoadingState( true );
		showMessage( 'Resetting user progress...', 'info' );

		AdminUtils.Utils.lpFetchAPI( url, requestData, {
			success: handleApiSuccess,
			error: handleApiError,
			completed: () => setLoadingState( false ),
		} );
	};

	const handleApiSuccess = ( response ) => {
		const { status, message } = response;

		switch ( status ) {
		case 'success':
			showMessage( message || 'User progress reset successfully!', 'success' );
			clearForm();
			break;
		case 'error':
			showMessage( message || 'Failed to reset user progress.', 'error' );
			break;
		default:
			showMessage( 'Unexpected response received.', 'error' );
		}
	};

	const handleApiError = ( error ) => {
		const errorMessage = error?.message || 'An error occurred while resetting user progress.';
		showMessage( errorMessage, 'error' );
	};

	const showMessage = ( message, type = 'info' ) => {
		if ( ! elements.message ) {
			return;
		}

		if ( messageTimeout ) {
			clearTimeout( messageTimeout );
			messageTimeout = null;
		}

		elements.message.textContent = message;
		elements.message.className = `message message-${ type }`;

		if ( type === 'success' || type === 'info' ) {
			messageTimeout = setTimeout( clearMessage, MESSAGE_TIMEOUT );
		}
	};

	const clearMessage = () => {
		if ( elements.message ) {
			elements.message.textContent = '';
			elements.message.className = 'message';
		}
		messageTimeout = null;
	};

	const setLoadingState = ( isLoading ) => {
		if ( elements.submitButton ) {
			elements.submitButton.disabled = isLoading;
		}

		if ( elements.progress ) {
			elements.progress.textContent = isLoading ? 'Processing...' : '';
		}
	};

	const clearForm = () => {
		if ( elements.form ) {
			elements.form.reset();
		}
		selectedUsers = [];
		updateSelectedUsersDisplay();
		hideDropdown();
	};

	const cleanup = () => {
		if ( elements.form ) {
			elements.form.removeEventListener( 'submit', handleFormSubmit );
		}
		if ( elements.autocompleteInput ) {
			elements.autocompleteInput.removeEventListener( 'input', handleSearchInput );
			elements.autocompleteInput.removeEventListener( 'keydown', handleSearchKeydown );
			elements.autocompleteInput.removeEventListener( 'focus', handleInputFocus );
			elements.autocompleteInput.removeEventListener( 'blur', handleInputBlur );
		}
		document.removeEventListener( 'click', handleDocumentClick );

		if ( messageTimeout ) {
			clearTimeout( messageTimeout );
		}
		if ( searchTimeout ) {
			clearTimeout( searchTimeout );
		}
	};

	document.addEventListener( 'DOMContentLoaded', init );

	return cleanup;
}
