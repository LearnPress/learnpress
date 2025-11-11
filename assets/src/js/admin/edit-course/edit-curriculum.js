/**
 * Edit Curriculum JS handler.
 *
 * @since 4.2.8.6
 * @version 1.0.2
 */
import { EditSection } from './edit-curriculum/edit-section.js';
import { EditSectionItem } from './edit-curriculum/edit-section-item.js';
import * as lpUtils from 'lpAssetsJsPath/utils.js';

const sectionEdit = new EditSection();
const sectionItemEdit = new EditSectionItem();

export class EditCourseCurriculum {
	constructor() {
		this.init();
	}

	static selectors = {
		idElEditCurriculum: '#lp-course-edit-curriculum',
		elCurriculumSections: '.curriculum-sections',
		elToggleAllSections: '.course-toggle-all-sections',
		LPTarget: '.lp-target',
		elCollapse: 'lp-collapse',
	};

	init() {
		lpUtils.lpOnElementReady(
			`${ EditCourseCurriculum.selectors.idElEditCurriculum }`,
			( elEditCurriculum ) => {
				// Set variables use for section edit
				sectionEdit.init();

				// Set variables use for edit section item
				sectionItemEdit.init();
			}
		);
	}
}
