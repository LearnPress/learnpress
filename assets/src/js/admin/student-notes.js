/**
 * Student Notes uses LearnPress's shared SweetAlert dialogs, loading and note endpoints.
 *
 * @since 4.4.9.2
 */
import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import { __ } from '@wordpress/i18n';
import SweetAlert from 'sweetalert2';

export class StudentNotesAdmin {
	constructor() {
		this.isRequesting = false;
	}

	init() {
		lpUtils.eventHandlers( 'submit', [
			{ selector: '.lp-student-notes__edit-form', class: this, callBack: this.handleSave.name },
		] );
		lpUtils.eventHandlers( 'click', [
			{ selector: '[data-note-template]', class: this, callBack: this.handleOpen.name },
			{ selector: '.lp-student-notes__delete', class: this, callBack: this.handleDelete.name },
			{ selector: '.lp-student-notes__cancel', class: this, callBack: this.handleCancel.name },
		] );
	}

	handleOpen( args ) {
		args.e.preventDefault();
		const button = args.target.closest( '[data-note-template]' );
		const template = button ? document.getElementById( button.dataset.noteTemplate ) : null;
		if ( ! template || this.isRequesting ) {
			return;
		}
		const content = document.createElement( 'div' );
		content.className = 'lp-student-notes__dialog-content';
		content.appendChild( template.content.cloneNode( true ) );
		SweetAlert.fire( {
			title: button.dataset.title || '',
			html: content,
			width: 640,
			showConfirmButton: false,
			showCloseButton: true,
			allowOutsideClick: () => ! this.isRequesting,
			allowEscapeKey: () => ! this.isRequesting,
		} );
	}

	handleCancel( args ) {
		args.e.preventDefault();
		if ( ! this.isRequesting ) {
			SweetAlert.close();
		}
	}

	handleSave( args ) {
		args.e.preventDefault();
		const form = args.target.closest( '.lp-student-notes__edit-form' );
		if ( ! form || this.isRequesting || ! form.reportValidity() ) {
			return;
		}
		this.request( {
			action: 'lp_note_save',
			note_id: form.dataset.noteId,
			content: form.elements.content.value,
		}, form.querySelector( '[type="submit"]' ), form );
	}

	async handleDelete( args ) {
		args.e.preventDefault();
		const button = args.target.closest( '.lp-student-notes__delete' );
		if ( ! button || this.isRequesting ) {
			return;
		}
		const result = await SweetAlert.fire( {
			title: button.dataset.confirm,
			icon: 'warning',
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: __( 'Cancel', 'learnpress' ),
			confirmButtonText: __( 'Delete', 'learnpress' ),
			reverseButtons: true,
		} );
		if ( result.isConfirmed && ! this.isRequesting ) {
			this.request( { action: 'lp_note_delete', note_id: button.dataset.noteId }, button );
		}
	}

	request( data, button, form = null ) {
		this.isRequesting = true;
		lpUtils.lpSetLoadingEl( button, 1 );
		const controls = form ? [ ...form.elements ] : [ button ];
		controls.forEach( ( control ) => {
			control.disabled = true;
		} );
		const close = SweetAlert.getCloseButton();
		if ( close ) {
			close.disabled = true;
		}
		window.lpAJAXG.fetchAJAX( data, {
			success: ( response ) => {
				lpToastify.show( response.message, response.status === 'success' ? 'success' : 'error' );
				if ( response.status === 'success' ) {
					window.location.reload();
				}
			},
			error: () => lpToastify.show( __( 'An error occurred. Please try again.', 'learnpress' ), 'error' ),
			completed: () => {
				this.isRequesting = false;
				lpUtils.lpSetLoadingEl( button, 0 );
				controls.forEach( ( control ) => {
					control.disabled = false;
				} );
				if ( close ) {
					close.disabled = false;
				}
			},
		} );
	}
}

new StudentNotesAdmin().init();
