/**
 * Script handle admin notices.
 *
 * @since 4.1.7.3.2
 * @version 1.0.3
 */
import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';

class AdminNotices {
	static selectors = {
		elAddonsNewVersionTotals: '.lp-addons-new-version-totals',
		elBtnDismiss: '.btn-lp-notice-dismiss',
		elAdminMenu: '#adminmenu',
		elTabLP: '#toplevel_page_learn_press',
		elTabLPName: '.wp-menu-name',
		elTabLPNotice: '.tab-lp-admin-notice',
		elTabLPAddons: 'a[href="admin.php?page=learn-press-addons"]',
		elMenuAddons: '.lp-menu-addons',
		elUpdatePlugins: '.update-plugins',
		elMessage: '.learn-press-message',
	};

	init() {
		lpUtils.lpOnElementReady(
			AdminNotices.selectors.elAddonsNewVersionTotals,
			() => {
				this.notifyAddonsNewVersion();
			}
		);

		this.events();
	}

	notifyAddonsNewVersion() {
		try {
			const elAdminMenu = document.querySelector(
				AdminNotices.selectors.elAdminMenu
			);
			if ( ! elAdminMenu ) {
				return;
			}

			const elTabLP = elAdminMenu.querySelector(
				AdminNotices.selectors.elTabLP
			);
			if ( ! elTabLP ) {
				return;
			}
			const elTabLPName = elTabLP.querySelector(
				AdminNotices.selectors.elTabLPName
			);
			if ( ! elTabLPName ) {
				return;
			}
			const elAddonsNewVerTotal = document.querySelector(
				AdminNotices.selectors.elAddonsNewVersionTotals
			);
			if ( ! elAddonsNewVerTotal ) {
				return;
			}
			const htmlNotifyLP = `<span class="tab-lp-admin-notice"></span>`;
			if (
				! elTabLPName.querySelector(
					AdminNotices.selectors.elTabLPNotice
				)
			) {
				elTabLPName.insertAdjacentHTML( 'beforeend', htmlNotifyLP );
			}
			const elTabLPAddons = elTabLP.querySelector(
				AdminNotices.selectors.elTabLPAddons
			);
			if ( ! elTabLPAddons ) {
				return;
			}

			const total = elAddonsNewVerTotal.dataset.total;
			const elMenuAddons = elTabLPAddons.querySelector(
				AdminNotices.selectors.elMenuAddons
			);
			if ( ! elMenuAddons ) {
				return;
			}

			elTabLPAddons.setAttribute(
				'href',
				'admin.php?page=learn-press-addons&tab=update'
			);
			elMenuAddons.textContent = total;
			elMenuAddons.classList.remove( lpUtils.lpClassName.hidden );
		} catch ( e ) {
			console.log( e );
		}
	}

	/*** Events ***/
	events() {
		if ( AdminNotices._loadedEvents ) {
			return;
		}
		AdminNotices._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: AdminNotices.selectors.elBtnDismiss,
				class: this,
				callBack: this.dismissNotice.name,
			},
		] );
	}

	dismissNotice( args ) {
		const { e, target } = args;
		const el = target.closest( AdminNotices.selectors.elBtnDismiss );
		if ( ! el ) {
			return;
		}

		e.preventDefault();

		// eslint-disable-next-line no-alert
		if ( confirm( el.dataset.message ) ) {
			const parent = el.closest( AdminNotices.selectors.elMessage );
			const dataSend = JSON.parse( el.dataset.send );
			window.lpAJAXG.fetchAJAX( dataSend, {
				success: ( response ) => {
					const { message, status } = response;
					lpToastify.show( message, status );
					if ( status === 'success' ) {
						parent?.remove();
					}
				},
				error: ( error ) => {
					lpToastify.show( error, 'error' );
				},
			} );
		}
	}
}

const adminNotices = new AdminNotices();
adminNotices.init();
