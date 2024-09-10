let modal;
const { __ } = wp.i18n;

const editFeatureImage = () => {
	modal = document.querySelector( '#lp-ai-course-edit-fi-modal' );

	logo();
	createImageBtn();
	openModal();
	closeModal();
	generate();
	togglePrompt();
	copyText();
	applyText();
};

const logo = () => {
	if ( ! modal ) {
		return;
	}
	const uploadBtn = modal.querySelector( '#ai-course-edit-fi-field-logo' );

	if ( ! uploadBtn ) {
		return;
	}

	const input = modal.querySelector( '#ai-course-fi-field-logo-input' );
	const preview = modal.querySelector( '#ai-course-fi-field-logo-preview' );

	uploadBtn.addEventListener( 'click', function() {
		input.click();
	} );

	input.addEventListener( 'change', function( event ) {
		const file = this.files[ 0 ];
		const errorMessage = document.querySelector( '#ai-course-fi-field-logo-error' );
		errorMessage.innerHTML = '';

		if ( file ) {
			if ( file.type !== 'image/png' ) {
				errorMessage.innerHTML = __( 'Error: File must be a PNG image.', 'learnpress' );
				return;
			}

			if ( file.size > 4 * 1024 * 1024 ) {
				errorMessage.innerHTML = __( 'Error: File must be less than 4MB.', 'learnpress' );
				return;
			}

			const reader = new FileReader();
			reader.onload = function( e ) {
				const img = new Image();
				img.src = e?.target?.result;

				img.onload = function() {
					if ( img.width !== img.height ) {
						errorMessage.innerHTML = __( 'Error: Image must be square.', 'learnpress' );
						return;
					}

					preview.innerHTML = `
                    <img src="${ e.target.result }" alt="` + __( 'Image preview', 'learnpress' ) + `">
                `;
				};
			};

			reader.readAsDataURL( file );
		}
	} );

	document.getElementById( 'ai-course-remove-fi-field-logo' ).addEventListener( 'click', function() {
		preview.innerHTML = '';
		document.getElementById( 'ai-course-fi-field-logo-input' ).value = '';
	} );
};

const createImageBtn = () => {
	const imageDiv = document.querySelector( 'body.post-type-lp_course #postimagediv' );
	if ( ! imageDiv ) {
		return;
	}

	imageDiv.insertAdjacentHTML( 'beforeend', `
	<div class="inside">
	<button type="button" class="button" id="lp-edit-ai-course-create-fi">` + __( 'Create new AI image', 'learnpress' ) + `</button>
	<button type="button" class="button" id="lp-edit-ai-course-edit-fi">` + __( 'Edit AI image', 'learnpress' ) + `</button>
	</div>` );
};

const copyText = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;

		if ( ! target.classList.contains( 'copy' ) ) {
			return;
		}

		const modal = target.closest( '#lp-ai-course-edit-fi-modal' );

		if ( ! modal ) {
			return;
		}

		const courseFeatureImageItem = target.closest( '.course-edit-fi-item' );

		if ( ! courseFeatureImageItem ) {
			return;
		}

		let text = courseFeatureImageItem.querySelector( 'div.ai-result' ).innerHTML;
		text = text.trim();
		text = convertParagraphsToNewlines( text );
		if ( window.isSecureContext && navigator.clipboard ) {
			target.disabled = true;
			navigator.clipboard.writeText( text )
				.then( () => {
					target.innerHTML = __( 'Copied', 'learnpress' );
					setTimeout( () => {
						target.innerHTML = __( 'Copy', 'learnpress' );
						target.disabled = false;
					}, 1000 );
				} )
				.catch( ( err ) => {
					console.error( __( 'Failed to copy text: ', 'learnpress' ), err );
				} );
		} else {
			unsecuredCopyToClipboard( target, text );
		}
	} );
};

const applyText = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;

		if ( ! target.classList.contains( 'apply' ) ) {
			return;
		}

		const modal = target.closest( '#lp-ai-course-edit-fi-modal' );

		if ( ! modal ) {
			return;
		}

		const courseFeatureImageItem = target.closest( '.course-edit-fi-item' );

		if ( ! courseFeatureImageItem ) {
			return;
		}

		const editor = tinyMCE.get( 'content' );

		if ( ! editor ) {
			return;
		}

		let text = courseFeatureImageItem.querySelector( 'div.ai-result' ).innerHTML;
		text = text.trim();

		editor.setContent( convertNewlinesToParagraphs( text ) );
		target.innerHTML = __( 'Applied', 'learnpress' );
		target.disabled = true;
		setTimeout( () => {
			target.innerHTML = __( 'Apply', 'learnpress' );
			target.disabled = false;
		}, 1000 );
	} );
};

const convertNewlinesToParagraphs = ( text ) => {
	let result = text
		.replace( /\n+/g, '</p><p>' )
		.trim();

	if ( result ) {
		result = '<p>' + result + '</p>';
	}

	return result;
};

const convertParagraphsToNewlines = ( htmlString ) => {
	return htmlString
		.replace( /<\/p>/g, '\n\n' )
		.replace( /<p>/g, '' )
		.trim();
};

const unsecuredCopyToClipboard = ( target, text ) => {
	const textArea = document.createElement( 'textarea' );
	textArea.value = text;
	document.body.appendChild( textArea );
	textArea.focus();
	textArea.select();
	try {
		document.execCommand( 'copy' );
		target.innerHTML = __( 'Copied', 'learnpress' );
		target.disabled = true;
		setTimeout( () => {
			target.innerHTML = __( 'Copy', 'learnpress' );
			target.disabled = false;
		}, 1000 );
	} catch ( err ) {
		console.error( 'Unable to copy to clipboard', err );
	}
	document.body.removeChild( textArea );
};

const togglePrompt = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;
		if ( ! target.classList.contains( 'toggle-prompt' ) ) {
			return;
		}

		if ( ! target.closest( '#lp-ai-course-edit-fi-modal' ) ) {
			return;
		}

		const isActive = target.classList.contains( 'active' );
		target.classList.toggle( 'active' );
		const promptOutput = modal.querySelector( '.prompt-output' );

		if ( isActive ) {
			target.innerHTML = __( 'Display prompt', 'learnpress' );
			target.classList.remove( 'active' );
			promptOutput.classList.remove( 'active' );
		} else {
			target.innerHTML = __( 'Hide prompt', 'learnpress' );
			target.classList.add( 'active' );
			promptOutput.classList.add( 'active' );
		}
	} );
};

const openModal = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;

		if ( target.id !== 'lp-edit-ai-course-edit-fi' ) {
			return;
		}

		modal.classList.add( 'active' );
		target.disabled = false;
	} );
};

const closeModal = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;
		if ( target.id === 'lp-edit-ai-course-edit-fi' ) {
			return;
		}

		const openModalBtn = document.querySelector( '#lp-edit-ai-course-edit-fi' );
		const modal = document.querySelector( '#lp-ai-course-edit-fi-modal' );

		const handleClose = () => {
			modal.classList.remove( 'active' );
			openModalBtn.disabled = false;
		};

		if ( target.classList.contains( 'close-btn' ) && target.closest( '#lp-ai-course-edit-fi-modal' ) ) {
			handleClose();
		}

		if ( ! target.classList.contains( 'modal-content' ) && ! target.closest( '.modal-content' ) ) {
			handleClose();
		}
	} );
};

const generate = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;
		if ( target.getAttribute( 'id' ) !== 'lp-generate-course-edit-fi-btn' ) {
			return;
		}

		const modal = target.closest( '#lp-ai-course-edit-fi-modal' );

		if ( ! modal ) {
			return;
		}

		const togglePromptBtnNode = modal.querySelector( '.toggle-prompt' );
		const promptOutputNode = modal.querySelector( '.prompt-output' );
		const courseFeatureImageOutputNode = modal.querySelector( '.course-edit-fi-output' );

		const contentNode = modal.querySelector( '.content' );

		//Before generate
		( () => {
			target.disabled = true;
			togglePromptBtnNode.classList.remove( 'active', 'display' );
			togglePromptBtnNode.innerHTML = __( 'Display prompt', 'learnpress' );
			promptOutputNode.innerHTML = '';
			promptOutputNode.classList.remove( 'active' );
			courseFeatureImageOutputNode.innerHTML = '';
			contentNode.style.opacity = 0.6;
		} )();

		const styleNode = contentNode.querySelector( '#ai-course-edit-fi-field-style' );
		const iconNode = contentNode.querySelector( '#ai-course-edit-fi-field-icon' );
		const logoNode = contentNode.querySelector( '#ai-course-fi-field-logo-preview img' );
		const qualityNode = contentNode.querySelector( '#ai-course-edit-fi-field-quality' );
		const sizeNode = contentNode.querySelector( '#ai-course-edit-fi-field-size' );
		const outputsNode = contentNode.querySelector( '#ai-course-edit-fi-field-outputs' );

		const data = {
			style: Array.from( styleNode.selectedOptions ).map( ( option ) => option.value ),
			icon: iconNode.value,
			logo: logoNode.src ? base64ToBlobUrl( logoNode.src ) : '',
			quality: qualityNode.value,
			size: sizeNode.value,
			outputs: outputsNode.value,
		};


		wp.apiFetch( {
			path: '/lp/v1/open-ai/edit-feature-image', method: 'POST', data,
		} ).then( ( res ) => {
			if ( res.data.prompt ) {
				promptOutputNode.innerHTML = res.data.prompt;
			}

			if ( res.data.content ) {
				let courseFeatureImage = '';
				[ ...res.data.content ].map( ( content ) => {
					content = convertNewlinesToParagraphs( content );
					courseFeatureImage += `
					<div class="course-edit-fi-item">
						<div class="ai-result">
							${ content }
						</div>
						<div class="action">
							<button class="copy">` + __( 'Copy', 'learnpress' ) + `</button>
							<button class="apply">` + __( 'Apply', 'learnpress' ) + `</button>
						</div>
					</div>`;
				} );
				courseFeatureImageOutputNode.innerHTML = courseFeatureImage;
			}
		} ).catch( ( err ) => {
			console.log( err );
		} ).finally( () => {
			//After generate
			( () => {
				target.disabled = false;
				togglePromptBtnNode.classList.add( 'display' );
				contentNode.style.opacity = 1;
			} )();
		} );
	} );
};

const base64ToBlobUrl = ( base64Data ) => {
	const [ metadata, base64 ] = base64Data.split( ',' );


	const mimeType = metadata.match( /:(.*?);/ )[ 1 ];

	const byteCharacters = atob( base64 );
	const byteArrays = [];

	for ( let offset = 0; offset < byteCharacters.length; offset += 1024 ) {
		const slice = byteCharacters.slice( offset, offset + 1024 );
		const byteNumbers = new Array( slice.length );
		for ( let i = 0; i < slice.length; i++ ) {
			byteNumbers[ i ] = slice.charCodeAt( i );
		}
		byteArrays.push( new Uint8Array( byteNumbers ) );
	}

	const blob = new Blob( byteArrays, { type: mimeType } );

	return URL.createObjectURL( blob );
};

export default editFeatureImage;
