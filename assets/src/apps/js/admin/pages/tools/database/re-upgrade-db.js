import lpModalOverlay from '../../../../utils/lp-modal-overlay';
import handleAjax from '../../../../utils/handle-ajax-api';

const reUpgradeDB = () => {
	const elToolReUpgradeDB = document.querySelector( '#lp-tool-re-upgrade-db' );

	if ( ! elToolReUpgradeDB ) {
		return;
	}

	// Check valid to show popup re-upgrade
	let url = 'lp/v1/database/check-db-valid-re-upgrade';
	handleAjax( url, {}, {
		success( res ) {
			const { data: { can_re_upgrade } } = res;

			if ( ! can_re_upgrade ) {
				return;
			}

			elToolReUpgradeDB.style.display = 'block';

			const elBtnReUpradeDB = elToolReUpgradeDB.querySelector( '.lp-btn-re-upgrade-db' );
			const elMessage = elToolReUpgradeDB.querySelector( '.learn-press-message' );

			elBtnReUpradeDB.addEventListener( 'click', () => {
				// eslint-disable-next-line no-alert
				if ( confirm( 'Are you want to Re Upgrade!' ) ) {
					url = 'lp/v1/database/del-tb-lp-upgrade-db';
					handleAjax( url, {}, {
						success( res ) {
							const { status, message, data: { url } } = res;

							if ( 'success' === status && undefined !== url ) {
								window.location.href = url;
							}
						},
						error( err ) {
							elMessage.classList.add( 'error' );
							elMessage.textContent = err.message;
							elMessage.style.display = 'block';
						},
					} );
				}
			} );
		},
		error( err ) {

		},
	} );
};

export default reUpgradeDB;
