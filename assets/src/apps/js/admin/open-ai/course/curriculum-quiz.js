let modal;
const {__} = wp.i18n;
let activeOpenModalBtn;
let sectionId = null;
let sectionOrder = null;

const curriculumQuiz = () => {
	modal = document.querySelector('#lp-ai-curriculum-quiz-modal');

	if (!modal) {
		return;
	}

	openModal();
	closeModal();
	generate();
	copyText();
	applyText();
};

const copyText = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;

		if (!target.classList.contains('copy')) {
			return;
		}

		const modal = target.closest('#lp-ai-curriculum-quiz-modal');

		if (!modal) {
			return;
		}

		const titleItem = target.closest('.curriculum-quiz-item');

		if (!titleItem) {
			return;
		}

		let text = titleItem.querySelector('div.ai-result').innerHTML;
		text = text.trim();

		if (window.isSecureContext && navigator.clipboard) {
			target.disabled = true;
			navigator.clipboard.writeText(text)
				.then(() => {
					target.innerHTML = __('Copied', 'learnpress');
					setTimeout(() => {
						target.innerHTML = __('Copy', 'learnpress');
						target.disabled = false;
					}, 1000);
				})
				.catch((err) => {
					console.error(__('Failed to copy text: ', 'learnpress'), err);
				});
		} else {
			unsecuredCopyToClipboard(target, text);
		}
	});
};

const applyText = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;

		if (!target.classList.contains('apply')) {
			return;
		}

		const modal = target.closest('#lp-ai-curriculum-quiz-modal');

		if (!modal) {
			return;
		}

		const quizItem = target.closest('.curriculum-quiz-item');

		if (!quizItem) {
			return;
		}

		const data = {
			course_id: modal.getAttribute('data-course-id'),
			section_id:sectionId,
			section_order:sectionOrder,
			title:quizItem.querySelector('.ai-result').innerHTML,
		};

		target.disabled = true;
		wp.apiFetch({
			path: '/lp/v1/open-ai/curriculum-quiz', method: 'POST', data,
		}).then((res) => {
			if (res.status === 'error' && res.msg) {
				// eslint-disable-next-line no-alert
				window.alert(res.msg);
			}

			if (res.status === 'success' && res.msg) {
				// eslint-disable-next-line no-alert
				window.alert(res.msg);
				window.location.reload();
			}

		}).catch((err) => {
			console.log(err);
		}).finally(() => {
			//After generate
			(() => {
				target.disabled = false;
			})();
		});
	});
};

const unsecuredCopyToClipboard = (target, text) => {
	const textArea = document.createElement('textarea');
	textArea.value = text;
	document.body.appendChild(textArea);
	textArea.focus();
	textArea.select();
	try {
		document.execCommand('copy');
		target.innerHTML = __('Copied', 'learnpress');
		target.disabled = true;
		setTimeout(() => {
			target.innerHTML = __('Copy', 'learnpress');
			target.disabled = false;
		}, 1000);
	} catch (err) {
		console.error('Unable to copy to clipboard', err);
	}
	document.body.removeChild(textArea);
};


const openModal = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;
		if (!target.classList.contains('lp-edit-ai-curriculum-quiz')) {
			return;
		}

		modal.classList.add('active');
		target.disabled = true;
		activeOpenModalBtn = target;
		const section  = target.closest('.section');
		sectionId = section.getAttribute('data-section-id');
		sectionOrder = section.getAttribute('data-section-order');
		document.querySelector('body').style.overflow = 'hidden';
	});
};

const closeModal = () => {
	let isMouseDownOnTarget = false

	const handleClose = () => {
		// const openModalBtn = document.querySelector('.lp-edit-ai-curriculum-quiz.active');

		modal.classList.remove('active');
		if(activeOpenModalBtn){
			activeOpenModalBtn.disabled = false;
		}

		document.querySelector('body').style.overflow = 'visible';
	};

	document.addEventListener('click', function(event) {
		const target = event.target;
		if (target.classList.contains('close-btn') && target.closest('#lp-ai-curriculum-quiz-modal')) {
			handleClose();
		}

		if(target.classList.contains('ai-overlay')){
			handleClose();
		}
	});
};

const generate = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;
		if (!['lp-generate-curriculum-quiz-btn', 'lp-re-generate-curriculum-quiz'].includes(target.getAttribute('id'))) {
			return;
		}

		const modal = target.closest('#lp-ai-curriculum-quiz-modal');

		if (!modal) {
			return;
		}

		const togglePromptBtnNode = modal.querySelector('.toggle-prompt');
		const promptOutputNode = modal.querySelector('.prompt-output');
		const titleOutputNode = modal.querySelector('.curriculum-quiz-output');

		const contentNode = modal.querySelector('.content');
		const promptTextArea = promptOutputNode.querySelector('textarea');

		//Before generate
		(() => {
			target.disabled = true;
			togglePromptBtnNode.classList.remove('active', 'display');
			togglePromptBtnNode.innerHTML = __('Display prompt', 'learnpress');
			promptOutputNode.classList.remove('active');
			titleOutputNode.innerHTML = '';
			contentNode.style.opacity = 0.6;
		})();

		const topicNode = contentNode.querySelector('#ai-curriculum-quiz-field-topic');
		const goalNode = contentNode.querySelector('#ai-curriculum-quiz-field-goal');
		const audienceNode = contentNode.querySelector('#ai-curriculum-quiz-field-audience');
		const toneNode = contentNode.querySelector('#ai-curriculum-quiz-field-tone');
		const langNode = contentNode.querySelector('#ai-curriculum-quiz-field-language');
		const outputsNode = contentNode.querySelector('#ai-curriculum-quiz-field-outputs');


		let data = {
			type: 'curriculum-quiz',
			topic: topicNode.value,
			goal: goalNode.value,
			audience: Array.from(audienceNode.selectedOptions).map((option) => option.value),
			tone: Array.from(toneNode.selectedOptions).map((option) => option.value),
			lang: Array.from(langNode.selectedOptions).map((option) => option.value),
			outputs: outputsNode.value,
		};

		if (target.getAttribute('id') === 'lp-re-generate-curriculum-quiz') {
			data.prompt = promptTextArea ? promptTextArea.value : '';
		}

		wp.apiFetch({
			path: '/lp/v1/open-ai/generate-text', method: 'POST', data,
		}).then((res) => {
			if (res.data.prompt && !data.prompt ) {
				promptOutputNode.innerHTML = res.data.prompt.replace(/\\n/g, '\n');
			}

			if (res.data.content) {
				let titleContent = '';
				[...res.data.content].map((content) => {
					titleContent += `
					<div class="curriculum-quiz-item">
						<div class="ai-result">
							${content}
						</div>
						<div class="action">
							<button class="copy button">` + __('Copy', 'learnpress') + `</button>
							<button class="apply button">` + __('Apply', 'learnpress') + `</button>
						</div>
					</div>`;
				});
				titleOutputNode.innerHTML = titleContent;
			}

			if (res.msg && res.status === 'error') {
				titleOutputNode.innerHTML = `<div class="error"> ${res.msg} </div>`;
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

export default curriculumQuiz;
