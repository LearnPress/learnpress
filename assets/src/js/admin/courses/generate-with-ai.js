/**
 * Create course with AI
 */

import * as lpUtils from 'lpAssetsJsPath/utils.js';
import SweetAlert from 'sweetalert2';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
let lp_structure_course;
let lp_is_generating_course_data = false;
const lp_course_ai_setting = JSON.parse( localStorage.getItem( 'lp_course_ai_setting' ) ) || {};

export class CreateCourseViaAI {
	constructor() {
		this.init();
	}

	static selectors = {
		elGenerateDataAiWrap: '.lp-generate-data-ai-wrap',
	};

	init() {
		if ( ! lpData?.enable_open_ai ) {
			lpUtils.lpOnElementReady( '.page-title-action', ( el ) => {
				el.insertAdjacentHTML(
					'afterend',
					`<button type="button" class="lp-btn-warning-enable-ai lp-btn-ai-style">
					<i class="lp-ico-ai"></i>
					<span>${ lpData.i18n.generate_with_ai }</span>
				</button>`
				);
			} );
		} else {
			lpUtils.lpOnElementReady( '.page-title-action', ( el ) => {
				el.insertAdjacentHTML(
					'afterend',
					`<button type="button" class="lp-btn-generate-course-with-ai lp-btn-ai-style">
					<i class="lp-ico-ai"></i>
					<span>${ lpData.i18n.generate_with_ai }</span>
				</button>`
				);
			} );
		}

		this.events();
	}

	events() {
		if ( CreateCourseViaAI._loadedEvents ) {
			return;
		}
		CreateCourseViaAI._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: '.lp-btn-warning-enable-ai',
				class: this,
				callBack: this.showPopupEnableAI.name,
			},
			{
				selector: '.lp-btn-generate-course-with-ai',
				class: this,
				callBack: this.showPopupCreateFullCourse.name,
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
				callBack: this.generateDataCourse.name,
			},
			{
				selector: '.lp-btn-create-course',
				class: this,
				callBack: this.createCourse.name,
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

	// Show popup warning enable AI before using.
	showPopupEnableAI() {
		const modalTemplate = document.querySelector( '#lp-tmpl-must-enable-ai' );

		if ( ! modalTemplate ) {
			console.error( 'Enable OpenAI Modal Template not found!' );
			return;
		}

		SweetAlert.fire( {
			html: modalTemplate.innerHTML,
			width: '420px',
			showCloseButton: false,
			showConfirmButton: false,
		} );
	}

	showPopupCreateFullCourse() {
		const modalTemplate = document.querySelector(
			'#lp-tmpl-create-course-ai'
		);

		if ( ! modalTemplate ) {
			console.error( 'AI Create Full Course Modal Template not found!' );
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
				const popup = SweetAlert.getPopup();
				popup.click();

				const targetAudience = popup.querySelector( 'select[name="target_audience"]' );
				if ( targetAudience && lp_course_ai_setting?.target_audience ) {
					targetAudience.tomselect.setValue( lp_course_ai_setting.target_audience );
				}
				const tone = popup.querySelector( 'select[name="tone"]' );
				if ( tone && lp_course_ai_setting?.tone ) {
					tone.tomselect.setValue( lp_course_ai_setting.tone );
				}
				const language = popup.querySelector( 'select[name="language"]' );
				if ( language && lp_course_ai_setting?.language ) {
					language.tomselect.setValue( lp_course_ai_setting.language );
				}

				targetAudience.addEventListener( 'change', ( event ) => {
					lp_course_ai_setting.target_audience = targetAudience.tomselect.getValue();
					localStorage.setItem( 'lp_course_ai_setting', JSON.stringify( lp_course_ai_setting ) );
				} );

				tone.addEventListener( 'change', ( event ) => {
					lp_course_ai_setting.tone = tone.tomselect.getValue();
					localStorage.setItem( 'lp_course_ai_setting', JSON.stringify( lp_course_ai_setting ) );
				} );

				language.addEventListener( 'change', ( event ) => {
					const value = language.tomselect.getValue();
					lp_course_ai_setting.language = value ? [ value ] : [];
					localStorage.setItem( 'lp_course_ai_setting', JSON.stringify( lp_course_ai_setting ) );
				} );
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
		const elCreateCourseAIWrap = elBtnActions.closest( CreateCourseViaAI.selectors.elGenerateDataAiWrap );
		let step = parseInt( elBtnActions.dataset.step );

		const stepAction = target.dataset.action;
		if ( stepAction === 'next' ) {
			step++;
		} else if ( stepAction === 'prev' ) {
			step--;
		}

		elBtnActions.dataset.step = step;
		const elForm = target.closest( 'form' );
		const elContentStep = elForm.querySelector( `.step-content[data-step="${ step }"]` );
		const elItemStep = elCreateCourseAIWrap.querySelector( `.step-item[data-step="${ step }"]` );
		elForm.querySelectorAll( '.step-content' ).forEach( ( el ) => el.classList.remove( 'active' ) );
		elContentStep.classList.add( 'active' );
		elCreateCourseAIWrap.querySelectorAll( '.step-item' ).forEach( ( el ) => el.classList.remove( 'active' ) );
		elItemStep.classList.add( 'active' );

		// Get all buttons step to show/hide
		const form = target.closest( 'form' );
		const elBtnSteps = form.querySelectorAll( 'button[data-step-show]' );
		elBtnSteps.forEach( ( el ) => {
			const stepsShow = el.dataset.stepShow.split( ',' ).map( ( s ) => parseInt( s.trim() ) );
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
					const elBtnNext = form.querySelector( '.lp-btn-step[data-action=next]' );
					elBtnNext.click();

					const elPromptTextarea = form.querySelector( 'textarea[name=lp-openai-prompt-generated-field]' );
					elPromptTextarea.value = data;
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
	generateDataCourse( args ) {
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
					const elResults = form.querySelector( '.lp-ai-generated-results' );
					elResults.innerHTML = data.lp_html_preview;

					const elBtnNext = form.querySelector( '.lp-btn-step[data-action=next]' );
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

	/**
	 * Create course with data of OpenAI
	 * @param args
	 */
	createCourse( args ) {
		const { e, target } = args;
		e.preventDefault();
		lpUtils.lpSetLoadingEl( target, true );

		// Get dataSend
		const dataSend = JSON.parse( target.dataset.send );
		dataSend.lp_structure_course = lp_structure_course;

		const form = target.closest( 'form' );
		const elBtnPrev = form.querySelector( '.lp-btn-step[data-action=prev]' );
		lpUtils.lpShowHideEl( elBtnPrev, 0 );

		const windowWidth = window.outerWidth;
		let alertWidth;
		if ( windowWidth < 768 ) {
			alertWidth = '90%';
		} else {
			alertWidth = '30%';
		}

		const creatingCourseAiModal = document.querySelector( '#lp-tmpl-creating-course-ai' );
		SweetAlert.fire( {
			html: creatingCourseAiModal.innerHTML,
			showCloseButton: false,
			showConfirmButton: false,
			allowOutsideClick: false,
			width: alertWidth,
		} );

		// Ajax to generate prompt
		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				SweetAlert.close();
				lpToastify.show( message, status );

				if ( status === 'success' ) {
					setTimeout(
						() => {
							window.location.href = data.edit_course_url;
						},
						2000
					);
				} else {
					lpUtils.lpShowHideEl( elBtnPrev, 1 );
				}
			},
			error: ( error ) => {
				lpToastify.show( error, 'error' );
				lpUtils.lpShowHideEl( elBtnPrev, 1 );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( target, false );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
}
