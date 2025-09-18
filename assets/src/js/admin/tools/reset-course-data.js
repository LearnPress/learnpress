/**
 * Reset Course Data Tool
 * Nhamdv at September 2025
 *
 */
import * as AdminUtils from '../utils-admin.js';

export default function resetCourseData() {
	const FORM_ID = 'lp-reset-course-progress-form';
	const CONFIRM_MESSAGE = 'Are you sure you want to Reset Course Data?';
	const MESSAGE_TIMEOUT = 3000;
	const SELECTORS = {
		submitButton: '.lp-button-reset-course-data',
		message: '.message',
		progress: '.percent',
	};

	const elements = {};
	let messageTimeout = null;

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
	};

	const bindEvents = () => {
		elements.form.addEventListener( 'submit', handleFormSubmit );
	};

	const handleFormSubmit = ( event ) => {
		event.preventDefault();

		if ( ! confirm( CONFIRM_MESSAGE ) ) {
			return;
		}

		const formData = extractFormData();
		if ( ! validateFormData( formData ) ) {
			showMessage( 'Please select at least one course to reset.', 'error' );
			return;
		}

		processResetRequest( formData );
	};

	const extractFormData = () => {
		const formData = new FormData( elements.form );
		const course_ids = formData.getAll( 'course_ids' ).filter( ( id ) => id.trim() !== '' );

		return { course_ids };
	};

	const validateFormData = ( data ) => {
		return Array.isArray( data.course_ids ) && data.course_ids.length > 0;
	};

	const processResetRequest = ( data ) => {
		const url = AdminUtils.Api.admin.apiResetCourseData;
		const requestData = {
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': lpDataAdmin.nonce,
			},
			method: 'POST',
			body: JSON.stringify( {
				course_ids: data.course_ids.map( ( id ) => parseInt( id, 10 ) ).filter( ( id ) => ! isNaN( id ) ),
			} ),
		};

		setLoadingState( true );
		showMessage( 'Resetting course data...', 'info' );

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
			showMessage( message || 'Course data reset successfully!', 'success' );
			clearForm();
			break;
		case 'error':
			showMessage( message || 'Failed to reset course data.', 'error' );
			break;
		default:
			showMessage( 'Unexpected response received.', 'error' );
		}
	};

	const handleApiError = ( error ) => {
		const errorMessage = error?.message || 'An error occurred while resetting course data.';
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
	};

	const cleanup = () => {
		if ( elements.form ) {
			elements.form.removeEventListener( 'submit', handleFormSubmit );
		}
		if ( messageTimeout ) {
			clearTimeout( messageTimeout );
		}
	};

	document.addEventListener( 'DOMContentLoaded', init );

	return cleanup;
}

