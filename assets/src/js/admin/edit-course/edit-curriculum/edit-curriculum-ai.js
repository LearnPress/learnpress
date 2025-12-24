/**
 * Generate data with OpenAI
 */

import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import SweetAlert from 'sweetalert2';
import { EditSection } from './edit-section.js';
import { EditSectionItem } from './edit-section-item.js';
import { EditCourseCurriculum } from 'lpAssetsJsPath/admin/edit-course/edit-curriculum.js';

let editSection = null;
let editSectionItem = null;

export class EditCurriculumAi {
	constructor() {
		this.init();
	}

	static selectors = {
		elBtnApplyCurriculum: '.lp-btn-apply-curriculum',
	};

	init() {
		if ( ! lpData?.enable_open_ai ) {
			return;
		}

		lpUtils.lpOnElementReady(
			EditCourseCurriculum.selectors.idElEditCurriculum,
			( el ) => {
				const elCountSections = el.querySelector(
					EditSection.selectors.elCountSections
				);
				elCountSections.insertAdjacentHTML(
					'afterend',
					`<button type="button"
					class="lp-btn-generate-with-ai lp-btn-ai-style"
					data-template="#lp-tmpl-edit-course-curriculum-ai">
					<i class="lp-ico-ai"></i><span>Generate with AI</span>
				</button>`
				);
			}
		);

		this.events();
	}

	events() {
		if ( EditCurriculumAi._loadedEvents ) {
			return;
		}
		EditCurriculumAi._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: EditCurriculumAi.selectors.elBtnApplyCurriculum,
				class: this,
				callBack: this.applyData.name,
			},
		] );
	}

	async applyData( args ) {
		const { e, target } = args;
		let dataSend = JSON.parse( target.dataset.send );
		dataSend = lpUtils.mergeDataWithDatForm(
			target.closest( 'form' ),
			dataSend
		);

		if ( ! dataSend[ 'lp-openai-generated-data' ] ) {
			return;
		}

		const data = JSON.parse( dataSend[ 'lp-openai-generated-data' ] );

		//console.log( 'Generated Data:', data );

		const sections = data[ 0 ].sections;
		if ( ! sections || sections.length === 0 ) {
			lpToastify.show(
				'No sections found in the generated data.',
				'error'
			);
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
		const elEditCurriculum = document.querySelector(
			EditCourseCurriculum.selectors.idElEditCurriculum
		);
		const elDivAddNewSection = elEditCurriculum.querySelector(
			EditSection.selectors.elDivAddNewSection
		);
		elDivAddNewSection.scrollIntoView( {
			behavior: 'smooth',
			block: 'center',
		} );

		// Wait 800ms to ensure scroll completely
		await new Promise( ( resolve ) => setTimeout( resolve, 800 ) );

		for ( const sectionData of sections ) {
			// Set title
			const elSectionTitleNewInput = elEditCurriculum.querySelector(
				EditSection.selectors.elSectionTitleNewInput
			);
			const elBtnAddSection = elEditCurriculum.querySelector(
				EditSection.selectors.elBtnAddSection
			);
			elSectionTitleNewInput.value = sectionData.section_title || '';

			await new Promise( ( resolve ) => {
				editSection.addSection( {
					e: new PointerEvent( 'click' ),
					target: elBtnAddSection,
					callBackNest: this.updateSectionDescription( {
						sectionData,
						elEditCurriculum,
					} ),
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
				const elSectionDesInput = elSection.querySelector(
					EditSection.selectors.elSectionDesInput
				);
				elSectionDesInput.value = sectionData.section_description || '';

				// Call AJAX to save description
				await new Promise( ( resolve ) => {
					editSection.updateSectionDescription( {
						e: new PointerEvent( 'click' ),
						target: elSectionDesInput,
						callBackNest: this.addSectionItems( {
							sectionData,
							elEditCurriculum,
						} ),
						resolve,
					} );
				} );

				setTimeout( args.resolve, 1 );
			},
			error: ( error ) => {},
			completed: () => {},
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
					`${ EditSectionItem.selectors.elBtnSelectItemType }[data-item-type=lp_lesson]`
				);

				const elBtnSelectItemTypeQuiz = elSection.querySelector(
					`${ EditSectionItem.selectors.elBtnSelectItemType }[data-item-type=lp_quiz]`
				);

				for ( const itemData of lessons ) {
					elBtnSelectItemTypeLesson.click();

					await this.addItemToSection( {
						itemData,
						elSection,
						elEditCurriculum,
					} );
				}

				for ( const itemData of quizzes ) {
					elBtnSelectItemTypeQuiz.click();

					await this.addItemToSection( {
						itemData,
						elSection,
						elEditCurriculum,
					} );
				}

				setTimeout( args.resolve, 1 );
			},
			error: ( error ) => {},
			completed: () => {},
		};
	}

	async addItemToSection( args ) {
		const { itemData, elSection, elEditCurriculum } = args;

		const elBtnAddItem = elSection.querySelector(
			EditSectionItem.selectors.elBtnAddItem
		);
		const elAddItemTypeTitleInput = elSection.querySelector(
			EditSectionItem.selectors.elAddItemTypeTitleInput
		);
		elAddItemTypeTitleInput.value =
			itemData.lesson_title || itemData.quiz_title || '';

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
