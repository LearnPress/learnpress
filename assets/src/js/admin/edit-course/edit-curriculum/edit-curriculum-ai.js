/**
 * Generate data with OpenAI
 */

import * as lpUtils from './../../../utils.js';
import SweetAlert from 'sweetalert2';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';
import { EditSection } from './edit-section.js';
import { EditSectionItem } from './edit-section-item.js';
import * as lpEditCurriculumShare from './share';

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
const popupSweetAlert = null;

let editSection = null;
let editSectionItem = null;

export class EditCurriculumAi {
	constructor() {
		this.init();
		this.selector = {
			elGenerateDataAiWrap: '.lp-generate-data-ai-wrap',
		};
		this.dataGenerate = '';
	}

	init() {
		lpUtils.lpOnElementReady( '#lp-course-edit-curriculum', ( el ) => {
			const elInside = el.querySelector( '.count-sections' );
			elInside.insertAdjacentHTML(
				'afterend',
				`<button type="button"
					class="lp-btn-generate-with-ai button-secondary"
					data-template="#lp-tmpl-edit-course-curriculum-ai">
					Generate Curriculum with AI
				</button>`
			);
		} );

		this.events();
	}

	events() {
		lpUtils.eventHandlers( 'click', [
			{
				selector: '.lp-btn-apply-curriculum',
				class: this,
				callBack: this.applyData.name,
			},
		] );
	}

	async applyData( args ) {
		const { e, target } = args;
		let dataSend = JSON.parse( target.dataset.send );
		dataSend = lpUtils.mergeDataWithDatForm( target.closest( 'form' ), dataSend );

		if ( ! dataSend[ 'lp-openai-generated-data' ] ) {
			return;
		}

		const data = JSON.parse( dataSend[ 'lp-openai-generated-data' ] );

		//console.log( 'Generated Data:', data );

		const sections = data[ 0 ].sections;
		if ( ! sections || sections.length === 0 ) {
			showToast( 'No sections found in the generated data.', 'error' );
		}

		console.log( 'Generated Sections:', sections );

		SweetAlert.close();

		// Wait half second to ensure SweetAlert is closed completely
		await new Promise( ( resolve ) => setTimeout( resolve, 500 ) );

		// New edit section instance
		editSection = new EditSection();
		editSection.init();

		// New edit section item instance
		editSectionItem = new EditSectionItem();
		editSectionItem.init();

		// Scroll to element add section
		const elEditCurriculum = document.querySelector( editSection.className.idElEditCurriculum );
		const elDivAddNewSection = elEditCurriculum.querySelector( editSection.className.elDivAddNewSection );
		elDivAddNewSection.scrollIntoView( { behavior: 'smooth', block: 'center' } );

		// Wait 800ms to ensure scroll completely
		await new Promise( ( resolve ) => setTimeout( resolve, 800 ) );

		for ( const sectionData of sections ) {
			// Set title
			const elSectionTitleNewInput = elEditCurriculum.querySelector( editSection.className.elSectionTitleNewInput );
			const elBtnAddSection = elEditCurriculum.querySelector( editSection.className.elBtnAddSection );
			elSectionTitleNewInput.value = sectionData.section_title || '';

			await new Promise( ( resolve ) => {
				editSection.addSection( {
					e: new PointerEvent( 'click' ),
					target: elBtnAddSection,
					callBackNest: this.updateSectionDescription( { sectionData, elEditCurriculum } ),
					resolve,
				} );
			} );
		}
	}

	/**
	 * Update section description after create new section
	 * @param args
	 */
	updateSectionDescription( args ) {
		const { sectionData, elEditCurriculum } = args;

		return {
			success: async ( args ) => {
				const { elSection } = args;
				await new Promise( ( resolve ) => setTimeout( resolve, 1000 ) );

				// Set description for the new section
				const elSectionDesInput = elSection.querySelector( editSection.className.elSectionDesInput );
				elSectionDesInput.value = sectionData.section_description || '';

				// Call AJAX to save description
				await new Promise( ( resolve ) => {
					editSection.updateSectionDescription( {
						e: new PointerEvent( 'click' ),
						target: elSectionDesInput,
						callBackNest: this.addSectionItems( { sectionData, elEditCurriculum } ),
						resolve,
					} );
				} );

				setTimeout( args.resolve, 1 );
			},
			error: ( error ) => {

			},
			completed: () => {

			},
		};
	}

	addSectionItems( args ) {
		const { sectionData, elEditCurriculum } = args;
		const lessons = sectionData.lessons || [];
		const quizzes = sectionData.quizzes || [];

		return {
			success: async ( args ) => {
				//await new Promise( ( resolve ) => setTimeout( resolve, 1000 ) );
				const { elSection } = args;

				const elBtnSelectItemTypeLesson = elSection.querySelector(
					`${ editSectionItem.className.elBtnSelectItemType }[data-item-type=lp_lesson]` );

				const elBtnSelectItemTypeQuiz = elSection.querySelector(
					`${ editSectionItem.className.elBtnSelectItemType }[data-item-type=lp_quiz]` );

				for ( const itemData of lessons ) {
					elBtnSelectItemTypeLesson.click();

					await this.addItemToSection( { itemData, elSection, elEditCurriculum } );
				}

				for ( const itemData of quizzes ) {
					elBtnSelectItemTypeQuiz.click();

					await this.addItemToSection( { itemData, elSection, elEditCurriculum } );
				}

				setTimeout( args.resolve, 1 );
			},
			error: ( error ) => {

			},
			completed: () => {
			},
		};
	}

	async addItemToSection( args ) {
		const { itemData, elSection, elEditCurriculum } = args;

		const elBtnAddItem = elSection.querySelector( editSectionItem.className.elBtnAddItem );
		const elAddItemTypeTitleInput = elSection.querySelector( editSectionItem.className.elAddItemTypeTitleInput );
		elAddItemTypeTitleInput.value = itemData.lesson_title || itemData.quiz_title || '';

		// Scroll to element add item
		elBtnAddItem.scrollIntoView( { behavior: 'smooth', block: 'center' } );

		// Call AJAX to add item to section
		await new Promise( ( resolve ) => {
			editSectionItem.addItemToSection( {
				e: new PointerEvent( 'click' ),
				target: elBtnAddItem,
				resolve,
				callBackNest: {
					completed: async ( args ) => {
						setTimeout( resolve, 1000 );
					},
				},
			} );
		} );

		setTimeout( args.resolve, 1 );
	}
}
