/**
 * Single course - user actions on course.
 *
 * HTML of buttons is rendered by PHP (SingleCourseTemplate, UserCourseTemplate), this file only:
 * - Read params from data-send of buttons, call window.lpAJAXG.fetchAJAX (UserCourseAjax actions).
 * - Nonce of actions (UserCourseAjax::$action_nonce) is read from attribute data-lp-action-nonce of form,
 *   send via field 'lp_action_nonce'.
 *
 * - Confirm (SweetAlert) when button has data-title.
 * - Error is shown on .lp-ajax-message of form, or toast when form has not it.
 *
 * Enroll, purchase course (form submit): replace REST API lp/v1/courses/enroll-course, purchase-course.
 * Retake course: replace REST API lp/v1/courses/retake-course (assets/src/apps/js/frontend/single-course.js).
 * Extra boxes (requirements, features, target audiences - templates/single-course/extra-info.php):
 * accordion, replace accordionExtraTab (assets/src/apps/js/frontend/single-course.js).
 * Tabs of course (templates/single-course/tabs/tabs.php, SingleCourseClassicTemplate): set active nav tab,
 * replace initCourseTabs (assets/src/apps/js/frontend/single-course.js), not save cookie 'course-tab' (not read).
 * Nav tabs drag to scroll, click tab scrolls it to center: replace TabsDragScroll (apps/js/frontend/tabs-scroll.js).
 * Social share toggle, copy link of course (SingleCourseModernLayout): replace LPCopyToClipboard (js/frontend/copy-to-clipboard.js).
 * Materials of course (CourseMaterialTemplate): load more, replace lpMaterialsLoad on single course page
 * (assets/src/apps/js/frontend/material.js is still used on page item of course - single-curriculum.js).
 *
 * @since 4.5.1
 * @version 1.0.0
 */
import SweetAlert from 'sweetalert2';
import * as lpUtils from '../utils.js';
import * as lpToastify from '../lpToastify.js';

export class SingleCourse {
	static selectors = {
		elFormEnroll: 'form.enroll-course',
		elBtnEnroll: '.button-enroll-course[data-send]',
		elFormPurchase: 'form.purchase-course',
		elBtnPurchase: '.button-purchase-course[data-send]',
		elRepurchaseOption: '[name=_lp_allow_repurchase_type]',
		elCourseDetailInfo: '.course-detail-info',
		elFormRetake: '.lp-form-retake-course',
		elBtnRetake: '.button-retake-course[data-send]',
		elAjaxMessage: '.lp-ajax-message',
		elExtraBox: '.course-extra-box',
		elExtraBoxTitle: '.course-extra-box__title',
		elExtraBoxContent: '.course-extra-box__content',
		elBtnLoadMoreMaterial: 'button.lp-loadmore-material',
		elLoadMoreMaterialWrap: 'div.lp-loadmore-material',
		elMaterialTarget: '.lp-target',
		elMaterialTableBody: 'table.course-material-table tbody',
		elCourseTabs: '#learn-press-course-tabs',
		elCourseTabRadio: '#learn-press-course-tabs input[name="learn-press-course-tab-radio"]',
		elTabsDragScroll: '.TabsDragScroll',
		elTabDragScrollItem: '.TabsDragScroll ul > li',
		elShareToggle: '.social-share-toggle',
		elShareToggleIcon: '.share-toggle-icon',
		elShareToggleClose: '.social-share-toggle__close',
		elShareContent: '.content-widget-social-share',
		elBtnClipboard: '.btn-clipboard',
		elClipboardWrap: '.clipboard-post',
		elClipboardValue: '.clipboard-value',
	};

	/**
	 * Class of social share toggle is opened.
	 */
	static classShareOpen = 'social-share-toggle__open';

	constructor() {
		this.isRequesting = false;
		this.i18n = {};
	}

	init() {
		// Localized by LP_Assets::_get_script_data() (handle lp-single-course-v2).
		this.i18n = window.lpSingleCourseV2Settings?.i18n || {};
		this.initExtraBoxes();
		this.initTabsDragScroll();
		this.reloadWhenBackForward();
		this.events();
	}

	events() {
		if ( SingleCourse._loadedEvents ) {
			return;
		}
		SingleCourse._loadedEvents = true;

		const handler = ( selector, callBack ) => ( { selector, class: this, callBack } );

		lpUtils.eventHandlers( 'click', [
			handler( SingleCourse.selectors.elBtnRetake, this.retakeCourse.name ),
			handler( SingleCourse.selectors.elExtraBoxTitle, this.toggleExtraBox.name ),
			handler( SingleCourse.selectors.elBtnLoadMoreMaterial, this.loadMoreMaterial.name ),
			handler( SingleCourse.selectors.elTabDragScrollItem, this.scrollTabToCenter.name ),
			handler( SingleCourse.selectors.elShareToggleIcon, this.toggleShare.name ),
			handler( SingleCourse.selectors.elShareToggleClose, this.closeShareByButton.name ),
			handler( SingleCourse.selectors.elBtnClipboard, this.copyToClipboard.name ),
		] );

		// Click outside social share: close it.
		document.addEventListener( 'click', ( e ) => this.closeShareOutside( e ) );
		document.addEventListener( 'keydown', ( e ) => {
			if ( e.key === 'Escape' ) {
				this.closeAllShare();
			}
		} );

		lpUtils.eventHandlers( 'change', [
			handler( SingleCourse.selectors.elCourseTabRadio, this.changeCourseTab.name ),
		] );

		lpUtils.eventHandlers( 'submit', [
			handler( SingleCourse.selectors.elFormEnroll, this.enrollCourse.name ),
			handler( SingleCourse.selectors.elFormPurchase, this.purchaseCourse.name ),
			handler( SingleCourse.selectors.elFormRetake, this.preventSubmit.name ),
		] );
	}

	// ---------------------------------------------------------------------
	// Actions
	// ---------------------------------------------------------------------

	/**
	 * Submit form enroll course, redirect when success (first item, or checkout page for guest).
	 *
	 * @param {Object} args
	 */
	enrollCourse( args ) {
		const { e, target } = args;
		const elForm = target.closest( SingleCourse.selectors.elFormEnroll );
		const elBtn = elForm?.querySelector( SingleCourse.selectors.elBtnEnroll );
		if ( ! elBtn ) {
			return;
		}

		e.preventDefault();

		this.request(
			elBtn,
			elForm,
			( data, message ) => this.onSuccessRedirect( elBtn, elForm, data, message ),
			{
				// Security: markup must not choose server action.
				action: 'user_enroll_course',
			}
		);
	}

	/**
	 * Submit form purchase course, redirect to checkout page when success.
	 * Course allow repurchase: show popup options, then send again with option chose.
	 *
	 * @param {Object} args
	 */
	purchaseCourse( args ) {
		const { e, target } = args;
		const elForm = target.closest( SingleCourse.selectors.elFormPurchase );
		const elBtn = elForm?.querySelector( SingleCourse.selectors.elBtnPurchase );
		if ( ! elBtn ) {
			return;
		}

		e.preventDefault();

		this.doPurchase( elBtn, elForm );
	}

	/**
	 * Call AJAX purchase course.
	 *
	 * @param {Element} elBtn          button has data-send
	 * @param {Element} elForm         form purchase, fields are sent (addons can add)
	 * @param {string}  repurchaseType option user chose on popup repurchase
	 */
	doPurchase( elBtn, elForm, repurchaseType = '' ) {
		this.request(
			elBtn,
			elForm,
			( data, message ) => {
				if ( data.type === 'allow_repurchase' ) {
					this.showRepurchaseOptions( elBtn, elForm, data );
					// Keep button enabled, user can close popup and click again.
					return false;
				}

				this.onSuccessRedirect( elBtn, elForm, data, message );
			},
			{
				// Security: markup must not choose server action.
				action: 'user_purchase_course',
				form_data: this.getFormData( elForm ),
				repurchase_type: repurchaseType,
			}
		);
	}

	/**
	 * Show popup options repurchase (HTML from server), send purchase again with option chose.
	 *
	 * @param {Element} elBtn
	 * @param {Element} elForm
	 * @param {Object}  data   { html, title_popup }
	 */
	showRepurchaseOptions( elBtn, elForm, data ) {
		SweetAlert.fire( {
			title: data.title_popup || '',
			html: data.html || '',
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: this.i18n.cancel || 'Cancel',
			confirmButtonText: this.i18n.yes || 'Yes',
			reverseButtons: true,
			preConfirm: () => {
				const elOption = SweetAlert.getPopup()?.querySelector( `${ SingleCourse.selectors.elRepurchaseOption }:checked` );

				return elOption ? elOption.value : '';
			},
		} ).then( ( result ) => {
			if ( result.isConfirmed && result.value ) {
				this.doPurchase( elBtn, elForm, result.value );
			}
		} );
	}

	/**
	 * Enroll, purchase success: show message, hide button, redirect.
	 *
	 * @param {Element} elBtn
	 * @param {Element} elForm
	 * @param {Object}  data    { redirect }
	 * @param {string}  message
	 */
	onSuccessRedirect( elBtn, elForm, data, message ) {
		elBtn.style.display = 'none';

		if ( message ) {
			const elMessage = document.createElement( 'div' );
			elMessage.className = 'learn-press-message success';
			elMessage.textContent = this.toText( message );
			elForm.append( elMessage );
		}

		if ( data.redirect ) {
			window.location.href = data.redirect;
		}
	}

	/**
	 * Retake course, redirect to item continue (or course) when success.
	 *
	 * @param {Object} args
	 */
	retakeCourse( args ) {
		const { e, target } = args;
		const elBtn = target.closest( SingleCourse.selectors.elBtnRetake );
		const elForm = elBtn?.closest( SingleCourse.selectors.elFormRetake );
		if ( ! elBtn || ! elForm ) {
			return;
		}

		e.preventDefault();

		if ( this.isRequesting || elBtn.disabled ) {
			return;
		}

		this.confirm( elBtn, () => this.doRetake( elBtn, elForm ) );
	}

	/**
	 * Call AJAX retake course, redirect when success.
	 *
	 * @param {Element} elBtn  button has data-send
	 * @param {Element} elForm form retake
	 */
	doRetake( elBtn, elForm ) {
		this.request(
			elBtn,
			elForm,
			( data, message ) => {
				this.showMessage( elForm, message );
				elBtn.style.display = 'none';

				if ( data.url_redirect ) {
					setTimeout( () => {
						window.location.replace( data.url_redirect );
					}, 1000 );
				}
			},
			{
				// Security: markup must not choose server action.
				action: 'user_retake_course',
			}
		);
	}

	/**
	 * Collapse all extra boxes when load page.
	 * Script is loaded 'defer', so HTML is parsed.
	 */
	initExtraBoxes() {
		document.querySelectorAll( SingleCourse.selectors.elExtraBox ).forEach( ( elBox ) => {
			this.collapseExtraBox( elBox );
		} );
	}

	/**
	 * Click title of extra box: open it and close others (accordion), or close it when opened.
	 *
	 * @param {Object} args
	 */
	toggleExtraBox( args ) {
		const elTitle = args.target.closest( SingleCourse.selectors.elExtraBoxTitle );
		const elBox = elTitle?.closest( SingleCourse.selectors.elExtraBox );
		if ( ! elBox ) {
			return;
		}

		const isActive = elBox.classList.contains( 'active' );

		document.querySelectorAll( SingleCourse.selectors.elExtraBox ).forEach( ( elOther ) => {
			if ( elOther !== elBox && elOther.classList.contains( 'active' ) ) {
				this.collapseExtraBox( elOther );
			}
		} );

		if ( isActive ) {
			this.collapseExtraBox( elBox );
		} else {
			this.expandExtraBox( elBox );
		}
	}

	/**
	 * Expand extra box, height animates to height of content, then 'auto' to fit when content wraps (resize).
	 *
	 * @param {Element} elBox
	 */
	expandExtraBox( elBox ) {
		elBox.classList.add( 'active' );

		const elContent = elBox.querySelector( SingleCourse.selectors.elExtraBoxContent );
		if ( ! elContent ) {
			return;
		}

		elContent.style.height = elContent.scrollHeight + 'px';
		elContent.addEventListener(
			'transitionend',
			() => {
				if ( elBox.classList.contains( 'active' ) ) {
					elContent.style.height = 'auto';
				}
			},
			{ once: true }
		);
	}

	/**
	 * Collapse extra box.
	 *
	 * @param {Element} elBox
	 */
	collapseExtraBox( elBox ) {
		elBox.classList.remove( 'active' );

		const elContent = elBox.querySelector( SingleCourse.selectors.elExtraBoxContent );
		if ( ! elContent ) {
			return;
		}

		// Height 'auto' can't animate, set fixed height first.
		if ( elContent.style.height === 'auto' ) {
			elContent.style.height = elContent.scrollHeight + 'px';
			// Force reflow, so browser animates from fixed height to 0.
			void elContent.offsetHeight;
		}

		elContent.style.height = '0';
	}

	/**
	 * Change tab of course: set class 'active' for nav tab (li) of tab chose.
	 * Panel of tab is shown by CSS (input:checked ~ .course-tab-panels),
	 * nav tabs are wrapped (.wrapper-course-nav-tabs), CSS can't select, so JS sets 'active'.
	 *
	 * @param {Object} args
	 */
	changeCourseTab( args ) {
		const elRadio = args.target.closest( SingleCourse.selectors.elCourseTabRadio );
		const elTabs = elRadio?.closest( SingleCourse.selectors.elCourseTabs );
		if ( ! elTabs || ! elRadio.checked ) {
			return;
		}

		const elNavActive = [ ...elTabs.querySelectorAll( 'label[for]' ) ]
			.find( ( elLabel ) => elLabel.htmlFor === elRadio.id )
			?.closest( 'li' );
		if ( ! elNavActive ) {
			return;
		}

		[ ...elNavActive.parentElement.children ].forEach( ( elNav ) => {
			elNav.classList.toggle( 'active', elNav === elNavActive );
		} );
	}

	/**
	 * Nav tabs: drag (mouse) to scroll horizontal.
	 */
	initTabsDragScroll() {
		const elMenus = [ ...document.querySelectorAll( SingleCourse.selectors.elTabsDragScroll ) ]
			.map( ( elWrap ) => elWrap.querySelector( 'ul' ) )
			.filter( Boolean );
		if ( ! elMenus.length ) {
			return;
		}

		let elMenuDragging = null;
		elMenus.forEach( ( elMenu ) => {
			elMenu.addEventListener( 'mousedown', () => {
				elMenuDragging = elMenu;
			} );
			elMenu.addEventListener( 'mousemove', ( e ) => {
				if ( elMenuDragging !== elMenu ) {
					return;
				}

				elMenu.scrollLeft -= e.movementX;
				elMenu.classList.add( 'dragging' );
			} );
		} );

		document.addEventListener( 'mouseup', () => {
			if ( elMenuDragging ) {
				elMenuDragging.classList.remove( 'dragging' );
				elMenuDragging = null;
			}
		} );
	}

	/**
	 * Click nav tab: scroll it to center of nav tabs.
	 *
	 * @param {Object} args
	 */
	scrollTabToCenter( args ) {
		const elLi = args.target.closest( SingleCourse.selectors.elTabDragScrollItem );
		const elMenu = elLi?.parentElement;
		if ( ! elMenu || typeof elMenu.scrollTo !== 'function' ) {
			return;
		}

		elMenu.scrollTo( {
			left: elLi.offsetLeft - ( ( elMenu.clientWidth / 2 ) - ( elLi.offsetWidth / 2 ) ),
			behavior: 'smooth',
		} );
	}

	/**
	 * Click icon share: open / close social share.
	 *
	 * @param {Object} args
	 */
	toggleShare( args ) {
		const elToggle = args.target.closest( SingleCourse.selectors.elShareToggleIcon )
			?.closest( SingleCourse.selectors.elShareToggle );
		if ( ! elToggle ) {
			return;
		}

		args.e.preventDefault();
		elToggle.classList.toggle( SingleCourse.classShareOpen );
	}

	/**
	 * Click button close of social share.
	 *
	 * @param {Object} args
	 */
	closeShareByButton( args ) {
		const elToggle = args.target.closest( SingleCourse.selectors.elShareToggle );
		if ( ! elToggle ) {
			return;
		}

		args.e.preventDefault();
		elToggle.classList.remove( SingleCourse.classShareOpen );
	}

	/**
	 * Click outside content and icon of social share opened: close it.
	 *
	 * @param {Event} e
	 */
	closeShareOutside( e ) {
		document.querySelectorAll( `${ SingleCourse.selectors.elShareToggle }.${ SingleCourse.classShareOpen }` ).forEach( ( elToggle ) => {
			const elContent = elToggle.querySelector( SingleCourse.selectors.elShareContent );
			const elIcon = elToggle.querySelector( SingleCourse.selectors.elShareToggleIcon );
			if ( elContent?.contains( e.target ) || elIcon?.contains( e.target ) ) {
				return;
			}

			elToggle.classList.remove( SingleCourse.classShareOpen );
		} );
	}

	/**
	 * Close all social share opened (press Escape).
	 */
	closeAllShare() {
		document.querySelectorAll( `${ SingleCourse.selectors.elShareToggle }.${ SingleCourse.classShareOpen }` ).forEach( ( elToggle ) => {
			elToggle.classList.remove( SingleCourse.classShareOpen );
		} );
	}

	/**
	 * Copy link of course (input .clipboard-value), show text 'Copied' (data-copied) on button.
	 *
	 * @param {Object} args
	 */
	copyToClipboard( args ) {
		const elBtn = args.target.closest( SingleCourse.selectors.elBtnClipboard );
		if ( ! elBtn ) {
			return;
		}

		args.e.preventDefault();

		const elInput = elBtn.closest( SingleCourse.selectors.elClipboardWrap )?.querySelector( SingleCourse.selectors.elClipboardValue ) ||
			document.querySelector( SingleCourse.selectors.elClipboardValue );
		if ( ! elInput ) {
			return;
		}

		const showCopied = () => {
			const msg = elBtn.dataset.copied || '';
			const elTooltip = document.createElement( 'span' );
			elTooltip.className = 'tooltip';
			elTooltip.textContent = msg;
			elBtn.textContent = msg;
			elBtn.append( elTooltip );
		};

		const copyBySelect = () => {
			elInput.focus();
			elInput.select();
			try {
				if ( document.execCommand( 'copy' ) ) {
					showCopied();
				}
			} catch ( err ) {}
		};

		if ( window.navigator?.clipboard?.writeText && window.isSecureContext ) {
			window.navigator.clipboard.writeText( elInput.value ).then( showCopied, copyBySelect );
		} else {
			copyBySelect();
		}
	}

	/**
	 * Load more materials of course (load_content_via_ajax, CourseMaterialTemplate::render_material_items).
	 * Page is saved on data-send of .lp-target when success.
	 *
	 * @param {Object} args
	 */
	loadMoreMaterial( args ) {
		const ajaxHandle = window.lpAJAXG;
		const elBtn = args.target.closest( SingleCourse.selectors.elBtnLoadMoreMaterial );
		const elTarget = elBtn?.closest( SingleCourse.selectors.elMaterialTarget );
		if ( ! elTarget || elBtn.disabled || ! ajaxHandle || typeof ajaxHandle.fetchAJAX !== 'function' ) {
			return;
		}

		let dataSend;
		try {
			// Action is removed, request is always 'load_content_via_ajax'.
			dataSend = ajaxHandle.getDataSetCurrent( elTarget );
		} catch ( e ) {
			return;
		}

		if ( ! dataSend?.args ) {
			return;
		}

		dataSend.args.paged = ( parseInt( dataSend.args.paged ) || 1 ) + 1;

		elBtn.disabled = true;
		lpUtils.lpSetLoadingEl( elBtn, 1 );

		ajaxHandle.fetchAJAX( dataSend, {
			success: ( response ) => {
				const { status, message, data } = response || {};
				if ( status !== 'success' ) {
					lpToastify.show( this.toText( message ) || this.i18n.error || 'Error', 'error' );
					return;
				}

				elTarget.querySelector( SingleCourse.selectors.elMaterialTableBody )?.insertAdjacentHTML( 'beforeend', data?.content || '' );
				ajaxHandle.setDataSetCurrent( elTarget, dataSend );

				if ( parseInt( data?.paged ) >= parseInt( data?.total_pages ) ) {
					( elBtn.closest( SingleCourse.selectors.elLoadMoreMaterialWrap ) || elBtn ).remove();
				}
			},
			error: ( error ) => {
				lpToastify.show( this.toText( error?.message || String( error ) ), 'error' );
			},
			completed: () => {
				if ( elBtn.isConnected ) {
					elBtn.disabled = false;
					lpUtils.lpSetLoadingEl( elBtn, 0 );
				}
			},
		} );
	}

	/**
	 * Reload single course page when back (browser restores page from cache), to update buttons of course.
	 */
	reloadWhenBackForward() {
		if ( ! document.querySelector( SingleCourse.selectors.elCourseDetailInfo ) ) {
			return;
		}

		window.addEventListener( 'pageshow', ( event ) => {
			const navigation = window.performance?.getEntriesByType?.( 'navigation' )?.[ 0 ];
			if ( event.persisted || navigation?.type === 'back_forward' ) {
				window.location.reload();
			}
		} );
	}

	/**
	 * Prevent submit form (ex: press Enter), action is handled via AJAX.
	 *
	 * @param {Object} args
	 */
	preventSubmit( args ) {
		args.e.preventDefault();
	}

	// ---------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------

	/**
	 * Show confirm (SweetAlert) when button has data-title, same as UserQuiz::confirm.
	 *
	 * @param {Element}  elBtn
	 * @param {Function} callBack called when confirmed or not need confirm
	 */
	confirm( elBtn, callBack ) {
		if ( ! elBtn.dataset.title ) {
			callBack();
			return;
		}

		SweetAlert.fire( {
			title: elBtn.dataset.title,
			text: elBtn.dataset.content,
			icon: 'warning',
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: this.i18n.cancel || 'Cancel',
			confirmButtonText: this.i18n.yes || 'Yes',
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				callBack();
			}
		} );
	}

	/**
	 * Get fields of form as object, field has many values is array.
	 *
	 * @param {Element} elForm
	 * @return {Object} fields
	 */
	getFormData( elForm ) {
		const formData = new FormData( elForm );
		const fields = {};
		[ ...new Set( formData.keys() ) ].forEach( ( key ) => {
			const values = formData.getAll( key );
			fields[ key ] = values.length > 1 ? values : values[ 0 ];
		} );

		return fields;
	}

	/**
	 * Message from server is escaped (esc_html__), decode entities and strip tags to show as text.
	 *
	 * @param {string} message
	 * @return {string} text
	 */
	toText( message ) {
		if ( ! message ) {
			return '';
		}

		return new window.DOMParser().parseFromString( String( message ), 'text/html' ).documentElement.textContent || '';
	}

	/**
	 * Show error on .lp-ajax-message of form, or toast when form has not it.
	 *
	 * @param {Element} elForm
	 * @param {string}  message
	 */
	showError( elForm, message ) {
		if ( elForm.querySelector( SingleCourse.selectors.elAjaxMessage ) ) {
			this.showMessage( elForm, message, true );
			return;
		}

		lpToastify.show( this.toText( message ) || this.i18n.error || 'Error', 'error' );
	}

	/**
	 * Show message on .lp-ajax-message of form.
	 *
	 * @param {Element} elForm
	 * @param {string}  message
	 * @param {boolean} isError
	 */
	showMessage( elForm, message, isError = false ) {
		const elAjaxMessage = elForm.querySelector( SingleCourse.selectors.elAjaxMessage );
		if ( ! elAjaxMessage ) {
			return;
		}

		elAjaxMessage.textContent = this.toText( message );
		elAjaxMessage.classList.toggle( 'error', isError );
		elAjaxMessage.style.display = 'block';
	}

	/**
	 * Call AJAX with data-send of button, add nonce of action from form.
	 * Button keeps disabled when success (page will redirect), except onSuccess returns false.
	 *
	 * @param {Element}  elBtn     button has data-send
	 * @param {Element}  elForm    form has data-lp-action-nonce, show message
	 * @param {Function} onSuccess ( data, message ), return false to enable button again
	 * @param {Object}   extraData merge to data-send
	 */
	request( elBtn, elForm, onSuccess, extraData = {} ) {
		const ajaxHandle = window.lpAJAXG;
		if ( this.isRequesting || elBtn.disabled || ! ajaxHandle || typeof ajaxHandle.fetchAJAX !== 'function' ) {
			return;
		}

		let dataSend;
		try {
			dataSend = JSON.parse( elBtn.dataset.send || '{}' );
		} catch ( e ) {
			return;
		}

		this.isRequesting = true;
		elBtn.disabled = true;
		lpUtils.lpSetLoadingEl( elBtn, 1 );

		let isSuccess = false;
		ajaxHandle.fetchAJAX(
			{
				...dataSend,
				...extraData,
				lp_action_nonce: elForm.dataset.lpActionNonce || '',
			},
			{
				success: ( response ) => {
					if ( response?.status !== 'success' ) {
						this.showError( elForm, response?.message );
						return;
					}

					isSuccess = onSuccess( response.data || {}, response.message || '' ) !== false;
				},
				error: ( error ) => {
					this.showError( elForm, 'error: ' + ( error?.message || error ) );
				},
				completed: () => {
					this.isRequesting = false;
					lpUtils.lpSetLoadingEl( elBtn, 0 );
					if ( ! isSuccess ) {
						elBtn.disabled = false;
					}
				},
			}
		);
	}
}

const singleCourse = new SingleCourse();
singleCourse.init();
