import { lpShowHideEl } from '../../utils.js';

/**
 * Question Hint Toggle functionality
 * Handles showing/hiding quiz question hints when user clicks hint button
 */

document.addEventListener('DOMContentLoaded', () => {
	// Use event delegation to handle hint button clicks
	document.addEventListener('click', (e) => {
		const hintButton = e.target.closest('.btn-show-hint');
		
		if (hintButton) {
			e.preventDefault();
			handleHintToggle(hintButton);
		}
	});
});

/**
 * Handle hint toggle when hint button is clicked
 * @param {Element} hintButton - The hint button element that was clicked
 */
function handleHintToggle(hintButton) {
	// Find the question container
	const questionContainer = hintButton.closest('.question');
	
	if (!questionContainer) {
		return;
	}
	
	// Find the hint content element
	const hintContent = questionContainer.querySelector('.question-hint-content');
	
	if (!hintContent) {
		return;
	}
	
	// Check current state
	const isHidden = hintContent.classList.contains('lp-hidden');
	
	// Toggle visibility using lpShowHideEl
	// status 0: hide, 1: show
	lpShowHideEl(hintContent, isHidden ? 1 : 0);
}
