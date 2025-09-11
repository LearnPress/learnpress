/**
 * Edit Curriculum JS handler.
 *
 * @since 4.2.8.6
 * @version 1.0.1
 */

import * as sectionEdit from './edit-section.js';
import * as lpEditCurriculumShare from './share.js';

export class CourseAI {
	constructor() {
		this.init();
	}

	// For Title
	showPopupCreateTitle() {
		// Show popup here, use sweat alert insert html popup on TemplateHooks
	}
	generateTitleAI( paramSendAjaxToGenerate ) {
		// Call AJAX to generate title here.
	}
	applyTitleAI( titleValue ) {
		// Insert titleValue to title field then save.
	}
	// End For Title

	showPopupCreateDescription() {
		// Show popup here, use     sweat alert insert html popup on TemplateHooks
	}
	generateDescriptionAI() {
		// Call AJAX to generate description here.
	}

	// For Curriculum
	applyCurriculumAI( e, dataJSON ) {
		const target = document.querySelector( '.lp-btn-add-section' );
		const elSectionTitleNewInput = document.querySelector( '.lp-section-title-new-input' );
		elSectionTitleNewInput.value = dataJSON.section_name;

		const callBackCreateItemForSection = {
			success: ( elNewSection, response ) => {
				console.log( 1111 );
			},
		};

		const callBackUpdateSectionDescription = {
			success: ( elNewSection, response ) => {
				const { message, status, data } = response;

				const elSectionDesInput = elNewSection.querySelector( `${ sectionEdit.className.elSectionDesInput }` );
				elSectionDesInput.value = dataJSON.section_description;
				const elBtnUpdateDes = elNewSection.querySelector( `${ sectionEdit.className.elBtnUpdateDes }` );
				const e = new PointerEvent( 'click' );
				sectionEdit.updateSectionDescription( e, elBtnUpdateDes, callBackCreateItemForSection );
			},
		};
		sectionEdit.addSection( e, target, callBackUpdateSectionDescription );
	}
	// End for Curriculum

	// Events
	events() {
		console.log( 'ggggggggggg' );
		document.addEventListener( 'click', ( e ) => {
			const target = e.target;

			// Handle click show popup create title.
			if ( target.classList.contains( '.lp-btn-edit-course-title-ai' ) ) {
				showPopupCreateTitle();
			}

			// Handle click show popup create description.
			if ( target.classList.contains( '.lp-btn-edit-course-des-ai' ) ) {
				showPopupCreateDescription();
			}

			// Handle click generate title.
			if ( target.classList.contains( '.lp-btn-generate-course-title-ai' ) ) {
				const elFieldsInput = ''; // Get via form data.
				const paramSendAjaxToGenerate = {
					describe: '',
					audience: '',
					tone: '',
					language: '',
					number: '',
				};
				generateTitleAI( paramSendAjaxToGenerate );
			}

			// Handle click apply course title.
			if ( target.classList.contains( '.lp-btn-generate-course-title-ai' ) ) {
				const elTitleField = document.querySelector( '.class-input-here' );
				this.applyTitleAI( elTitleField.value );
			}

			// Test apply curriculum AI
			//console.log( target.id );
			if ( target.classList.contains( 'plural' ) ) {
				console.log( 'Test apply curriculum AI' );
				//e.preventDefault();
				const dataJSON = {
					course_id: 72,
					section_name: 'New Section',
					section_description: 'This is section description',
					items: [
						{
							type: 'lp_lesson',
							title: 'Lesson 1',
						},
						{
							type: 'lp_quiz',
							title: 'Quiz 1',
						},
					],
				};
				this.applyCurriculumAI( e, dataJSON );
			}
		} );
	}

	init() {
		this.events();
	}
}
