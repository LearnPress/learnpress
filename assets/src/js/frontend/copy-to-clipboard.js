function LPClick( element, iconBtn, inner ) {
	const wrappers = document.querySelectorAll( element );

	if ( ! wrappers.length ) {
		return;
	}

	wrappers.forEach( ( wrapper ) => {
		const clickBtn = wrapper.querySelector( iconBtn );
		const class_open = element.replace( '.', '' ) + '__open';
		const closeElement = wrapper.querySelector( element + '__close' );

		const isOpenElement = () => wrapper.classList.contains( class_open );

		const showElement = () => {
			if ( ! isOpenElement() ) {
				wrapper.classList.add( class_open );
			}
		};

		const hideElement = () => {
			if ( isOpenElement() ) {
				wrapper.classList.remove( class_open );
			}
		};

		const toggleElement = () => {
			if ( isOpenElement() ) {
				hideElement();
			} else {
				showElement();
			}
		};

		const onKeyDown = ( e ) => {
			if ( e.keyCode === 27 ) {
				hideElement();
			}
		};

		if ( clickBtn ) {
			clickBtn.onclick = function ( e ) {
				e.preventDefault();
				toggleElement();
			};
		}

		document.addEventListener( 'click', ( e ) => {
			if ( ! isOpenElement() ) {
				return;
			}

			const target = e.target;

			if ( target.closest( inner ) || target.closest( iconBtn ) ) {
				return;
			}

			hideElement();
		} );

		if ( closeElement ) {
			closeElement.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				hideElement();
			} );
		}

		document.addEventListener( 'keydown', onKeyDown, false );
	} );
}

export default function CopyToClipboard() {
	LPClick(
		'.social-share-toggle',
		'.share-toggle-icon',
		'.content-widget-social-share'
	);

	var copyTextareaBtn = document.querySelector( '.btn-clipboard' );
	if ( copyTextareaBtn ) {
		copyTextareaBtn.addEventListener( 'click', function ( event ) {
			var copyTextarea = document.querySelector( '.clipboard-value' );
			copyTextarea.focus();
			copyTextarea.select();
			try {
				var successful = document.execCommand( 'copy' );
				var msg = copyTextareaBtn.getAttribute( 'data-copied' );
				copyTextareaBtn.innerHTML =
					msg + '<span class="tooltip">' + msg + '</span>';
			} catch ( err ) {}
		} );
	}
}
