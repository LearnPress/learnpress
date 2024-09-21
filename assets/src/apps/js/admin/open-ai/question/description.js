let modal;
const {__} = wp.i18n;

const description = () => {
	modal = document.querySelector('#lp-ai-question-des-modal');

	if (!modal) {
		return;
	}
	addDesBtn();
	openModal();
	closeModal();
	generate();
	copyText();
	applyText();
};

const addDesBtn = () => {
	document.querySelector('body.post-type-lp_question #wp-content-media-buttons').insertAdjacentHTML('beforeend', `
	<button type="button" class="button" id="lp-edit-ai-question-des">` + __('Edit with AI', 'learnpress') + `</button>`);
};

const copyText = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;

		if (!target.classList.contains('copy')) {
			return;
		}

		const modal = target.closest('#lp-ai-question-des-modal');

		if (!modal) {
			return;
		}

		const descriptionItem = target.closest('.question-des-item');

		if (!descriptionItem) {
			return;
		}

		let text = descriptionItem.querySelector('div.ai-result').innerHTML;
		text = text.trim();
		text = convertParagraphsToNewlines(text);
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

		const modal = target.closest('#lp-ai-question-des-modal');

		if (!modal) {
			return;
		}

		const descriptionItem = target.closest('.question-des-item');

		if (!descriptionItem) {
			return;
		}

		const editor = tinyMCE.get('content');

		if (!editor) {
			return;
		}

		let text = descriptionItem.querySelector('div.ai-result').innerHTML;
		text = text.trim();

		editor.setContent(convertNewlinesToParagraphs(text));
		target.innerHTML = __('Applied', 'learnpress');
		target.disabled = true;
		setTimeout(() => {
			target.innerHTML = __('Apply', 'learnpress');
			target.disabled = false;
		}, 1000);
	});
};

const convertNewlinesToParagraphs = (text) => {
	let result = text
		.replace(/\n+/g, '</p><p>')
		.trim();

	if (result) {
		result = '<p>' + result + '</p>';
	}

	return result;
};

const convertParagraphsToNewlines = (htmlString) => {
	return htmlString
		.replace(/<\/p>/g, '\n\n')
		.replace(/<p>/g, '')
		.trim();
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

		if (target.id !== 'lp-edit-ai-question-des') {
			return;
		}

		modal.classList.add('active');
		target.disabled = false;
	});
};

const closeModal = () => {
	let isMouseDownOnTarget = false

	const handleClose = () => {
		const openModalBtn = document.querySelector('#lp-edit-ai-question-des');

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
			if (target.classList.contains('close-btn') && target.closest('#lp-ai-question-des-modal')) {
				handleClose();
			}

			if (!target.classList.contains('modal-content') && !target.closest('.modal-content')) {
				handleClose();
			}
		}
		isMouseDownOnTarget = false;
	});

	const changeMouseDownOnTarget = (target, value) => {
		if (target.id === 'lp-edit-ai-question-des') {
			return;
		}

		if (target.classList.contains('close-btn') && target.closest('#lp-ai-question-des-modal')) {
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
		if (!['lp-generate-question-des-btn', 'lp-re-generate-question-des'].includes(target.getAttribute('id'))) {
			return;
		}

		const modal = target.closest('#lp-ai-question-des-modal');

		if (!modal) {
			return;
		}

		const togglePromptBtnNode = modal.querySelector('.toggle-prompt');
		const promptOutputNode = modal.querySelector('.prompt-output');

		const desOutputNode = modal.querySelector('.question-des-output');

		const contentNode = modal.querySelector('.content');
		const promptTextArea = promptOutputNode.querySelector('textarea');

		//Before generate
		(() => {
			target.disabled = true;
			togglePromptBtnNode.classList.remove('active', 'display');
			togglePromptBtnNode.innerHTML = __('Display prompt', 'learnpress');
			promptOutputNode.classList.remove('active');
			desOutputNode.innerHTML = '';
			contentNode.style.opacity = 0.6;
		})();

		const topicNode = contentNode.querySelector('#ai-question-des-field-topic');
		// const goalNode = contentNode.querySelector( '#ai-question-des-field-goal' );
		const audienceNode = contentNode.querySelector('#ai-question-des-field-audience');
		const toneNode = contentNode.querySelector('#ai-question-des-field-tone');
		const langNode = contentNode.querySelector('#ai-question-des-field-language');
		const outputsNode = contentNode.querySelector('#ai-question-des-field-outputs');

		const data = {
			type: 'question-description',
			topic: topicNode.value,
			// goal: goalNode.value,
			audience: Array.from(audienceNode.selectedOptions).map((option) => option.value),
			tone: Array.from(toneNode.selectedOptions).map((option) => option.value),
			lang: Array.from(langNode.selectedOptions).map((option) => option.value),
			outputs: outputsNode.value,
		};

		if (target.getAttribute('id') === 'lp-re-generate-question-des') {
			data.prompt = promptTextArea ? promptTextArea.value : '';
		}
		wp.apiFetch({
			path: '/lp/v1/open-ai/generate-text', method: 'POST', data,
		}).then((res) => {
			if (res.data.prompt && !data.prompt ) {
				promptOutputNode.innerHTML = res.data.prompt.replace(/\\n/g, '\n');
			}

			if (res.data.content) {
				let des = '';
				[...res.data.content].map((content) => {
					content = convertNewlinesToParagraphs(content);
					des += `
					<div class="question-des-item">
						<div class="ai-result">
							${content}
						</div>
						<div class="action">
							<button class="copy button">` + __('Copy', 'learnpress') + `</button>
							<button class="apply button">` + __('Apply', 'learnpress') + `</button>
						</div>
					</div>`;
				});
				desOutputNode.innerHTML = des;
			}

			if (res.msg && res.status === 'error') {
				desOutputNode.innerHTML = `<div class="error"> ${res.msg} </div>`;
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

export default description;
