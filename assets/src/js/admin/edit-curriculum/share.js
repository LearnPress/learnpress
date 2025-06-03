/**
 * Share
 */
import * as lpUtils from '../../utils.js';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

let elEditCurriculum;
let elCurriculumSections;
let elLPTarget;
let dataSend;
let updateCountItems;
const className = {
	idElEditCurriculum: '#lp-course-edit-curriculum',
	elCurriculumSections: '.curriculum-sections',
	elSectionNewInput: '.lp-section-new-input',
	elSection: '.section',
	elSectionClone: 'section-clone',
	elToggleAllSections: '.course-toggle-all-sections',
	elBtnSelectItems: 'lp-btn-select-items',
	elSectionItem: '.section-item',
	elItemClone: 'section-item-clone',
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
	( { elEditCurriculum, elCurriculumSections, dataSend, updateCountItems } = variables );
};

export { setVariables, elEditCurriculum, elCurriculumSections, lpUtils, className, dataSend, showToast, updateCountItems };
