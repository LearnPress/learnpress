let modal;
const {__} = wp.i18n;

const curriculum = () => {
	modal = document.querySelector('#lp-ai-curriculum-modal');
	if (!modal) {
		return;
	}

	openModal();
	closeModal();
	generate();
	applySection();
};

const applySection = () => {

};


const openModal = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;

		if (target.id !== 'lp-edit-ai-curriculum') {
			return;
		}

		modal.classList.add('active');
		target.disabled = false;
	});
};

const closeModal = () => {
	let isMouseDownOnTarget = false

	const handleClose = () => {
		const openModalBtn = document.querySelector('#lp-edit-ai-curriculum');

		modal.classList.remove('active');

		if (openModalBtn) {
			openModalBtn.disabled = false;
		}
	};

	document.addEventListener('mousedown', function (event) {
		const target = event.target;

		if (!(target instanceof Element)) {
			return;
		}

		changeMouseDownOnTarget(target, true);
	});

	document.addEventListener('mouseleave', function (event) {
		const target = event.target;
		if (!(target instanceof Element)) {
			return;
		}
		changeMouseDownOnTarget(target, false);
	});

	document.addEventListener('mouseup', function (event) {
		const target = event.target;
		if (!(target instanceof Element)) {
			return;
		}
		if (isMouseDownOnTarget) {
			if (target.classList.contains('close-btn') && target.closest('#lp-ai-curriculum-modal')) {
				handleClose();
			}

			if (!target.classList.contains('modal-content') && !target.closest('.modal-content')) {
				handleClose();
			}
		}
		isMouseDownOnTarget = false;
	});

	const changeMouseDownOnTarget = (target, value) => {
		if (target.id === 'lp-edit-ai-curriculum') {
			return;
		}

		if (target.classList.contains('close-btn') && target.closest('#lp-ai-curriculum-modal')) {
			isMouseDownOnTarget = value;
		}

		if (!target.classList.contains('modal-content') && !target.closest('.modal-content')) {
			isMouseDownOnTarget = value
		}
	}
};

const generate = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;

		if (!['lp-generate-curriculum-btn', 'lp-re-generate-curriculum'].includes(target.getAttribute('id'))) {
			return;
		}

		const modal = target.closest('#lp-ai-curriculum-modal');

		if (!modal) {
			return;
		}

		const togglePromptBtnNode = modal.querySelector('.toggle-prompt');
		const promptOutputNode = modal.querySelector('.prompt-output');
		const curriculumOutputNode = modal.querySelector('.curriculum-output');

		const contentNode = modal.querySelector('.content');
		const promptTextArea = promptOutputNode.querySelector('textarea');

		//Before generate
		(() => {
			target.disabled = true;
			togglePromptBtnNode.classList.remove('active', 'display');
			togglePromptBtnNode.innerHTML = __('Display prompt', 'learnpress');
			promptOutputNode.classList.remove('active');
			curriculumOutputNode.innerHTML = '';
			contentNode.style.opacity = 0.6;
		})();

		const sectionNumberNode = contentNode.querySelector('#ai-curriculum-field-section-numbers');
		const lessPerSectionNode = contentNode.querySelector('#ai-curriculum-field-less-per-section');
		const levelNode = contentNode.querySelector('#ai-curriculum-field-level');
		const topicNode = contentNode.querySelector('#ai-curriculum-field-topic');
		const languageNode = contentNode.querySelector('#ai-curriculum-field-language');
		const outputsNode = contentNode.querySelector('#ai-curriculum-field-outputs');
		const courseTitleNode = document.querySelector('#titlewrap input');
		const editor = tinyMCE.get('content');

		let data = {
			type: 'course-curriculum',
			section_number: sectionNumberNode.value,
			less_per_section: lessPerSectionNode.value,
			level: levelNode.value,
			topic: topicNode.value,
			lang: languageNode.value,
			outputs: outputsNode ? outputsNode.value : 1,
			title: courseTitleNode ? courseTitleNode.value : '',
			data_return: 'json',
		};

		if (!!editor) {
			data.description = editor.getContent()
		}

		if (target.getAttribute('id') === 'lp-re-generate-curriculum') {
			data.prompt = promptTextArea ? promptTextArea.value : '';
		}

		wp.apiFetch({
			path: '/lp/v1/open-ai/generate-text', method: 'POST', data,
		}).then((res) => {
			if (res.data.prompt && !data.prompt ) {
				promptOutputNode.innerHTML = res.data.prompt.replace(/\\n/g, '\n');
			}

			if (res.data.content) {
				let curriculumContent = '';
				const curriculums = res.data.content;

				for (let i = 0; i < curriculums.length; i++) { // loop curriculum
					let sectionContent = '';
					const curriculum = curriculums[i];

					if (!curriculum) {
						continue;
					}

					const sections = curriculum?.sections || [];
					for (let j = 0; j < sections.length; j++) {
						const section = sections[j];
						if (!section) {
							continue;
						}

						const lessons = section?.lessons || [];
						let lessonTitle = '';

						lessons.map(lesson => {
							lessonTitle += `
								<li class="lesson-title">${lesson.lesson_title}</li>
							`;
						});

						sectionContent += `
						<div class="section">
							<div class="section-title">${section.section_title}</div>
							<div class="section-content">
								<ul class="lesson">
									${lessonTitle}
								</ul>
								<button class="apply-section button">` + __('Apply section', 'learnpress') + `</button>
							</div>
						</div>
						`;
					}

					curriculumContent += `
					<div class="course-curriculum-item">
						<div class="ai-result">
							${sectionContent}
						</div>
						<div class="action">
							<button class="apply-curriculum button">` + __('Apply curriculum', 'learnpress') + `</button>
						</div>
					</div>`;
				}

				curriculumOutputNode.innerHTML = curriculumContent;
			}

			if (res.msg && res.status === 'error') {
				curriculumOutputNode.innerHTML = `<div class="error"> ${res.msg} </div>`;
			}
		}).catch((err) => {
			console.log(err);
		}).finally(() => {
			//After generate
			(() => {
				target.disabled = false;
				togglePromptBtnNode.classList.add('display');
				contentNode.style.opacity = 1;
			})();
		});
	});
};

export default curriculum;
