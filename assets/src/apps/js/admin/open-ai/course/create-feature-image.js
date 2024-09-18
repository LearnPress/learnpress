let modal;
const {__} = wp.i18n;

const createFeaturImage = () => {
	modal = document.querySelector('#lp-ai-course-create-fi-modal');
	if (!modal) {
		return;
	}

	openModal();
	closeModal();
	generate();
	saveImage();
	setFeatureImage();
};

const saveImage = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;

		if (!target.classList.contains('ai-save-image')) {
			return;
		}

		if (!target.closest('.course-create-fi-item')) {
			return;
		}

		const actionNode = target.closest('.action');
		target.disabled = true;
		const data = {
			image_data: target.closest('.course-create-fi-item').querySelector('.ai-result img').src
		};

		wp.apiFetch({
			path: '/lp/v1/open-ai/save-feature-image', method: 'POST', data,
		}).then((res) => {
			if (res.status === 'error' && res.msg) {
				// eslint-disable-next-line no-alert
				window.alert(res.msg);
			}

			target.remove();
			if (res.data.id) {
				actionNode.innerHTML = `<button class="ai-set-feature-image button">` + __('Set featured image', 'learnpress') + `</button>`
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

const setFeatureImage = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;

		if (!target.classList.contains('ai-set-feature-image')) {
			return;
		}

		document.querySelector('#set-post-thumbnail').click();
	});
}

const openModal = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;

		if (target.id !== 'lp-edit-ai-course-create-fi') {
			return;
		}

		modal.classList.add('active');
		target.disabled = false;
	});
};

const closeModal = () => {
	let isMouseDownOnTarget = false

	const handleClose = () => {
		const openModalBtn = document.querySelector('#lp-edit-ai-course-create-fi');

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
			if (target.classList.contains('close-btn') && target.closest('#lp-ai-course-create-fi-modal')) {
				handleClose();
			}

			if (!target.classList.contains('modal-content') && !target.closest('.modal-content')) {
				handleClose();
			}
		}
		isMouseDownOnTarget = false;
	});

	const changeMouseDownOnTarget = (target, value) => {
		if (target.id === 'lp-edit-ai-course-create-fi') {
			return;
		}

		if (target.classList.contains('close-btn') && target.closest('#lp-ai-course-create-fi-modal')) {
			isMouseDownOnTarget = value;
		}

		if (!target.classList.contains('modal-content') && !target.closest('.modal-content')) {
			isMouseDownOnTarget = value
		}
	}
};

const generate = () => {
	document.addEventListener('mousedown', function (event) {
		const target = event.target;

		if (!['lp-generate-course-create-fi-btn', 'lp-re-generate-course-create-fi'].includes(target.getAttribute('id'))) {
			return;
		}

		const modal = target.closest('#lp-ai-course-create-fi-modal');

		if (!modal) {
			return;
		}

		const togglePromptBtnNode = modal.querySelector('.toggle-prompt');
		const promptOutputNode = modal.querySelector('.prompt-output');
		const courseFiOutputNode = modal.querySelector('.course-create-fi-output');

		const contentNode = modal.querySelector('.content');
		const promptTextArea = promptOutputNode.querySelector('textarea');

		//Before generate
		(() => {
			target.disabled = true;
			togglePromptBtnNode.classList.remove('active', 'display');
			togglePromptBtnNode.innerHTML = __('Display prompt', 'learnpress');
			promptOutputNode.classList.remove('active');
			courseFiOutputNode.innerHTML = '';
			contentNode.style.opacity = 0.6;
		})();

		const styleNode = contentNode.querySelector('#ai-course-create-fi-field-style');
		const iconNode = contentNode.querySelector('#ai-course-create-fi-field-icon');
		const qualityNode = contentNode.querySelector('#ai-course-create-fi-field-quality');
		const sizeNode = contentNode.querySelector('#ai-course-create-fi-field-size');
		const outputsNode = contentNode.querySelector('#ai-course-create-fi-field-outputs');
		const courseTitleNode = document.querySelector('#titlewrap input');

		let data = {
			style: Array.from(styleNode.selectedOptions).map((option) => option.value),
			icon: iconNode.value,
			size: sizeNode.value,
			quality: qualityNode.value,
			outputs: outputsNode ? outputsNode.value : 1,
			title: courseTitleNode ? courseTitleNode.value: '',
		};

		if (target.getAttribute('id') === 'lp-re-generate-course-create-fi') {
			data.prompt = promptTextArea ? promptTextArea.value : '';
		}

		wp.apiFetch({
			path: '/lp/v1/open-ai/create-feature-image', method: 'POST', data,
		}).then((res) => {
			if (res.status === 'error' && res.msg) {
				// eslint-disable-next-line no-alert
				window.alert(res.msg);
			}

			if (res.data.prompt) {
				promptOutputNode.innerHTML = res.data.prompt.replace(/\\n/g, '\n');
			}

			if (res.data.content) {
				let courseFeatureImage = '';
				[...res.data.content].map((content) => {
					const dataBase64 = content.b64_json;
					const src = `data:image/png;base64,${dataBase64}`;
					courseFeatureImage += `
					<div class="course-create-fi-item">
						<div class="ai-result">
							<img src="${src}" alt="">
						</div>
						<div class="action">
							<button class="ai-save-image button">` + __('Save image', 'learnpress') + `</button>
						</div>
					</div>`;
				});
				courseFiOutputNode.innerHTML = courseFeatureImage;
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

export default createFeaturImage;
