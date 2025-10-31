/**
 * Generate data with OpenAI
 */

import * as lpUtils from './../utils.js';
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

export class GenerateWithOpenai {
	constructor() {
		this.init();
		this.selector = {
			elGenerateDataAiWrap: '.lp-generate-data-ai-wrap',
		};
		this.dataGenerate = '';
	}

	init() {
		lpUtils.lpOnElementReady( '#titlewrap', ( el ) => {
			el.insertAdjacentHTML(
				'afterend',
				`<button type="button"
					class="lp-btn-generate-title-with-ai lp-button button-secondary"
					data-template="#lp-tmpl-edit-title-ai">
					Generate Title with AI
				</button>`
			);
		} );

		this.events();
	}

	events() {
		lpUtils.eventHandlers( 'click', [
			{
				selector: '.lp-btn-generate-title-with-ai',
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
		] );
	}

	showPopup( args ) {
		const { e, target } = args;
		const templateId = target.dataset.template;

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
				const popup = SweetAlert.getPopup();
				// Click to show tomSelect style
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

		if ( step === 2 ) {
			lpUtils.lpShowHideEl( elBtnNext, 0 );
			lpUtils.lpShowHideEl( elBtnGeneratePrompt, 1 );
		} else {
			lpUtils.lpShowHideEl( elBtnGeneratePrompt, 0 );
		}

		if ( step === 3 ) {
			lpUtils.lpShowHideEl( elBtnNext, 0 );
			lpUtils.lpShowHideEl( elBtnCallOpenAI, 1 );
		} else {
			lpUtils.lpShowHideEl( elBtnCallOpenAI, 0 );
		}
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
					const elResults = form.querySelector( '.lp-ai-generated-results' );
					elResults.innerHTML = data.lp_html_preview;

					// const elBtnNext = form.querySelector( '.lp-btn-step[data-action=next]' );
					// elBtnNext.click();
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

	applyGeneratedData( args ) {
		const { e, target } = args;
		e.preventDefault();

		const dataApply = target.dataset.apply;
		const elPostTitle = document.querySelector( 'input[name=post_title]' );
		elPostTitle.value = dataApply;
	}

	copyGeneratedData( args ) {
		const { e, target } = args;
		e.preventDefault();

		const dataCopy = target.dataset.copy;

		navigator.clipboard.writeText( dataCopy ).then( () => {
			showToast( 'Copied to clipboard!', 'success' );
		} ).catch( ( err ) => {
			showToast( 'Failed to copy text: ' + err, 'error' );
		} );
	}
}
