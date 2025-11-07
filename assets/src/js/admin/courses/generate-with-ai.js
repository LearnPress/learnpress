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
		this.selector = {
			elGenerateDataAiWrap: '.lp-generate-data-ai-wrap',
		};
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
				popup.click();
			},
		} ).then( ( result ) => {
			if ( result.isDismissed ) {}
		} );
	}

	showStep( args ) {
		const { e, target } = args;
		e.preventDefault();

		const elBtnActions = target.closest( '.button-actions' );
		const elCreateCourseAIWrap = elBtnActions.closest( this.selector.elGenerateDataAiWrap );
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
