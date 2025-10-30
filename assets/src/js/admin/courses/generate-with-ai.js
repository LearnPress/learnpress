/**
 * Create course with AI
 */

import * as lpUtils from './../../utils.js';
import SweetAlert from 'sweetalert2';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

const argsToastify = {
	text: '',
	gravity: lpDataAdmin.toast.gravity, // `top` or `bottom`
	position: lpDataAdmin.toast.position, // `left`, `center` or `right`
	className: `${ lpDataAdmin.toast.classPrefix }`,
	close: lpDataAdmin.toast.close == 1,
	stopOnFocus: lpDataAdmin.toast.stopOnFocus == 1,
	duration: lpDataAdmin.toast.duration,
};
const showToast = ( message, status = 'success' ) => {
	const toastify = new Toastify( {
		...argsToastify,
		text: message,
		className: `${ lpDataAdmin.toast.classPrefix } ${ status }`,
	} );
	toastify.showToast();
};
let lp_structure_course;

export class CreateCourseViaAI {
	constructor() {
		this.init();
	}

	init() {
		lpUtils.lpOnElementReady( '.page-title-action', ( el ) => {
			el.insertAdjacentHTML(
				'afterend',
				`<button type="button" class="lp-btn-generate-course-with-ai lp-button button-primary ">Generate with AI</button>`
			);
		} );

		this.events();
	}

	events() {
		lpUtils.eventHandlers( 'click', [
			{
				selector: '.lp-btn-generate-course-with-ai',
				callBack: this.showPopupCreateFullCourse,
			},
			{
				selector: '.lp-btn-step',
				callBack: this.showStep,
			},
			{
				selector: '.lp-btn-generate-prompt',
				callBack: this.generatePrompt,
			},
			{
				selector: '.lp-btn-call-open-ai',
				callBack: this.generateDataCourse,
			},
			{
				selector: '.lp-btn-create-course',
				callBack: this.createCourse,
			},
		] );
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
			showCloseButton: true,
			showConfirmButton: false,
			didOpen: () => {
				const popup = SweetAlert.getPopup();
				const elCreateCourseWrap = popup.querySelector( '.lp-create-course-ai-wrap' );
				// Click to show tomSelect style
				elCreateCourseWrap.click();
			},
		} ).then( ( result ) => {
			if ( result.isDismissed ) {}
		} );
	}

	showStep( e, target ) {
		e.preventDefault();

		const elBtnActions = target.closest( '.button-actions' );
		const elCreateCourseAIWrap = elBtnActions.closest( '.lp-create-course-ai-wrap' );
		let step = parseInt( elBtnActions.dataset.step );
		const stepMax = parseInt( elBtnActions.dataset.stepMax );
		const elBtnNext = elBtnActions.querySelector( '.lp-btn-step[data-action=next]' );
		const elBtnPrev = elBtnActions.querySelector( '.lp-btn-step[data-action=prev]' );
		const elBtnGeneratePrompt = elBtnActions.querySelector( '.lp-btn-generate-prompt' );
		const elBtnCallOpenAI = elBtnActions.querySelector( '.lp-btn-call-open-ai' );
		const elBtnCreateCourse = elBtnActions.querySelector( '.lp-btn-create-course' );

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

		if ( step === 1 ) {
			lpUtils.lpShowHideEl( elBtnPrev, 0 );
		} else {
			lpUtils.lpShowHideEl( elBtnPrev, 1 );
		}

		if ( step === stepMax || step > stepMax ) {
			lpUtils.lpShowHideEl( elBtnNext, 0 );
		} else {
			lpUtils.lpShowHideEl( elBtnNext, 1 );
		}

		if ( step === 3 ) {
			lpUtils.lpShowHideEl( elBtnNext, 0 );
			lpUtils.lpShowHideEl( elBtnGeneratePrompt, 1 );
		} else {
			lpUtils.lpShowHideEl( elBtnGeneratePrompt, 0 );
		}

		if ( step === 4 ) {
			lpUtils.lpShowHideEl( elBtnCallOpenAI, 1 );
		} else {
			lpUtils.lpShowHideEl( elBtnCallOpenAI, 0 );
		}

		if ( step === 5 ) {
			lpUtils.lpShowHideEl( elBtnCreateCourse, 1 );
		} else {
			lpUtils.lpShowHideEl( elBtnCreateCourse, 0 );
		}
	}

	/**
	 * Create prompt from data config
	 *
	 * @param e
	 * @param target
	 */
	generatePrompt( e, target ) {
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

				showToast( message, status );

				if ( status === 'success' ) {
					const elBtnNext = form.querySelector( '.lp-btn-step[data-action=next]' );
					elBtnNext.click();

					const elPromptTextarea = form.querySelector( 'textarea[name=lp-openai-prompt-generated-field]' );
					elPromptTextarea.value = data;
				}
			},
			error: ( error ) => {
				showToast( error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( target, false );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/**
	 * Submit prompt to OpenAI to generate course data
	 *
	 * @param e
	 * @param target
	 */
	generateDataCourse( e, target ) {
		e.preventDefault();
		lpUtils.lpSetLoadingEl( target, true );

		// Get dataSend
		const form = target.closest( 'form' );
		let dataSend = JSON.parse( target.dataset.send );
		dataSend = lpUtils.mergeDataWithDatForm( form, dataSend );

		const btnPrev = form.querySelector( '.lp-btn-step[data-action=prev]' );
		lpUtils.lpShowHideEl( btnPrev, 0 );

		setTimeout( () => {
			showToast( 'Generating course data. This may take a few moments...', 'info' );
		}, 1000 );

		// Ajax to generate prompt
		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				showToast( message, status );

				if ( status === 'success' ) {
					// Save structure data
					lp_structure_course = data.lp_structure_course;

					// Set preview HTML
					const elPreviewWrap = form.querySelector( '.lp-ai-course-data-preview-wrap' );
					elPreviewWrap.innerHTML = data.lp_html_preview;

					const elBtnNext = form.querySelector( '.lp-btn-step[data-action=next]' );
					elBtnNext.click();
				}
			},
			error: ( error ) => {
				showToast( error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( target, false );
				lpUtils.lpShowHideEl( btnPrev, 1 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	/**
	 * Create course with data of OpenAI
	 *
	 * @param e
	 * @param target
	 */
	createCourse( e, target ) {
		e.preventDefault();
		lpUtils.lpSetLoadingEl( target, true );

		// Get dataSend
		const dataSend = JSON.parse( target.dataset.send );
		dataSend.lp_structure_course = lp_structure_course;

		const form = target.closest( 'form' );
		const elBtnPrev = form.querySelector( '.lp-btn-step[data-action=prev]' );
		lpUtils.lpShowHideEl( elBtnPrev, 0 );

		// Ajax to generate prompt
		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				showToast( message, status );

				if ( status === 'success' ) {
					target.text = data.button_label;
					setTimeout(
						() => {
							window.location.href = data.edit_course_url;
						},
						1000
					);
				} else {
					lpUtils.lpShowHideEl( elBtnPrev, 1 );
				}
			},
			error: ( error ) => {
				showToast( error, 'error' );
				lpUtils.lpShowHideEl( elBtnPrev, 1 );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( target, false );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
}
