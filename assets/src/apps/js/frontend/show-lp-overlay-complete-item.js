import lpModalOverlay from '../utils/lp-modal-overlay';

const lpModalOverlayCompleteItem = {
	elBtnFinishCourse: null,
	elBtnCompleteItem: null,
	init() {
		if ( ! lpModalOverlay.init() ) {
			return;
		}

		if ( undefined === lpGlobalSettings || 'yes' !== lpGlobalSettings.option_enable_popup_confirm_finish ) {
			return;
		}

		this.elBtnFinishCourse = document.querySelectorAll( '.lp-btn-finish-course' );
		this.elBtnCompleteItem = document.querySelector( '.lp-btn-complete-item' );

		if ( this.elBtnCompleteItem ) {
			this.elBtnCompleteItem.addEventListener( 'click', ( e ) => {
				e.preventDefault();

				const form = e.target.closest( 'form' );

				lpModalOverlay.elLPOverlay.show();
				lpModalOverlay.setTitleModal( form.dataset.title );
				lpModalOverlay.setContentModal( '<div class="pd-2em">' + form.dataset.confirm + '</div>' );
				lpModalOverlay.callBackYes = () => {
					form.submit();
				};
			} );
		}

		if ( this.elBtnFinishCourse ) {
			this.elBtnFinishCourse.forEach( ( element ) => element.addEventListener( 'click', ( e ) => {
				e.preventDefault();

				const form = e.target.closest( 'form' );

				lpModalOverlay.elLPOverlay.show();
				lpModalOverlay.setTitleModal( form.dataset.title );
				lpModalOverlay.setContentModal( '<div class="pd-2em">' + form.dataset.confirm + '</div>' );
				lpModalOverlay.callBackYes = () => {
					form.submit();
				};
			} ) );
		}
	},
};

export default lpModalOverlayCompleteItem;
