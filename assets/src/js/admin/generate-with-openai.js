/**
 * Generate data with OpenAI
 */

import * as lpUtils from './../utils.js';
import SweetAlert from 'sweetalert2';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';

let lp_structure_course;
let popupSweetAlert = null;

export class GenerateWithOpenai {
	constructor() {
		this.init();
	}

	static selectors = {
		elBtnGenerateWithAi: '.lp-btn-generate-with-ai',
		elGenerateDataAiWrap: '.lp-generate-data-ai-wrap',
	};

	init() {
		lpUtils.lpOnElementReady( '#titlewrap', ( el ) => {
			el.insertAdjacentHTML(
				'afterend',
				`<button type="button"
					class="lp-btn-generate-with-ai button-secondary"
					data-template="#lp-tmpl-edit-title-ai">
					Generate Title with AI
				</button>`
			);
		} );
		lpUtils.lpOnElementReady( '#wp-content-media-buttons', ( el ) => {
			el.insertAdjacentHTML(
				'beforeend',
				`<button type="button"
					class="lp-btn-generate-with-ai button-secondary"
					data-template="#lp-tmpl-edit-description-ai">
					Generate Description with AI
				</button>`
			);
		} );
		lpUtils.lpOnElementReady( '#postimagediv', ( el ) => {
			const elInside = el.querySelector( '.postbox-header' );
			elInside.insertAdjacentHTML(
				'afterend',
				`<button type="button"
					style="margin: 12px 12px 0 12px;"
					class="lp-btn-generate-with-ai button-secondary"
					data-template="#lp-tmpl-edit-image-ai">
					Generate Image with AI
				</button>`
			);
		} );

		this.events();
	}

	events() {
		lpUtils.eventHandlers( 'click', [
			{
				selector: GenerateWithOpenai.selectors.elBtnGenerateWithAi,
				class: this,
				callBack: this.showPopup.name,
			},
			{
				selector: '.lp-btn-step',
				class: this,
				callBack: this.showStep.name,
			},
			{
				selector: '.lp-btn-generate-prompt',
				class: this,
				callBack: this.generatePrompt.name,
			},
			{
				selector: '.lp-btn-call-open-ai',
				class: this,
				callBack: this.generateData.name,
			},
			{
				selector: '.lp-btn-copy',
				class: this,
				callBack: this.copyGeneratedData.name,
			},
			{
				selector: '.lp-btn-apply',
				class: this,
				callBack: this.applyGeneratedData.name,
			},
			{
				selector: '.lp-btn-ai-apply-image',
				class: this,
				callBack: this.applyImageData.name,
			},
		] );
	}

	showPopup( args ) {
		const { e, target } = args;
		const templateId = target.dataset.template || '';

		const modalTemplate = document.querySelector( templateId );

		if ( ! modalTemplate ) {
			console.error( `Template ${ templateId } not found!` );
			return;
		}

		SweetAlert.fire( {
			html: modalTemplate.innerHTML,
			width: '60%',
			showCloseButton: true,
			showConfirmButton: false,
			didOpen: () => {
				popupSweetAlert = SweetAlert.getPopup();
				// Click to show tomSelect style
				popupSweetAlert.click();

				// Set post title and post content to hidden fields of form to AI prompt reference
				const post_title = document.querySelector(
					'input[name=post_title]'
				).value;
				const post_content = window.tinymce
					.get( 'content' )
					.getContent( { format: 'text' } );

				const form = popupSweetAlert.querySelector( 'form' );
				const elPostTitle = form.querySelector( '[name=post-title]' );
				if ( elPostTitle ) {
					elPostTitle.value = post_title;
				}

				const elPostContent = form.querySelector(
					'[name=post-content]'
				);
				if ( elPostContent ) {
					elPostContent.value = post_content;
				}
			},
		} ).then( ( result ) => {
			if ( result.isDismissed ) {
			}
		} );
	}

	showStep( args ) {
		const { e, target } = args;
		e.preventDefault();

		const elBtnActions = target.closest( '.button-actions' );
		const elCreateCourseAIWrap = elBtnActions.closest(
			GenerateWithOpenai.selectors.elGenerateDataAiWrap
		);
		let step = parseInt( elBtnActions.dataset.step );

		const stepAction = target.dataset.action;
		if ( stepAction === 'next' ) {
			step++;
		} else if ( stepAction === 'prev' ) {
			step--;
		}

		elBtnActions.dataset.step = step;
		const elForm = target.closest( 'form' );
		const elContentStep = elForm.querySelector(
			`.step-content[data-step="${ step }"]`
		);
		const elItemStep = elCreateCourseAIWrap.querySelector(
			`.step-item[data-step="${ step }"]`
		);
		elForm
			.querySelectorAll( '.step-content' )
			.forEach( ( el ) => el.classList.remove( 'active' ) );
		elContentStep.classList.add( 'active' );
		elCreateCourseAIWrap
			.querySelectorAll( '.step-item' )
			.forEach( ( el ) => el.classList.remove( 'active' ) );
		elItemStep.classList.add( 'active' );

		// Get all buttons step to show/hide
		const form = target.closest( 'form' );
		const elBtnSteps = form.querySelectorAll( 'button[data-step-show]' );
		elBtnSteps.forEach( ( el ) => {
			const stepsShow = el.dataset.stepShow
				.split( ',' )
				.map( ( s ) => parseInt( s.trim() ) );
			if ( stepsShow.includes( step ) ) {
				lpUtils.lpShowHideEl( el, 1 );
			} else {
				lpUtils.lpShowHideEl( el, 0 );
			}
		} );
	}

	/**
	 * Create prompt from data config
	 * @param args
	 */
	generatePrompt( args ) {
		const { e, target } = args;
		e.preventDefault();
		lpUtils.lpSetLoadingEl( target, true );

		// Get dataSend
		const form = target.closest( 'form' );
		let dataSend = JSON.parse( target.dataset.send );
		dataSend = lpUtils.mergeDataWithDatForm( form, dataSend );

		// Ajax to generate prompt
		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				lpToastify.show( message, status );

				if ( status === 'success' ) {
					const elPromptTextarea = form.querySelector(
						'textarea[name=lp-openai-prompt-generated-field]'
					);
					elPromptTextarea.value = data;

					const elBtnNext = form.querySelector(
						'.lp-btn-step[data-action=next]'
					);
					elBtnNext.click();
				}
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( target, false );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/**
	 * Submit prompt to OpenAI to generate course data
	 * @param args
	 */
	generateData( args ) {
		const { e, target } = args;
		e.preventDefault();
		lpUtils.lpSetLoadingEl( target, true );

		// Get dataSend
		const form = target.closest( 'form' );
		let dataSend = JSON.parse( target.dataset.send );
		dataSend = lpUtils.mergeDataWithDatForm( form, dataSend );

		const btnPrev = form.querySelector( '.lp-btn-step[data-action=prev]' );
		lpUtils.lpShowHideEl( btnPrev, 0 );

		setTimeout( () => {
			lpToastify.show(
				'Generating course data. This may take a few moments...',
				'info'
			);
		}, 1000 );

		// Ajax to generate prompt
		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				lpToastify.show( message, status );

				if ( status === 'success' ) {
					// Save structure data
					lp_structure_course = data.lp_structure_course;

					// Set preview HTML
					const elResults = form.querySelector(
						'.lp-ai-generated-results'
					);
					elResults.innerHTML = data.lp_html_preview;

					const elBtnNext = form.querySelector(
						'.lp-btn-step[data-action=next]'
					);
					elBtnNext.click();
				}
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( target, false );
				lpUtils.lpShowHideEl( btnPrev, 1 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	applyGeneratedData( args ) {
		const { e, target } = args;
		e.preventDefault();

		const dataApply = target.dataset.apply;
		const dataTarget = target.dataset.target;

		if ( dataTarget ) {
			if ( dataTarget === 'set-wp-editor-content' ) {
				this.setWPEditorContent( dataApply );
			} else {
				const elTarget = document.querySelector( dataTarget );
				if ( elTarget ) {
					elTarget.value = dataApply;
				}
			}
		}

		if ( popupSweetAlert ) {
			SweetAlert.close();
		}
	}

	copyGeneratedData( args ) {
		const { e, target } = args;
		e.preventDefault();

		const dataCopy = target.dataset.copy;

		if ( navigator.clipboard ) {
			navigator.clipboard
				.writeText( dataCopy )
				.then( () => {
					lpToastify.show( 'Copied to clipboard!', 'success' );
				} )
				.catch( ( err ) => {
					lpToastify.show( 'Failed to copy text: ' + err, 'error' );
				} );
		} else {
			// Fallback when clipboard API is unavailable
			const textarea = document.createElement( 'textarea' );
			textarea.value = dataCopy;
			textarea.style.position = 'fixed';
			textarea.style.left = '-9999px';
			document.body.appendChild( textarea );
			textarea.select();
			try {
				const successful = document.execCommand( 'copy' );
				lpToastify.show(
					successful ? 'Copied to clipboard!' : 'Failed to copy text',
					successful ? 'success' : 'error'
				);
			} catch ( err ) {
				lpToastify.show( 'Failed to copy text: ' + err, 'error' );
			}
			document.body.removeChild( textarea );
		}

		/*navigator.clipboard.writeText( dataCopy ).then( () => {
			lpToastify.show( 'Copied to clipboard!', 'success' );
		} ).catch( ( err ) => {
			lpToastify.show( 'Failed to copy text: ' + err, 'error' );
		} );*/
	}

	setWPEditorContent( htmlContent ) {
		const editor = window.tinymce.get( 'content' );
		editor.setContent( htmlContent );
	}

	applyImageData( args ) {
		const { e, target } = args;
		let dataSend = JSON.parse( target.dataset.send );
		dataSend = lpUtils.mergeDataWithDatForm(
			target.closest( 'form' ),
			dataSend
		);
		//e.preventDefault();
		lpUtils.lpSetLoadingEl( target, true );

		// Ajax to generate prompt
		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				lpToastify.show( message, status );

				if ( status === 'success' ) {
					// Set image
					const elImagePreview = document.querySelector(
						'#postimagediv .inside'
					);
					elImagePreview.outerHTML = data.html_image;

					if ( popupSweetAlert ) {
						SweetAlert.close();
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( target, false );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
}
