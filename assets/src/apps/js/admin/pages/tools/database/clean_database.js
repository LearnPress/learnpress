import lpModalOverlay from '../../../../utils/lp-modal-overlay';
import handleAjax from '../../../../utils/handle-ajax-api';

const cleanDatabases = () => {
	const elCleanDatabases = document.querySelector( '#lp-tool-clean-database' );

	if ( ! elCleanDatabases ) {
		return;
	}

	const elBtnCleanDatabases = elCleanDatabases.querySelector( '.lp-btn-clean-db' );
	elBtnCleanDatabases.addEventListener( 'click', function( e ) {
		e.preventDefault();
		const elToolsSelect = document.querySelector( '#tools-select__id' );
		const ElToolSelectLi = elToolsSelect.querySelectorAll( 'ul li input' );
		const checkedOne = Array.prototype.slice.call( ElToolSelectLi ).some( ( x ) => x.checked );
		const prepareMessage = elCleanDatabases.querySelector( '.tools-prepare__message' );
		if ( checkedOne == false ) {
			prepareMessage.style.display = 'block';
			prepareMessage.textContent = 'You must choose at least one table to take this action';
			return;
		}
		prepareMessage.style.display = 'none';

		const elLoading = elCleanDatabases.querySelector( '.wrapper-lp-loading' );
		if ( ! lpModalOverlay.init() ) {
			return;
		}

		lpModalOverlay.elLPOverlay.show();
		lpModalOverlay.setContentModal( elLoading.innerHTML );
		lpModalOverlay.setTitleModal( elCleanDatabases.querySelector( 'h2' ).textContent );
		lpModalOverlay.elBtnYes[ 0 ].style.display = 'inline-block';
		lpModalOverlay.elBtnYes[ 0 ].textContent = 'Run';
		lpModalOverlay.elBtnNo[ 0 ].textContent = 'Close';
		const listTables = new Array();
		const ElToolSelectLiCheked = elToolsSelect.querySelectorAll( 'ul li input:checked' );
		ElToolSelectLiCheked.forEach( ( e ) => {
			listTables.push( e.value );
		} );
		const tables = listTables[ 0 ];
		const item = elLoading.querySelector( '.progressbar__item' );

		const itemtotal = item.getAttribute( 'data-total' );
		const modal = document.querySelector( '.lp-modal-body .main-content' );
		const notice = modal.querySelector( '.lp-tool__message' );
		if ( itemtotal <= 0 ) {
			lpModalOverlay.elBtnYes[ 0 ].style.display = 'none';
			notice.textContent = ( 'There is no data that need to be repaired in the chosen tables' );
			notice.style.display = 'block';
			return;
		}
		lpModalOverlay.callBackYes = () => {
			// warn user before doing
			const r = confirm( 'The modified data is impossible to be restored. Please backup your website before doing this.' );
			if ( r == false ) {
				return;
			}
			const modal = document.querySelector( '.lp-modal-body .main-content' );
			const notice = modal.querySelector( '.lp-tool__message' );
			notice.textContent = 'This action is in processing. Don\'t close this page';
			notice.style.display = 'block';
			const url = '/lp/v1/admin/tools/clean-tables';
			const params = { tables, itemtotal };

			lpModalOverlay.elBtnNo[ 0 ].style.display = 'none';
			lpModalOverlay.elBtnYes[ 0 ].style.display = 'none';

			const functions = {
				success: ( res ) => {
					const { status, message, data: { processed, percent } } = res;
					const modalItem = modal.querySelector( '.progressbar__item' );
					const progressBarRows = modalItem.querySelector( '.progressbar__rows' );
					const progressPercent = modalItem.querySelector( '.progressbar__percent' );
					const progressValue = modalItem.querySelector( '.progressbar__value' );

					console.log( status );
					if ( 'success' === status ) {
						setTimeout( () => {
							handleAjax( url, params, functions );
						}, 2000 );
						// update processed quantity
						progressBarRows.textContent = processed + ' / ' + itemtotal;
						// update percent
						progressPercent.textContent = '( ' + percent + '%' + ' )';
						// update percent width
						progressValue.style.width = percent + '%';
					} else if ( 'finished' === status ) {
						// Re-update indexs
						progressBarRows.textContent = itemtotal + ' / ' + itemtotal;
						progressPercent.textContent = '( 100% )';
						// Update complete nofication
						const modal = document.querySelector( '.lp-modal-body .main-content' );
						const notice = modal.querySelector( '.lp-tool__message' );
						notice.textContent = 'Process has been completed. Press click the finish button to close this popup';
						notice.style.color = 'white';
						notice.style.background = 'green';
						progressValue.style.width = '100%';
						// Show finish button
						lpModalOverlay.elBtnNo[ 0 ].style.display = 'inline-block';
						lpModalOverlay.elBtnNo[ 0 ].textContent = 'Finish';
						lpModalOverlay.elBtnNo[ 0 ].addEventListener( 'click', function() {
							location.reload();
						} );
					} else {
						console.log( message );
					}
				},
				error: ( err ) => {
					console.log( err );
				},
				completed: () => {

				},
			};
			handleAjax( url, params, functions );
		};
	} );
};
export default cleanDatabases;
