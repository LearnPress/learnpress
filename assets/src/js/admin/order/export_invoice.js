/**
 * Export invoice to PDF
 */
export default function export_invoice() {
	let html2pdf_obj, modal;

	document.addEventListener( 'click', ( e ) => {
		const target = e.target;

		if ( target.id === 'lp-invoice__export' ) {
			html2pdf_obj.save();
		} else if ( target.id === 'lp-invoice__update' ) {
			const elOption = document.querySelector( '.export-options__content' );
			const fields = elOption.querySelectorAll( 'input' );
			const fieldNameUnChecked = [];
			fields.forEach( ( field ) => {
				if ( ! field.checked ) {
					fieldNameUnChecked.push( field.name );
				}
			} );

			window.localStorage.setItem( 'lp_invoice_un_fields', JSON.stringify( fieldNameUnChecked ) );
			window.localStorage.setItem( 'lp_invoice_show', 1 );
			window.location.reload();
		}
	} );
	const exportPDF = () => {
		const pdfOptions = {
			margin: [ 0, 0, 0, 5 ],
			filename: document.title,
			image: { type: 'webp' },
			html2canvas: { scale: 2.5 },
			jsPDF: { format: 'a4', orientation: 'p' },
		};
		const html = document.querySelector( '#lp-invoice__content' );
		html2pdf_obj = html2pdf().set( pdfOptions ).from( html );
	};
	const showInfoFields = () => {
		// Get fields name checked
		const fieldsChecked = window.localStorage.getItem( 'lp_invoice_un_fields' );
		const elOptions = document.querySelector( '.export-options__content' );
		const elInvoiceFields = document.querySelectorAll( '.invoice-field' );
		elInvoiceFields.forEach( ( field ) => {
			const nameClass = field.classList[ 1 ];
			if ( fieldsChecked && fieldsChecked.includes( nameClass ) ) {
				field.remove();
				const elOption = elOptions.querySelector( `[name=${ nameClass }]` );
				if ( elOption ) {
					elOption.checked = false;
				}
			}
		} );
		const showInvoice = parseInt( window.localStorage.getItem( 'lp_invoice_show' ) );
		if ( showInvoice === 1 ) {
			modal.style.display = 'block';
		}
	};

	document.addEventListener( 'DOMContentLoaded', () => {
		const elExportSection = document.querySelector( '#order-export__section' );
		if ( ! elExportSection.length ) {
			const tabs = document.querySelectorAll( '.tabs' );
			const tab = document.querySelectorAll( '.tab' );
			const panel = document.querySelectorAll( '.panel' );

			function onTabClick( event ) {
				// deactivate existing active tabs and panel

				for ( let i = 0; i < tab.length; i++ ) {
					tab[ i ].classList.remove( 'active' );
				}

				for ( let i = 0; i < panel.length; i++ ) {
					panel[ i ].classList.remove( 'active' );
				}

				// activate new tabs and panel
				event.target.classList.add( 'active' );
				const classString = event.target.getAttribute( 'data-target' );
				document.getElementById( 'panels' ).getElementsByClassName( classString )[ 0 ].classList.add( 'active' );
			}

			for ( let i = 0; i < tab.length; i++ ) {
				tab[ i ].addEventListener( 'click', onTabClick, false );
			}

			// Get the modal
			modal = document.getElementById( 'myModal' );
			// Get the button that opens the modal
			const btn = document.getElementById( 'order-export__button' );
			// Get the <span> element that closes the modal
			const span = document.getElementsByClassName( 'close' )[ 0 ];
			// When the user clicks on the button, open the modal
			btn.onclick = function() {
				modal.style.display = 'block';
			};

			// When the user clicks on <span> (x), close the modal
			span.onclick = function() {
				modal.style.display = 'none';
				window.localStorage.setItem( 'lp_invoice_show', 0 );
			};

			// When the user clicks anywhere outside the modal, close it
			window.onclick = function( event ) {
				if ( event.target === modal ) {
					modal.style.display = 'none';
					window.localStorage.setItem( 'lp_invoice_show', 0 );
				}
			};

			showInfoFields();
			exportPDF();
		}
	} );
}
