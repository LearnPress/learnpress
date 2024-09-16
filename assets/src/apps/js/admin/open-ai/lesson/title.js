let modal;
const {__} = wp.i18n;

const title = () => {
	modal = document.querySelector('#lp-ai-lesson-title-modal');

	if (!modal) {
		return;
	}

	addTitleBtn();
	openModal();
	closeModal();
	generate();
	togglePrompt();
	copyText();
	applyText();
};

const addTitleBtn = () => {
	document.querySelector('body.post-type-lp_lesson #titlewrap').insertAdjacentHTML('afterend', `
	<button type="button" class="button" id="lp-edit-ai-lesson-title">` + __('Edit with AI', 'learnpress') + `</button>`);
};

const copyText = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;

		if (!target.classList.contains('copy')) {
			return;
		}

		const modal = target.closest('#lp-ai-lesson-title-modal');

		if (!modal) {
			return;
		}

		const titleItem = target.closest('.lesson-title-item');

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

		const modal = target.closest('#lp-ai-lesson-title-modal');

		if (!modal) {
			return;
		}

		const titleItem = target.closest('.lesson-title-item');

		if (!titleItem) {
			return;
		}

		const titleNode = document.querySelector('#post-body-content #title');
		if (!titleNode) {
			return;
		}

		let text = titleItem.querySelector('div.ai-result').innerHTML;
		text = text.trim();

		titleNode.value = text;
		target.innerHTML = __('Applied', 'learnpress');
		target.disabled = true;
		setTimeout(() => {
			target.innerHTML = __('Apply', 'learnpress');
			target.disabled = false;
		}, 1000);
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

const togglePrompt = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;
		if (!target.classList.contains('toggle-prompt')) {
			return;
		}

		const modal = target.closest('.ai-modal');
		if (!modal) {
			return;
		}

		const isActive = target.classList.contains('active');
		target.classList.toggle('active');

		const promptOutput = modal.querySelector('.prompt-output');

		if (isActive) {
			target.innerHTML = __('Display prompt', 'learnpress');
			target.classList.remove('active');
			promptOutput.classList.remove('active');
		} else {
			target.innerHTML = __('Hide prompt', 'learnpress');
			target.classList.add('active');
			promptOutput.classList.add('active');
		}
	});
};

const openModal = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;

		if (target.id !== 'lp-edit-ai-lesson-title') {
			return;
		}

		modal.classList.add('active');
		target.disabled = true;
	});
};

const closeModal = () => {
	let isMouseDownOnTarget = false

	const handleClose = () => {
		const openModalBtn = document.querySelector('#lp-edit-ai-lesson-title');

		modal.classList.remove('active');
		openModalBtn.disabled = false;
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
			if (target.classList.contains('close-btn') && target.closest('#lp-ai-lesson-title-modal')) {
				handleClose();
			}

			if (!target.classList.contains('modal-content') && !target.closest('.modal-content')) {
				handleClose();
			}
		}
		isMouseDownOnTarget = false;
	});

	const changeMouseDownOnTarget = (target, value) => {
		if (target.id === 'lp-edit-ai-lesson-title') {
			return;
		}

		if (target.classList.contains('close-btn') && target.closest('#lp-ai-lesson-title-modal')) {
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
		if (!['lp-generate-lesson-title-btn', 'lp-re-generate-lesson-title'].includes(target.getAttribute('id'))) {
			return;
		}

		const modal = target.closest('#lp-ai-lesson-title-modal');

		if (!modal) {
			return;
		}

		const togglePromptBtnNode = modal.querySelector('.toggle-prompt');
		const promptOutputNode = modal.querySelector('.prompt-output');
		const titleOutputNode = modal.querySelector('.lesson-title-output');

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

		const topicNode = contentNode.querySelector('#ai-lesson-title-field-topic');
		const goalNode = contentNode.querySelector('#ai-lesson-title-field-goal');
		const audienceNode = contentNode.querySelector('#ai-lesson-title-field-audience');
		const toneNode = contentNode.querySelector('#ai-lesson-title-field-tone');
		const langNode = contentNode.querySelector('#ai-lesson-title-field-language');
		const outputsNode = contentNode.querySelector('#ai-lesson-title-field-outputs');


		let data = {
			type: 'lesson-title',
			topic: topicNode.value,
			goal: goalNode.value,
			audience: Array.from(audienceNode.selectedOptions).map((option) => option.value),
			tone: Array.from(toneNode.selectedOptions).map((option) => option.value),
			lang: Array.from(langNode.selectedOptions).map((option) => option.value),
			outputs: outputsNode.value,
		};

		if (target.getAttribute('id') === 'lp-re-generate-lesson-title') {
			data.prompt = promptTextArea ? promptTextArea.value : '';
		}

		wp.apiFetch({
			path: '/lp/v1/open-ai/generate-text', method: 'POST', data,
		}).then((res) => {
			if (res.data.prompt) {
				promptOutputNode.innerHTML = res.data.prompt.replace(/\\n/g, '\n');
			}

			if (res.data.content) {
				let titleContent = '';
				[...res.data.content].map((content) => {
					titleContent += `
					<div class="lesson-title-item">
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

export default title;
