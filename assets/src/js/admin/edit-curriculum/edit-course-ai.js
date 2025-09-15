/**
 *
 * @since 4.2.8.6
 * @version 1.3.2
 */

import TomSelect from 'tom-select';
import Swal from 'sweetalert2';
import * as sectionEdit from './edit-section.js';
import * as lpEditCurriculumShare from './share.js';

const buildSelectOptions = (options, defaultValue = '') => {
	let html = `<option value="">${defaultValue}</option>`;
	for (const [key, value] of Object.entries(options)) {
		html += `<option value="${key}">${value}</option>`;
	}
	return html;
};

export class CourseAI {
	constructor() {
		this.init();
	}

	showPopupCreateTitle() {
		const {i18n, options} = lpCourseAiModalData;

		const modalHtml = `
			<div class="modal-content">
				<div class="input-section">
					<h3>${i18n.describeCourseAbout}</h3>
					<textarea id="swal-course-topic" placeholder="e.g. A course to teach how to use LearnPress"></textarea>
					<h3>${i18n.describeCourseGoals}</h3>
					<textarea id="swal-course-goals" placeholder="e.g. A course for beginners to learn WordPress"></textarea>
					<h3>${i18n.audience}</h3>
					<select id="swal-audience" multiple>${buildSelectOptions(options.audience)}</select>
					<h3>${i18n.tone}</h3>
					<select id="swal-tone" multiple>${buildSelectOptions(options.tone)}</select>
					<h3>${i18n.outputLanguage}</h3>
					<select id="swal-language">${buildSelectOptions(options.language)}</select>
					<div class="outputs-control">
						<h3>${i18n.outputs}</h3>
						<div class="outputs-control-content">
						<div class="output-number-selector">
							<button id="lp-ai-minus-output-button" type="button">-</button>
							<span id="lp-ai-output-count">02</span>
							<button id="lp-ai-plus-output-button" type="button">+</button>
						</div>
							</div>
						</div>
					</div>

				<div class="output-section">
					<div class="output-header">
						<h3>Output</h3>
						<div class="header-icons">
							<button class="icon-button">
								<img width="18" height="18" src="https://cdn-icons-png.flaticon.com/512/6808/6808309.png" alt="Maximize"/>
							</button>
						</div>
					</div>
					<div class="output-item" id="lp-ai-output-prompt">
						<p class="prompt">${i18n.prompt || 'Prompt:'}</p>
						<textarea class="prompt-text" rows="6" id="lp-ai-output-prompt-desc" placeholder="e.g. A course to teach how to use LearnPress"></textarea>
					</div>
					<div id="lp-ai-output-suggestion"></div>
				</div>
			</div>
			`;

		Swal.fire({
			title: i18n.createCourseTitle,
			html: modalHtml,
			showConfirmButton: true,
			confirmButtonText: i18n.generate,
			customClass: {
				popup: 'create-course-modal',
				confirmButton: 'generate-button',
				actions: 'input-section'
			},
			width: '1000px',
			showCloseButton: true,
			didOpen: () => {

				const audienceSelect = new TomSelect('#swal-audience', {plugins: ['remove_button']});
				const toneSelect = new TomSelect('#swal-tone', {plugins: ['remove_button']});
				const languageSelect = new TomSelect('#swal-language', {});

				try {
					const savedAudience = localStorage.getItem('lp_ai_audience');
					if (savedAudience) {
						audienceSelect.setValue(JSON.parse(savedAudience));
					}

					const savedTone = localStorage.getItem('lp_ai_tone');
					if (savedTone) {
						toneSelect.setValue(savedTone);
					}

					const savedLang = localStorage.getItem('lp_ai_lang');
					if (savedLang) {
						languageSelect.setValue(savedLang);
					}
				} catch (e) {
					console.error('Lỗi khi tải cài đặt AI từ localStorage:', e);
				}

				const countEl = document.getElementById('lp-ai-output-count');
				document.getElementById('lp-ai-minus-output-button').addEventListener('click', () => {
					let count = parseInt(countEl.textContent, 10);
					if (count > 1) count--;
					countEl.textContent = count.toString().padStart(2, '0');
				});
				document.getElementById('lp-ai-plus-output-button').addEventListener('click', () => {
					let count = parseInt(countEl.textContent, 10);
					if (count < 10) count++;
					countEl.textContent = count.toString().padStart(2, '0');
				});

				const actionsContainer = Swal.getActions();
				const inputSection = Swal.getPopup().querySelector('.outputs-control-content');
				if (actionsContainer && inputSection) {
					inputSection.appendChild(actionsContainer);
				}
			},
			preConfirm: () => {

				const confirmButton = Swal.getConfirmButton();
				if (confirmButton) {
					confirmButton.disabled = true;
					Swal.showLoading();
				}
				const popup = Swal.getPopup();
				const formData = {
					topic: popup.querySelector('#swal-course-topic').value,
					goal: popup.querySelector('#swal-course-goals').value,
					audience: popup.querySelector('#swal-audience')?.tomselect?.getValue() ?? [],
					tone: popup.querySelector('#swal-tone')?.tomselect?.getValue(),
					lang: [popup.querySelector('#swal-language')?.tomselect?.getValue()],
					outputs: parseInt(popup.querySelector('#lp-ai-output-count').textContent, 10)
				};

				try {
					localStorage.setItem('lp_ai_audience', JSON.stringify(formData.audience));
					localStorage.setItem('lp_ai_tone', formData.tone);
					localStorage.setItem('lp_ai_lang', formData.lang);
				} catch (e) {
					console.error('Lỗi khi lưu cài đặt AI vào localStorage:', e);
				}

				this.generateContent('course-title', formData, this.showResultPopup, this.applyTitleAI)
					.catch(err => {
						Swal.showValidationMessage(`Request failed: ${err.message}`);
					});
				return false;
			}
		}).then((result) => {

		});
	}

	applyTitleAI({text}) {
		const titleNode = document.querySelector('#post-body-content #title');
		const titleLabelNode = document.querySelector('#post-body-content #title-prompt-text');
		if (titleLabelNode) {
			titleLabelNode.classList.add('screen-reader-text');
		}
		if (titleNode) {
			titleNode.value = text.trim();
			Swal.fire({
				title: lpCourseAiModalData.i18n.applied,
				icon: 'success',
				timer: 1000,
				showConfirmButton: false
			});
		}
	}

	// For Description
	showPopupCreateDescription() {
		const {i18n, options} = lpCourseAiModalData;

		const modalHtml = `
			<div class="modal-content">
				<div class="input-section">
					<h3>${i18n.describeCourseStandOut}</h3>
					<textarea id="swal-course-desc" placeholder="e.g. A course to teach how to use LearnPress"></textarea>

					<h3>${i18n.audience}</h3>
					<select id="swal-audience" multiple>${buildSelectOptions(options.audience)}</select>
					<h3>${i18n.tone}</h3>
					<select id="swal-tone" multiple>${buildSelectOptions(options.tone)}</select>
					<h3>${i18n.outputLanguage}</h3>
					<select id="swal-language">${buildSelectOptions(options.language)}</select>
					<div class="outputs-control">
						<h3>${i18n.outputs}</h3>
						<div class="outputs-control-content">
						<div class="output-number-selector">
							<button id="lp-ai-minus-output-button" type="button">-</button>
							<span id="lp-ai-output-count">02</span>
							<button id="lp-ai-plus-output-button" type="button">+</button>
						</div>
							</div>
						</div>
					</div>

				<div class="output-section">
					<div class="output-header">
						<h3>Output</h3>
						<div class="header-icons">
							<button class="icon-button">
								<img width="18" height="18" src="https://cdn-icons-png.flaticon.com/512/6808/6808309.png" alt="Maximize"/>
							</button>
						</div>
					</div>
					<div class="output-item" id="lp-ai-output-prompt">
						<p class="prompt">${i18n.prompt || 'Prompt:'}</p>
						<textarea class="prompt-text" rows="6" id="lp-ai-output-prompt-desc" placeholder="e.g. A course to teach how to use LearnPress"></textarea>
					</div>
					<div id="lp-ai-output-suggestion"></div>
				</div>
			</div>
			`;

		Swal.fire({
			title: i18n.createCourseDescription,
			html: modalHtml,
			showConfirmButton: true,
			confirmButtonText: i18n.generate,
			customClass: {
				popup: 'create-course-modal',
				confirmButton: 'generate-button',
				actions: 'input-section'
			},
			width: '1000px',
			showCloseButton: true,
			didOpen: () => {

				const audienceSelect = new TomSelect('#swal-audience', {plugins: ['remove_button']});
				const toneSelect = new TomSelect('#swal-tone', {plugins: ['remove_button']});
				const languageSelect = new TomSelect('#swal-language', {});

				try {
					const savedAudience = localStorage.getItem('lp_ai_audience');
					if (savedAudience) {
						audienceSelect.setValue(JSON.parse(savedAudience));
					}

					const savedTone = localStorage.getItem('lp_ai_tone');
					if (savedTone) {
						toneSelect.setValue(savedTone);
					}

					const savedLang = localStorage.getItem('lp_ai_lang');
					if (savedLang) {
						languageSelect.setValue(savedLang);
					}
				} catch (e) {
					console.error('Lỗi khi tải cài đặt AI từ localStorage:', e);
				}

				const countEl = document.getElementById('lp-ai-output-count');
				document.getElementById('lp-ai-minus-output-button').addEventListener('click', () => {
					let count = parseInt(countEl.textContent, 10);
					if (count > 1) count--;
					countEl.textContent = count.toString().padStart(2, '0');
				});
				document.getElementById('lp-ai-plus-output-button').addEventListener('click', () => {
					let count = parseInt(countEl.textContent, 10);
					if (count < 10) count++;
					countEl.textContent = count.toString().padStart(2, '0');
				});

				const actionsContainer = Swal.getActions();
				const inputSection = Swal.getPopup().querySelector('.outputs-control-content');
				if (actionsContainer && inputSection) {
					inputSection.appendChild(actionsContainer);
				}
			},
			preConfirm: () => {

				const confirmButton = Swal.getConfirmButton();
				if (confirmButton) {
					confirmButton.disabled = true;
					Swal.showLoading();
				}
				const popup = Swal.getPopup();
				const titleCourse = document.querySelector('#post-body-content #title');
				const formData = {
					topic: popup.querySelector('#swal-course-desc').value,
					audience: popup.querySelector('#swal-audience')?.tomselect?.getValue() ?? [],
					tone: popup.querySelector('#swal-tone')?.tomselect?.getValue(),
					lang: [popup.querySelector('#swal-language')?.tomselect?.getValue()],
					outputs: parseInt(popup.querySelector('#lp-ai-output-count').textContent, 10),
					title: titleCourse.value ?? ""
				};

				try {
					localStorage.setItem('lp_ai_audience', JSON.stringify(formData.audience));
					localStorage.setItem('lp_ai_tone', formData.tone);
					localStorage.setItem('lp_ai_lang', formData.lang);
				} catch (e) {
					console.error('Lỗi khi lưu cài đặt AI vào localStorage:', e);
				}

				this.generateContent('course-description', formData, this.showResultPopup, this.applyDescriptionAI)
					.catch(err => {
						Swal.showValidationMessage(`Request failed: ${err.message}`);
					});
				return false;
			}
		}).then((result) => {

		});
	}

	applyDescriptionAI({ text }) {
		if (window.tinymce && tinymce.activeEditor) {
			tinymce.activeEditor.setContent(text.trim());
		}else {
			console.warn('tinymce.activeEditor editor not found');
		}

		if (window.tinymce) {
	const editor = tinymce.get('content') || tinymce.get('post_content');
	if (editor) {

		editor.setContent(text.trim());
		Swal.fire({
			title: lpCourseAiModalData.i18n.applied,
			icon: 'success',
			timer: 1000,
			showConfirmButton: false
		});
	} else {
	console.warn('TinyMCE editor not found');
}
} else {
	console.warn('TinyMCE is not loaded');
}
}


	// // For Feature Image
	// showPopupFeatureImage() {
	// 	const { i18n, options, modelImage } = lpCourseAiModalData;
	// 	let sizeSelector = `<label>${i18n.sizeImage}</label><select id="swal-image-size">${buildSelectOptions(options.imageSize)}</select>`;
	// 	let qualitySelector = modelImage === 'dall-e-3' ? `<label>${i18n.qualityImage}</label><select id="swal-image-quality">${buildSelectOptions(options.imageQuality)}</select>` : '';
	//
	// 	Swal.fire({
	// 		title: i18n.createFeaturedImage,
	// 		html: `
	// 			<div class="lp-ai-modal-form">
	//                 <label>${i18n.style}</label>
	//                 <select id="swal-image-style" multiple>${buildSelectOptions(options.imageStyle)}</select>
	//                 <label>${i18n.imagesOrIcons}</label>
	//                 <textarea id="swal-image-desc" class="swal2-textarea" placeholder="e.g. a computer, a lightbulb icon"></textarea>
	//                 ${sizeSelector}
	// 				${qualitySelector}
	//             </div>
	// 		`,
	// 		confirmButtonText: i18n.generate,
	// 		customClass: { popup: 'lp-ai-sweetalert' },
	// 		width: '800px',
	// 		showCloseButton: true,
	// 		didOpen: () => {
	// 			new TomSelect('#swal-image-style', { plugins: ['remove_button'] });
	// 			new TomSelect('#swal-image-size', {});
	// 			if (modelImage === 'dall-e-3') {
	// 				new TomSelect('#swal-image-quality', {});
	// 			}
	// 		},
	// 		preConfirm: () => {
	// 			const popup = Swal.getPopup();
	// 			const data = {
	// 				topic: popup.querySelector('#swal-image-desc').value,
	// 				style: new TomSelect(popup.querySelector('#swal-image-style')).getValue(),
	// 				size: [new TomSelect(popup.querySelector('#swal-image-size')).getValue()],
	// 				title: document.querySelector('#post-body-content #title').value ?? '',
	// 			};
	// 			if (modelImage === 'dall-e-3') {
	// 				data.quality = [new TomSelect(popup.querySelector('#swal-image-quality')).getValue()];
	// 			}
	// 			return data;
	// 		}
	// 	}).then((result) => {
	// 		if (result.isConfirmed) {
	// 			this.generateContent('course-feature-image', result.value, this.showImageResultsPopup, this.applyFeatureImageAI);
	// 		}
	// 	});
	// }
	//
	// applyFeatureImageAI(image_url) {
	// 	const { i18n, nonce } = lpCourseAiModalData;
	// 	Swal.fire({
	// 		title: i18n.generating,
	// 		text: i18n.pleaseWait,
	// 		allowOutsideClick: false,
	// 		didOpen: () => Swal.showLoading()
	// 	});
	//
	// 	const post_id = document.querySelector('#post_ID')?.value || 0;
	//
	// 	const formData = new FormData();
	// 	formData.append('action', 'lp_ajax');
	// 	formData.append('lp-load-ajax', 'save_feature_image');
	// 	formData.append('nonce', nonce);
	// 	formData.append('image_url', image_url);
	// 	formData.append('post_id', post_id);
	//
	// 	fetch(lpCourseAiModalData.ajaxUrl, {
	// 		method: 'POST',
	// 		body: formData,
	// 	})
	// 		.then(response => response.json())
	// 		.then(res => {
	// 			if (res.success) {
	// 				Swal.fire({ title: i18n.applied, text: 'Reloading page...', icon: 'success', timer: 1500, showConfirmButton: false })
	// 					.then(() => location.reload());
	// 			} else {
	// 				Swal.fire(i18n.errorOccurred, res.data.message || 'Unknown error', 'error');
	// 			}
	// 		})
	// 		.catch(err => {
	// 			Swal.fire(i18n.errorOccurred, err.message, 'error');
	// 		});
	// }

	// For Curriculum
	// showPopupCreateCurriculum() {
	// 	const { i18n, options } = lpCourseAiModalData;
	//
	// 	Swal.fire({
	// 		title: i18n.createCourseCurriculum,
	// 		html: `
	// 			<div class="lp-ai-modal-form lp-ai-modal-grid">
	//                 <div><label>${i18n.sections}</label><input type="number" id="swal-curriculum-sections" class="swal2-input" value="3"></div>
	//                 <div><label>${i18n.lessonsPerSection}</label><input type="number" id="swal-curriculum-lessons" class="swal2-input" value="5"></div>
	//                 <div><label>${i18n.levels}</label><select id="swal-curriculum-levels">${buildSelectOptions(options.levels)}</select></div>
	//                 <div><label>${i18n.outputLanguage}</label><select id="swal-language">${buildSelectOptions(options.language)}</select></div>
	//                 <div class="full-width"><label>${i18n.specificKeyTopics}</label><textarea id="swal-curriculum-topics" class="swal2-textarea" placeholder="e.g. Common mistakes, best practices"></textarea></div>
	//             </div>
	// 		`,
	// 		confirmButtonText: i18n.generate,
	// 		customClass: { popup: 'lp-ai-sweetalert' },
	// 		width: '800px',
	// 		showCloseButton: true,
	// 		didOpen: () => {
	// 			new TomSelect('#swal-curriculum-levels', {});
	// 			new TomSelect('#swal-language', {});
	// 		},
	// 		preConfirm: () => {
	// 			const popup = Swal.getPopup();
	//
	// 			const getSelectValue = (selector) => {
	// 				const el = popup.querySelector(selector);
	// 				return el && el.tomselect ? el.tomselect.getValue() : [];
	// 			};
	//
	// 			return {
	// 				topic: popup.querySelector('#swal-course-topic').value,
	// 				goal: popup.querySelector('#swal-course-goals').value,
	// 				audience: getSelectValue('#swal-audience'),
	// 				tone: getSelectValue('#swal-tone'),
	// 				lang: getSelectValue('#swal-language'),
	// 			};
	// 		}
	//
	// 	}).then((result) => {
	// 		if (result?.isConfirmed) {
	// 			this.generateContent('course-curriculum', result.value, this.showCurriculumResultsPopup, this.applyCurriculumAI);
	// 		}
	// 	});
	// }

	// async applyCurriculumAI({ dataLessonsStr }) {
	// 	const elEditCurriculum = document.querySelector(lpEditCurriculumShare.className.idElEditCurriculum);
	// 	const dataSend = window.lpAJAXG.getDataSetCurrent(document.querySelector(lpEditCurriculumShare.className.LPTarget));
	//
	// 	if (!elEditCurriculum) {
	// 		console.error('Curriculum container not found');
	// 		return;
	// 	}
	//
	// 	Swal.fire({ title: lpCourseAiModalData.i18n.applied, icon: 'success', timer: 1000, showConfirmButton: false });
	//
	// 	lpEditCurriculumShare.setVariables({
	// 		elEditCurriculum,
	// 		courseId: dataSend.args.course_id,
	// 		elCurriculumSections: elEditCurriculum.querySelector(lpEditCurriculumShare.className.elCurriculumSections),
	// 	});
	// 	sectionEdit.init();
	//
	// 	try {
	// 		const curriculumData = JSON.parse(dataLessonsStr);
	//
	// 		for (const sectionData of (curriculumData.sections || [])) {
	// 			// const sectionEl = await sectionEdit.addSectionFromData(sectionData.section_title);
	// 			//
	// 			// if (sectionEl && sectionData.lessons?.length > 0) {
	// 			// 	sectionData.lessons.forEach(lesson => {
	// 			// 		sectionEdit.addLessonFromData(sectionEl, lesson.lesson_title);
	// 			// 	});
	// 			// }
	// 		}
	//
	// 	} catch (e) {
	// 		console.error('JSON parse error:', e);
	// 	}
	// }
	//
	// // Generic Content Generation using AJAX
	generateContent(type, formData, resultsCallback, applyCallback) {
		const {i18n, nonce} = lpCourseAiModalData;

		const isImage = type === 'course-feature-image';
		const ajaxAction = isImage ? 'create_course_feature_image' : 'generate_text';

		const data = new FormData();
		data.append('action', 'lp_ajax');
		data.append('lp-load-ajax', ajaxAction);
		data.append('nonce', nonce);
		data.append('type', type);

		for (const key in formData) {
			if (Array.isArray(formData[key])) {
				formData[key].forEach(value => data.append(`${key}[]`, value));
			} else {
				data.append(key, formData[key]);
			}
		}

		return fetch(lpCourseAiModalData.ajaxUrl, {
			method: 'POST',
			body: data,
		})
			.then(response => response.json())
			.then(res => {
				if (res.success) {
					const responseData = res.data.data;
					const callbackData = {
						prompt: responseData.prompt,
						content: isImage ? responseData.urls : responseData.content,
						extraData: isImage ? null : responseData.sections
					};
					resultsCallback.call(this, type, callbackData, applyCallback);
				} else {
					Swal.fire(i18n.errorOccurred, res.data.message || 'Unknown error', 'error');
				}
			})
			.catch(err => {
				Swal.fire(i18n.errorOccurred, err.message, 'error');
			});
	}

	// Generic Popups for Results
	showResultPopup(type, callbackData, applyCallback) {
		const {prompt, content, extraData} = callbackData;
		const {i18n} = lpCourseAiModalData;

		const promptTextarea = document.querySelector('#lp-ai-output-prompt-desc');

		if (promptTextarea && prompt) {
			promptTextarea.value = prompt;

			// add btn re-generate
			const outputPromptElm = document.querySelector('#lp-ai-output-prompt');
			if (!document.querySelector('#reGenerateBtn')) {
				const reBtn = document.createElement('button');
				reBtn.textContent = 'Re-generate';
				reBtn.id = 'reGenerateBtn';
				reBtn.classList.add('generate-button');
				reBtn.addEventListener('click', () => {
					const popup = Swal.getPopup();
					const formData = {
						outputs: parseInt(popup.querySelector('#lp-ai-output-count').textContent, 10),
						prompt: promptTextarea.value
					};
					this.generateContent(type, formData, this.showResultPopup, applyCallback)
						.catch(err => {
							Swal.showValidationMessage(`Request failed: ${err.message}`);
						});
				});
				outputPromptElm.appendChild(reBtn)
			}
		}

		let resultsHtml = content.map((item, index) => `
        <div class="output-item output-suggestion">
            <textarea>${item}</textarea>
            <div class="output-actions">
                <button type="button" class="action-button copy-button">${i18n.copy}</button>
                <button type="button" class="action-button apply-button" data-index="${index}">${i18n.apply}</button>
            </div>
        </div>
    `).join('');

		const suggestionContainer = document.querySelector('#lp-ai-output-suggestion');
		if (suggestionContainer) {
			suggestionContainer.innerHTML = resultsHtml;
			suggestionContainer.querySelectorAll('.apply-button').forEach(button => {
				button.addEventListener('click', (e) => {
					const target = e.currentTarget;
					const index = target.getAttribute('data-index');
					const text = target.closest('.output-item').querySelector('textarea').value;
					const applyData = {text, index, extraData};
					applyCallback.call(this, applyData);
				});
			});

			suggestionContainer.querySelectorAll('.copy-button').forEach(button => {
				button.addEventListener('click', e => {
					const textToCopy = e.currentTarget.closest('.output-item').querySelector('textarea').value;
					navigator.clipboard.writeText(textToCopy).then(() => {
						Swal.fire({
							title: lpCourseAiModalData.i18n.copied,
							icon: 'success',
							timer: 1000,
							showConfirmButton: false
						});
					});
				});
			});

		}

		const confirmButton = Swal.getConfirmButton();
		if (confirmButton) {
			confirmButton.disabled = false;
		}
	}

	// showImageResultsPopup(urls, applyCallback) {
	// 	const { i18n } = lpCourseAiModalData;
	// 	let resultsHtml = urls.map(url => `
	// 		<div class="output-placeholder" style="background-image: url('${url}');">
	// 			<div class="output-actions">
	// 				<button class="action-button" data-apply-url="${url}">Apply</button>
	// 			</div>
	// 		</div>
	// 	`).join('');
	//
	// 	Swal.fire({
	// 		title: i18n.results,
	// 		html: `<div class="lp-ai-image-results-wrapper">${resultsHtml}</div>`,
	// 		showConfirmButton: false,
	// 		showCloseButton: true,
	// 		width: '800px',
	// 		customClass: { popup: 'lp-ai-sweetalert' },
	// 		didOpen: (popup) => {
	// 			popup.addEventListener('click', (e) => {
	// 				if (e.target.matches('[data-apply-url]')) {
	// 					applyCallback.call(this, e.target.getAttribute('data-apply-url'));
	// 				}
	// 			});
	// 		}
	// 	});
	// }

	showCurriculumResultsPopup(contentArray, applyCallback, sectionsData) {
		this.showResultsPopup(contentArray, ({index}) => {
			applyCallback.call(this, {
				dataLessonsStr: sectionsData[index]
			});
		});
	}

	// Main Init and Events
	init() {
		this.tomSelect()
		this.addAIBtns();
		this.events();
	}

	tomSelect = () => {
		const tomSelectNodes = document.querySelectorAll('select.lp-tom-select');

		tomSelectNodes.forEach((tomSelectNode) => {

			if (tomSelectNode.tomselect) {
				tomSelectNode.tomselect.destroy();
			}

			const settings = {
				maxOptions: null,
				plugins: tomSelectNode.multiple
					? ['no_backspace_delete', 'remove_button', 'dropdown_input', 'change_listener']
					: ['dropdown_input'],
			};

			new TomSelect(tomSelectNode, settings);
		});
	};

	addAIBtns() {
		const __ = wp.i18n.__ || ((text) => text);

		const titleWrap = document.querySelector('body.post-type-lp_course #titlewrap');
		if (titleWrap && !document.getElementById('lp-edit-ai-course-title')) {
			titleWrap.insertAdjacentHTML('afterend', `<button type="button" class="button" id="lp-edit-ai-course-title">${__('Edit with AI', 'learnpress')}</button>`);
		}

		const btnAddMedia = document.querySelector('body.post-type-lp_course #insert-media-button');
		if (btnAddMedia && !document.getElementById('lp-edit-ai-course-description')) {
			btnAddMedia.insertAdjacentHTML('afterend', `<button type="button" class="button" id="lp-edit-ai-course-description">${__('Edit with AI', 'learnpress')}</button>`);
		}

		const btnAddFeatureImage = document.querySelector('body.post-type-lp_course #set-post-thumbnail');
		if (btnAddFeatureImage && !document.getElementById('lp-edit-ai-course-feature-image')) {
			btnAddFeatureImage.insertAdjacentHTML('afterend', `<button type="button" class="button" id="lp-edit-ai-course-feature-image">${__('Edit with AI', 'learnpress')}</button>`);
		}

		const editorBox = document.querySelector('#course-editor');
		if (editorBox) {
			setTimeout(() => {
				if (!editorBox.querySelector('#lp-edit-ai-course-curriculum')) {
					const handleActions = editorBox.querySelector('.handle-actions');
					if (handleActions) {
						const btn = document.createElement('button');
						btn.type = 'button';
						btn.className = 'button';
						btn.id = 'lp-edit-ai-course-curriculum';
						btn.textContent = __('Edit with AI', 'learnpress');
						handleActions.prepend(btn);
					}
				}
			}, 1500);
		}
	}

	events() {
		document.addEventListener('click', (e) => {
			const target = e.target;
			const actions = {
				'lp-edit-ai-course-title': this.showPopupCreateTitle,
				'lp-edit-ai-course-description': this.showPopupCreateDescription,
				// 'lp-edit-ai-course-feature-image': this.showPopupFeatureImage,
				//'lp-edit-ai-course-curriculum': this.showPopupCreateCurriculum,
			};
			if (actions[target.id]) {
				e.preventDefault();
				actions[target.id].call(this);
			}
		});
	}
}

document.addEventListener('DOMContentLoaded', () => {
	if (typeof lpCourseAiModalData !== 'undefined') {
		new CourseAI();
	}
});
