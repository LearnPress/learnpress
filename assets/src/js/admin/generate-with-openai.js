/**
 * Generate data with OpenAI
 */

import * as lpUtils from './../utils.js';
import SweetAlert from 'sweetalert2';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';

let lp_structure_course;
let popupSweetAlert = null;
let lp_is_generating_course_data = false;
const lp_course_ai_setting = JSON.parse( localStorage.getItem( 'lp_course_ai_setting' ) ) || {};
let isLayoutGutenberg = false;

let selectGutenberg;
let dispatchGutenberg;
let editorGutenberg;

export class GenerateWithOpenai {
	constructor() {
		this.init();
	}

	static selectors = {
		elBtnGenerateWithAi: '.lp-btn-generate-with-ai',
		elGenerateDataAiWrap: '.lp-generate-data-ai-wrap',
	};

	init() {
		if ( ! lpData?.enable_open_ai ) {
			return;
		}

		lpUtils.lpOnElementReady( '#titlewrap', ( el ) => {
			el.insertAdjacentHTML(
				'afterend',
				`<button type="button"
					class="lp-btn-generate-with-ai lp-btn-ai-style"
					data-template="#lp-tmpl-edit-title-ai">
					<i class="lp-ico-ai"></i><span>${ lpData.i18n.generate_with_ai }</span>
				</button>`
			);
		} );
		lpUtils.lpOnElementReady( '#wp-content-media-buttons', ( el ) => {
			el.insertAdjacentHTML(
				'beforeend',
				`<button type="button"
					class="lp-btn-generate-with-ai lp-btn-ai-style"
					data-template="#lp-tmpl-edit-description-ai">
					<i class="lp-ico-ai"></i><span>${ lpData.i18n.generate_with_ai }</span>
				</button>`
			);
		} );
		lpUtils.lpOnElementReady( '#postimagediv', ( el ) => {
			const elInside = el.querySelector( '.postbox-header' );
			elInside.insertAdjacentHTML(
				'afterend',
				`<button type="button"
					style="margin: 12px 12px 0 12px;"
					class="lp-btn-generate-with-ai lp-btn-ai-style"
					data-template="#lp-tmpl-edit-image-ai">
					<i class="lp-ico-ai"></i><span>${ lpData.i18n.generate_with_ai }</span>
				</button>`
			);
		} );

		// Check is layout Gutenberg
		if ( wp.data && wp.data.select( 'core/editor' ) ) {
			isLayoutGutenberg = true;
			selectGutenberg = wp.data.select;
			dispatchGutenberg = wp.data.dispatch;
			editorGutenberg = selectGutenberg( 'core/editor' );

			// For layout Gutenberg - button for title
			lpUtils.lpOnElementReady( '.editor-document-bar', ( el ) => {
				el.insertAdjacentHTML(
					'afterend',
					`<button type="button"
					style="margin-left: 5px"
					class="lp-btn-generate-with-ai"
					data-template="#lp-tmpl-edit-title-ai">
					<i class="lp-ico-ai"></i><span>${ lpData.i18n.generate_with_ai }</span>
				</button>`
				);
			} );

			// For layout Gutenberg - button for description
			lpUtils.lpOnElementReady( '.editor-post-featured-image', ( el ) => {
				el.insertAdjacentHTML(
					'beforebegin',
					`<button type="button"
					style="padding: 5px 10px; justify-content: center;"
					class="lp-btn-generate-with-ai"
					data-template="#lp-tmpl-edit-description-ai">
					<i class="lp-ico-ai"></i><span>Generate description with AI</span>
				</button>`
				);
			} );

			// For layout Gutenberg - button for image
			lpUtils.lpOnElementReady( '.editor-post-featured-image', ( el ) => {
				el.insertAdjacentHTML(
					'afterend',
					`<button type="button"
					style="padding: 5px 10px; justify-content: center;"
					class="lp-btn-generate-with-ai"
					data-template="#lp-tmpl-edit-image-ai">
					<i class="lp-ico-ai"></i><span>${ lpData.i18n.generate_with_ai }</span>
				</button>`
				);
			} );
		}

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
			{
				selector: '.lp-btn-close-ai-popup',
				//class: this,
				callBack: ( args ) => {
					const { e, target } = args;

					const message = lpData.i18n.confirm_close_ai;

					if ( ! lp_is_generating_course_data ) {
						SweetAlert.close();
					} else if ( confirm( message ) ) {
						SweetAlert.close();
					}

					// Testing custom confirm box
					/*if ( confirm( message ) ) {
						SweetAlert.close();
					}*/
				},
			},
		] );
	}

	showPopup( args ) {
		const { e, target } = args;
		const templateId = target.dataset.template || target.closest( '.lp-btn-generate-with-ai' ).dataset.template ||
			target.closest( '.lp-generate-data-ai-wrap' ).dataset.template || '';

		const modalTemplate = document.querySelector( templateId );

		if ( ! modalTemplate ) {
			console.error( `Template ${ templateId } not found!` );
			return;
		}

		SweetAlert.fire( {
			html: modalTemplate.innerHTML,
			width: '60%',
			showCloseButton: false,
			showConfirmButton: false,
			allowOutsideClick: false,
			allowEscapeKey: false,
			didOpen: () => {
				popupSweetAlert = SweetAlert.getPopup();
				// Click to show tomSelect style
				popupSweetAlert.click();

				// Set post title and post content to hidden fields of form to AI prompt reference
				const elPostTitleInput = document.querySelector( 'input[name=post_title]' );
				let post_title = '';
				if ( elPostTitleInput ) {
					post_title = elPostTitleInput.value;
				} else if ( isLayoutGutenberg ) {
					const elGutenbergTitle = document.querySelector( '.editor-post-card-panel__title-name' );
					if ( elGutenbergTitle ) {
						post_title = elGutenbergTitle.textContent;
					}
				}

				let post_content = '';
				if ( ! isLayoutGutenberg ) {
					if ( ! window.tinymce || ! window.tinymce.get( 'content' ) ) {
						post_content = document
							.querySelector( '#content' )
							.value;
					} else {
						post_content = window.tinymce
							.get( 'content' )
							.getContent( { format: 'text' } );
					}
				} else {
					const content = editorGutenberg.getEditedPostContent();
					post_content = content
						.replace( /(<([^>]+)>)/gi, '' ); // Remove HTML tags
				}

				const form = popupSweetAlert.querySelector( 'form' );
				const elPostTitle = form.querySelector( '[name=post-title]' );
				if ( elPostTitle ) {
					elPostTitle.value = post_title;

					if ( ! post_title ) {
						const elGroup = elPostTitle.closest( '.form-group' );
						const elReferWarning = elGroup.querySelector( '.lp-ai-warning-refer' );
						if ( elReferWarning ) {
							lpUtils.lpShowHideEl( elReferWarning, 1 );
						}
					}
				}

				const elPostContent = form.querySelector(
					'[name=post-content]'
				);
				if ( elPostContent ) {
					elPostContent.value = post_content;

					if ( post_content.length < 2 ) {
						const elGroup = elPostContent.closest( '.form-group' );
						const elReferWarning = elGroup.querySelector( '.lp-ai-warning-refer' );
						if ( elReferWarning ) {
							lpUtils.lpShowHideEl( elReferWarning, 1 );
						}
					}
				}

				const targetAudience = popupSweetAlert.querySelector( 'select[name="target_audience"]' );
				if ( targetAudience ) {
					if ( lp_course_ai_setting?.target_audience ) {
						targetAudience.tomselect.setValue( lp_course_ai_setting.target_audience );
					}

					targetAudience.addEventListener( 'change', ( event ) => {
						lp_course_ai_setting.target_audience = targetAudience.tomselect.getValue();
						localStorage.setItem( 'lp_course_ai_setting', JSON.stringify( lp_course_ai_setting ) );
					} );
				}

				const tone = popupSweetAlert.querySelector( 'select[name="tone"]' );
				if ( tone ) {
					if ( lp_course_ai_setting?.tone ) {
						tone.tomselect.setValue( lp_course_ai_setting.tone );
					}

					tone.addEventListener( 'change', ( event ) => {
						lp_course_ai_setting.tone = tone.tomselect.getValue();
						localStorage.setItem( 'lp_course_ai_setting', JSON.stringify( lp_course_ai_setting ) );
					} );
				}

				const language = popupSweetAlert.querySelector( 'select[name="language"]' );
				if ( language ) {
					if ( lp_course_ai_setting?.language ) {
						language.tomselect.setValue( lp_course_ai_setting.language );
					}

					language.addEventListener( 'change', ( event ) => {
						const value = language.tomselect.getValue();
						lp_course_ai_setting.language = value ? [ value ] : [];
						localStorage.setItem( 'lp_course_ai_setting', JSON.stringify( lp_course_ai_setting ) );
					} );
				}
			},
		} ).then( ( result ) => {
			if ( result.isDismissed ) {
				if ( lp_is_generating_course_data ) {
					lp_is_generating_course_data = false;
				}
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

		// Ajax to generate prompt
		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;
				if ( lp_is_generating_course_data ) {
					lpToastify.show( message, status );
				}

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
				lp_is_generating_course_data = false;
			},
		};

		lp_is_generating_course_data = true;
		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	applyGeneratedData( args ) {
		const { e, target } = args;
		e.preventDefault();

		const dataApply = target.dataset.apply;
		const dataTarget = target.dataset.target;

		if ( dataTarget ) {
			if ( ! isLayoutGutenberg ) {
				if ( dataTarget === 'set-wp-editor-content' ) {
					this.setWPEditorContent( dataApply );
				} else if ( dataTarget === 'set-wp-title' ) {
					const elTitleInput = document.querySelector( 'input[name=post_title]' );
					if ( elTitleInput ) {
						elTitleInput.value = dataApply;
					}
				}
			} else if ( dataTarget === 'set-wp-editor-content' ) {
				dispatchGutenberg( 'core/editor' ).editPost( { content: dataApply } );
			} else if ( dataTarget === 'set-wp-title' ) {
				dispatchGutenberg( 'core/editor' ).editPost( { title: dataApply } );
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
					if ( ! isLayoutGutenberg ) {
						// Set image
						const elImagePreview = document.querySelector(
							'#postimagediv .inside'
						);
						elImagePreview.outerHTML = data.html_image;
					} else {
						dispatchGutenberg( 'core/editor' ).editPost( { featured_media: data.attachment_id } );
					}

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
