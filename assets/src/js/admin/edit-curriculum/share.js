/**
 * Share
 */
import * as lpUtils from '../../utils.js';
import Toastify from 'toastify-js';

let elEditCurriculum;
let elCurriculumSections;
let elLPTarget;
let dataSend;
const className = {
	idElEditCurriculum: '#lp-course-edit-curriculum',
	elCurriculumSections: '.curriculum-sections',
	elSectionNewInput: '.lp-section-new-input',
	elSection: '.section',
	elToggleAllSections: '.course-toggle-all-sections',
	elBtnSelectItems: 'lp-btn-select-items',
	btnSelectItemType: '.lp-btn-select-item-type',
	btnAddItem: 'lp-btn-add-item',
	elNewSectionItem: '.lp-new-section-item',
	elItemClone: 'section-item-clone',
	elItemNewInput: '.lp-item-new-input',
	elItemTitleInput: '.lp-item-title-input',
	elSectionItem: '.section-item',
	elSectionListItems: '.section-list-items',
	LPTarget: '.lp-target',
	elCollapse: 'lp-collapse',
	elSectionActions: '.section-actions',
	elBtnAddItemsSelected: '.lp-btn-add-items-selected',
};
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
const setVariables = ( variables ) => {
	( { elEditCurriculum, elCurriculumSections, dataSend } = variables );
};

export { setVariables, elEditCurriculum, elCurriculumSections, lpUtils, className, dataSend, showToast };
