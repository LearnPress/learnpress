/**
 * Admin Student Notes page: show a note's details in the LearnPress modal (SweetAlert2).
 * The details are rendered by PHP in a <template> next to each "View note" button.
 *
 * @since 4.4.9.2
 * @version 1.0.0
 */
import SweetAlert from 'sweetalert2';
import * as lpUtils from 'lpAssetsJsPath/utils.js';

export class StudentNotesAdmin {
	static selectors = {
		viewBtn: '.lp-student-notes__view',
	};

	init() {
		lpUtils.eventHandlers( 'click', [
			{
				selector: StudentNotesAdmin.selectors.viewBtn,
				class: this,
				callBack: this.handleView.name,
			},
		] );
	}

	handleView( args ) {
		args.e.preventDefault();

		const btn = args.target.closest( StudentNotesAdmin.selectors.viewBtn );
		const template = btn ? document.getElementById( btn.dataset.template ) : null;
		if ( ! template ) {
			return;
		}

		SweetAlert.fire( {
			title: btn.dataset.title || '',
			html: template.innerHTML,
			width: 640,
			showConfirmButton: false,
			showCloseButton: true,
			customClass: { popup: 'lp-student-notes-modal' },
		} );
	}
}

new StudentNotesAdmin().init();
