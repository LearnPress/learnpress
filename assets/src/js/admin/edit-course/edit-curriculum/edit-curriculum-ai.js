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

		//console.log( 'Generated Sections:', sections );

		SweetAlert.close();

		const editSection = new EditSection();
		editSection.init();
		for ( const section of sections ) {
			// generate code sleep 1 second between each add section to avoid conflict

			const section_title = section.section_title || '';
			const section_description = section.section_description || '';

			// Set title
			const elCurriculum = document.querySelector( editSection.className.idElEditCurriculum );
			const elSectionTitleNewInput = elCurriculum.querySelector( editSection.className.elSectionTitleNewInput );
			const elBtnAddSection = elCurriculum.querySelector( editSection.className.elBtnAddSection );
			elSectionTitleNewInput.value = section_title;

			const CBAfterSetSectionDescription = {
				success: ( response ) => {
					console.log( 'Set section description response:', response );
				},
				error: ( error ) => {

				},
				completed: () => {
				},
			};

			const CBAfterAddSection = {
				success: async ( newSection, response ) => {
					// Wait 1 second to run next section
					await new Promise( ( resolve ) => setTimeout( resolve, 1500 ) );

					const elSectionDesInput = newSection.querySelector( editSection.className.elSectionDesInput );
					// Set description for the new section
					elSectionDesInput.value = section_description;

					// Call AJAX to save description
					editSection.updateSectionDescription( {
						e: new PointerEvent( 'click' ),
						target: elSectionDesInput,
						callBackNest: CBAfterSetSectionDescription,
					} );
				},
				error: ( error ) => {

				},
				completed: () => {

				},
			};

			editSection.addSection( {
				e: new PointerEvent( 'click' ),
				target: elBtnAddSection,
				callBackNest: CBAfterAddSection,
			} );

			// Set description
			const elSectionDesInput = elCurriculum.querySelector( editSection.className.elSectionDesInput );
			elSectionDesInput.value = section_description;

			// Wait 1 second to run next section
			await new Promise( ( resolve ) => setTimeout( resolve, 1500 ) );
		}
	}
}
