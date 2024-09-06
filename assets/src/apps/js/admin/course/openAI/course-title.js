import TomSelect from 'tom-select';
import { addQueryArgs } from '@wordpress/url';

let courseTitleModal, aiTitleBtn;
const { __ } = wp.i18n;


const courseTitle = () => {
	aiTitleBtn = document.querySelector( '#lp-edit-ai-course-title' );
	courseTitleModal = document.querySelector( '#lp-ai-course-title-modal' );

	tomSelect();
	openCourseTitleModal();
	closeCourseTitleModal();
	generateCourseTitle();
	togglePrompt();
	copyText();
};

const copyText = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;

		if ( ! target.classList.contains( 'copy' ) ) {
			return;
		}

		const courseTitleModal = target.closest( '#lp-ai-course-title-modal' );

		if ( ! courseTitleModal ) {
			return;
		}

		const courseTitleItem = target.closest( '.course-title-item' );

		if ( ! courseTitleItem ) {
			return;
		}

		const text = courseTitleItem.querySelector( 'p' ).innerHTML;

		if ( window.isSecureContext && navigator.clipboard ) {
			navigator.clipboard.writeText( text );
		} else {
			unsecuredCopyToClipboard( text );
		}
	} );
};

const unsecuredCopyToClipboard = ( text ) => {
	const textArea = document.createElement( 'textarea' );
	textArea.value = text;
	document.body.appendChild( textArea );
	textArea.focus();
	textArea.select();
	try {
		document.execCommand( 'copy' );
	} catch ( err ) {
		console.error( 'Unable to copy to clipboard', err );
	}
	document.body.removeChild( textArea );
};

const togglePrompt = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;
		console.log( target );
		if ( ! target.classList.contains( 'toggle-prompt' ) ) {
			return;
		}

		if ( ! target.closest( '#lp-ai-course-title-modal' ) ) {
			return;
		}

		const isActive = target.classList.contains( 'active' );
		target.classList.toggle( 'active' );
		const promptOutput = courseTitleModal.querySelector( '.prompt-output' );

		if ( isActive ) {
			target.innerHTML = __( 'Display prompt', 'learnpress' );
			promptOutput.classList.remove( 'active' );
		} else {
			target.innerHTML = __( 'Hide prompt', 'learnpress' );
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

const openCourseTitleModal = () => {
	if ( ! aiTitleBtn ) {
		return;
	}

	aiTitleBtn.addEventListener( 'click', function() {
		if ( courseTitleModal ) {
			courseTitleModal.classList.add( 'active' );
		}
	} );
};

const closeCourseTitleModal = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;

		if ( ! target.classList.contains( 'close-btn' ) ) {
			return;
		}

		const courseTitleModal = target.closest( '#lp-ai-course-title-modal' );
		if ( ! courseTitleModal ) {
			return;
		}

		courseTitleModal.classList.remove( 'active' );
	} );
};

const generateCourseTitle = () => {
	document.addEventListener( 'click', function( event ) {
		const target = event.target;
		if ( target.getAttribute( 'id' ) !== 'lp-generate-course-title-btn' ) {
			return;
		}

		const courseTitleModal = target.closest( '#lp-ai-course-title-modal' );

		if ( ! courseTitleModal ) {
			return;
		}

		const promptOutputNode = courseTitleModal.querySelector( '.prompt-output' );
		const courseTitleOutputNode = courseTitleModal.querySelector( '.course-title-output' );

		const contentNode = courseTitleModal.querySelector( '.content' );

		target.disabled = true;
		contentNode.style.opacity = 0.6;

		const topicNode = contentNode.querySelector( '#ai-course-title-field-topic' );
		const goalNode = contentNode.querySelector( '#ai-course-title-field-goal' );
		const audienceNode = contentNode.querySelector( '#ai-course-title-field-audience' );
		const toneNode = contentNode.querySelector( '#ai-course-title-field-tone' );
		const langNode = contentNode.querySelector( '#ai-course-title-field-language' );
		const outputsNode = contentNode.querySelector( '#ai-course-title-field-outputs' );

		const params = {
			topic: topicNode.value,
			goal: goalNode.value,
			audience: Array.from( audienceNode.selectedOptions ).map( ( option ) => option.value ),
			tone: Array.from( toneNode.selectedOptions ).map( ( option ) => option.value ),
			lang: Array.from( langNode.selectedOptions ).map( ( option ) => option.value ),
			outputs: outputsNode.value,
		};

		wp.apiFetch( {
			path: addQueryArgs( '/lp/v1/open-ai/get-course-title', params ), method: 'GET',
		} ).then( ( res ) => {
			if ( res.data.prompt ) {
				promptOutputNode.innerHTML = res.data.prompt;
			}

			if ( res.data.content ) {
				let courseTitleContent = '';
				[ ...res.data.content ].map( ( content ) => {
					courseTitleContent += `
					<div class="course-title-item">
						<p>
							${ content }
						</p>
						<div class="action">
							<button class="copy">` + __( 'Copy', 'learnpress' ) + `</button>
							<button class="apply">` + __( 'Apply', 'learnpress' ) + `</button>
						</div>
					</div>`;
				} );
				courseTitleOutputNode.innerHTML = courseTitleContent;
			}
		} ).catch( ( err ) => {
			console.log( err );
		} ).finally( () => {
			target.disabled = false;
			contentNode.style.opacity = 1;
		} );
	} );
};

export default courseTitle;
