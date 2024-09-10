import TomSelect from 'tom-select';
import { addQueryArgs } from '@wordpress/url';

let modal;
const { __ } = wp.i18n;

const title = () => {
	modal = document.querySelector( '#lp-ai-course-title-modal' );

	addTitleBtn();
	tomSelect();
	openModal();
	closeModal();
	generate();
	togglePrompt();
	copyText();
	applyText();
};

const addTitleBtn = () => {
	document.querySelector( 'body.post-type-lp_course #titlewrap' ).insertAdjacentHTML( 'afterend', `
	<button type="button" class="button" id="lp-edit-ai-course-title">` + __( 'Edit with AI', 'learnpress' ) + `</button>` );
};

const copyText = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;

		if ( ! target.classList.contains( 'copy' ) ) {
			return;
		}

		const modal = target.closest( '#lp-ai-course-title-modal' );

		if ( ! modal ) {
			return;
		}

		const titleItem = target.closest( '.course-title-item' );

		if ( ! titleItem ) {
			return;
		}

		let text = titleItem.querySelector( 'div.ai-result' ).innerHTML;
		text = text.trim();

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

		const modal = target.closest( '#lp-ai-course-title-modal' );

		if ( ! modal ) {
			return;
		}

		const titleItem = target.closest( '.course-title-item' );

		if ( ! titleItem ) {
			return;
		}

		const titleNode = document.querySelector( '#post-body-content #title' );
		if ( ! titleNode ) {
			return;
		}

		let text = titleItem.querySelector( 'div.ai-result' ).innerHTML;
		text = text.trim();

		titleNode.value = text;
		target.innerHTML = __( 'Applied', 'learnpress' );
		target.disabled = true;
		setTimeout( () => {
			target.innerHTML = __( 'Apply', 'learnpress' );
			target.disabled = false;
		}, 1000 );
	} );
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

		if ( ! target.closest( '#lp-ai-course-title-modal' ) ) {
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

const tomSelect = () => {
	const tomSelectNodes = document.querySelectorAll( 'select.lp-tom-select' );
	for ( let i = 0; i < tomSelectNodes.length; i++ ) {
		const tomSelectNode = tomSelectNodes[ i ];
		let settings = {
			maxOptions: null,
		};
		if ( tomSelectNode.multiple ) {
			const plugins = [ 'no_backspace_delete', 'remove_button', 'clear_button', 'change_listener' ];
			settings = { ...settings, plugins };
		} else {
			const plugins = [ 'clear_button' ];
			settings = { ...settings, plugins };
		}

		new TomSelect( tomSelectNode, settings );
	}
};

const openModal = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;

		if ( target.id !== 'lp-edit-ai-course-title' ) {
			return;
		}

		modal.classList.add( 'active' );
		target.disabled = true;
	} );
};

const closeModal = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;
		if ( target.id === 'lp-edit-ai-course-title' ) {
			return;
		}

		const openModalBtn = document.querySelector( '#lp-edit-ai-course-title' );
		const modal = document.querySelector( '#lp-ai-course-title-modal' );

		const handleClose = () => {
			modal.classList.remove( 'active' );
			openModalBtn.disabled = false;
		};

		if ( target.classList.contains( 'close-btn' ) && target.closest( '#lp-ai-course-title-modal' ) ) {
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
		if ( target.getAttribute( 'id' ) !== 'lp-generate-course-title-btn' ) {
			return;
		}

		const modal = target.closest( '#lp-ai-course-title-modal' );

		if ( ! modal ) {
			return;
		}

		const togglePromptBtnNode = modal.querySelector( '.toggle-prompt' );
		const promptOutputNode = modal.querySelector( '.prompt-output' );
		const titleOutputNode = modal.querySelector( '.course-title-output' );

		const contentNode = modal.querySelector( '.content' );

		//Before generate
		( () => {
			target.disabled = true;
			togglePromptBtnNode.classList.remove( 'active', 'display' );
			togglePromptBtnNode.innerHTML = __( 'Display prompt', 'learnpress' );
			promptOutputNode.innerHTML = '';
			promptOutputNode.classList.remove( 'active' );
			titleOutputNode.innerHTML = '';
			contentNode.style.opacity = 0.6;
		} )();

		const topicNode = contentNode.querySelector( '#ai-course-title-field-topic' );
		const goalNode = contentNode.querySelector( '#ai-course-title-field-goal' );
		const audienceNode = contentNode.querySelector( '#ai-course-title-field-audience' );
		const toneNode = contentNode.querySelector( '#ai-course-title-field-tone' );
		const langNode = contentNode.querySelector( '#ai-course-title-field-language' );
		const outputsNode = contentNode.querySelector( '#ai-course-title-field-outputs' );

		const data = {
			topic: topicNode.value,
			goal: goalNode.value,
			audience: Array.from( audienceNode.selectedOptions ).map( ( option ) => option.value ),
			tone: Array.from( toneNode.selectedOptions ).map( ( option ) => option.value ),
			lang: Array.from( langNode.selectedOptions ).map( ( option ) => option.value ),
			outputs: outputsNode.value,
		};

		wp.apiFetch( {
			path: '/lp/v1/open-ai/course-title', method: 'POST', data,
		} ).then( ( res ) => {
			if ( res.data.prompt ) {
				promptOutputNode.innerHTML = res.data.prompt;
			}

			if ( res.data.content ) {
				let titleContent = '';
				[ ...res.data.content ].map( ( content ) => {
					titleContent += `
					<div class="course-title-item">
						<div class="ai-result">
							${ content }
						</div>
						<div class="action">
							<button class="copy">` + __( 'Copy', 'learnpress' ) + `</button>
							<button class="apply">` + __( 'Apply', 'learnpress' ) + `</button>
						</div>
					</div>`;
				} );
				titleOutputNode.innerHTML = titleContent;
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

export default title;
