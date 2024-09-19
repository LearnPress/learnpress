let modal;
const {__} = wp.i18n;

const description = () => {
	modal = document.querySelector('#lp-ai-course-des-modal');

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
	document.querySelector('body.post-type-lp_course #wp-content-media-buttons').insertAdjacentHTML('beforeend', `
	<button type="button" class="button" id="lp-edit-ai-course-des">` + __('Edit with AI', 'learnpress') + `</button>`);
};

const copyText = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;

		if (!target.classList.contains('copy')) {
			return;
		}

		const modal = target.closest('#lp-ai-course-des-modal');

		if (!modal) {
			return;
		}

		const descriptionItem = target.closest('.course-des-item');

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

		const modal = target.closest('#lp-ai-course-des-modal');

		if (!modal) {
			return;
		}

		const descriptionItem = target.closest('.course-des-item');

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

		if (target.id !== 'lp-edit-ai-course-des') {
			return;
		}

		modal.classList.add('active');
		target.disabled = false;
	});
};

const closeModal = () => {
	let isMouseDownOnTarget = false

	const handleClose = () => {
		const openModalBtn = document.querySelector('#lp-edit-ai-course-des');

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
			if (target.classList.contains('close-btn') && target.closest('#lp-ai-course-des-modal')) {
				handleClose();
			}

			if (!target.classList.contains('modal-content') && !target.closest('.modal-content')) {
				handleClose();
			}
		}
		isMouseDownOnTarget = false;
	});

	const changeMouseDownOnTarget = (target, value) => {
		if (target.id === 'lp-edit-ai-course-des') {
			return;
		}

		if (target.classList.contains('close-btn') && target.closest('#lp-ai-course-des-modal')) {
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
		if (!['lp-generate-course-des-btn', 'lp-re-generate-course-des'].includes(target.getAttribute('id'))) {
			return;
		}

		const modal = target.closest('#lp-ai-course-des-modal');

		if (!modal) {
			return;
		}

		const togglePromptBtnNode = modal.querySelector('.toggle-prompt');
		const promptOutputNode = modal.querySelector('.prompt-output');

		const desOutputNode = modal.querySelector('.course-des-output');

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

		const topicNode = contentNode.querySelector('#ai-course-des-field-topic');
		// const goalNode = contentNode.querySelector( '#ai-course-des-field-goal' );
		const audienceNode = contentNode.querySelector('#ai-course-des-field-audience');
		const toneNode = contentNode.querySelector('#ai-course-des-field-tone');
		const paragraphNumberNode = contentNode.querySelector('#ai-course-des-field-paragraph-number');
		const langNode = contentNode.querySelector('#ai-course-des-field-language');
		const outputsNode = contentNode.querySelector('#ai-course-des-field-outputs');
		const courseTitleNode = document.querySelector('#titlewrap input');

		const data = {
			type: 'course-description',
			topic: topicNode.value,
			// goal: goalNode.value,
			audience: Array.from(audienceNode.selectedOptions).map((option) => option.value),
			tone: Array.from(toneNode.selectedOptions).map((option) => option.value),
			paragraph_number: paragraphNumberNode.value,
			lang: Array.from(langNode.selectedOptions).map((option) => option.value),
			outputs: outputsNode.value,
			title: courseTitleNode ? courseTitleNode.value: '',
		};

		if (target.getAttribute('id') === 'lp-re-generate-course-des') {
			data.prompt = promptTextArea ? promptTextArea.value : '';
		}
		wp.apiFetch({
			path: '/lp/v1/open-ai/generate-text', method: 'POST', data,
		}).then((res) => {
			if (res.data.prompt) {
				promptOutputNode.innerHTML = res.data.prompt.replace(/\\n/g, '\n');
			}

			if (res.data.content) {
				let des = '';
				[...res.data.content].map((content) => {
					content = convertNewlinesToParagraphs(content);
					des += `
					<div class="course-des-item">
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
