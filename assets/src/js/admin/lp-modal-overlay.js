const $ = jQuery;

const elLPOverlay = $( '.lp-overlay' );

const lpModalOverlay = {
	elMainContent: null,
	elTitle: null,
	elBtnYes: null,
	elBtnNo: null,
	elFooter: null,
	elCalledModal: null,
	callBackYes: null,
	init() {
		const lpModalOverlay = this;
		this.elMainContent = elLPOverlay.find( '.main-content' );
		this.elTitle = elLPOverlay.find( '.modal-title' );
		this.elBtnYes = elLPOverlay.find( '.btn-yes' );
		this.elBtnNo = elLPOverlay.find( '.btn-no' );
		this.elFooter = elLPOverlay.find( '.lp-modal-footer' );

		$( document ).on( 'click', '.close, .btn-no', function() {
			elLPOverlay.hide();
		} );

		$( document ).on( 'click', '.btn-yes', function() {
			lpModalOverlay.callBackYes();
		} );
	},
	setElCalledModal( elCalledModal ) {
		this.elCalledModal = elCalledModal;
	},
	setContentModal( content, event ) {
		this.elMainContent.html( content );
		if ( 'function' === typeof event ) {
			event();
		}
	},
	setTitleModal( content ) {
		this.elTitle.html( content );
	},
};

export {
	lpModalOverlay,
	elLPOverlay,
};
