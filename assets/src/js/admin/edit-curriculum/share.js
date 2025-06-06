/**
 * Share variables and functions for the edit curriculum page.
 *
 * @since 4.2.8.6
 * @version 1.0.0
 */
import * as lpUtils from '../../utils.js';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

let courseId;
let elEditCurriculum;
let elCurriculumSections;
let updateCountItems;
const className = {
	idElEditCurriculum: '#lp-course-edit-curriculum',
	elCurriculumSections: '.curriculum-sections',
	elSection: '.section',
	elToggleAllSections: '.course-toggle-all-sections',
	elSectionItem: '.section-item',
	LPTarget: '.lp-target',
	elCollapse: 'lp-collapse',
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
	( {
		courseId,
		elEditCurriculum,
		elCurriculumSections,
		updateCountItems,
	} = variables );
};

export {
	setVariables,
	showToast,
	lpUtils,
	className,
	courseId,
	elEditCurriculum,
	elCurriculumSections,
	updateCountItems,
};
