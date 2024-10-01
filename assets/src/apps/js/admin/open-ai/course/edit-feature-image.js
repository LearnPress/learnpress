let modal;
const {__} = wp.i18n;

const editFeatureImage = () => {
	modal = document.querySelector('#lp-ai-course-edit-fi-modal');

	if (!modal) {
		return;
	}

	createImageBtn();
	logo();
	mask();
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

		if (!target.closest('.course-edit-fi-item')) {
			return;
		}

		const actionNode = target.closest('.action');
		target.disabled = true;
		const data = {
			image_data: target.closest('.course-edit-fi-item').querySelector('.ai-result img').src
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

const logo = () => {
	if (!modal) {
		return;
	}
	const uploadBtn = modal.querySelector('#ai-course-edit-fi-field-logo');

	if (!uploadBtn) {
		return;
	}

	const input = modal.querySelector('#ai-course-fi-field-logo-input');
	const preview = modal.querySelector('#ai-course-fi-field-logo-preview');
	const removeBtn = document.getElementById('ai-course-remove-fi-field-logo');

	uploadBtn.addEventListener('click', function () {
		input.click();
	});

	input.addEventListener('change', function (event) {
		const file = this.files[0];
		const errorMessage = document.querySelector('#ai-course-fi-field-logo-error');
		errorMessage.innerHTML = '';

		if (file) {
			if (file.type !== 'image/png') {
				errorMessage.innerHTML = __('Error: File must be a PNG image.', 'learnpress');
				return;
			}

			if (file.size > 4 * 1024 * 1024) {
				errorMessage.innerHTML = __('Error: File must be less than 4MB.', 'learnpress');
				return;
			}

			const reader = new FileReader();
			reader.onload = function (e) {
				const img = new Image();
				img.src = e?.target?.result;

				img.onload = function () {
					if (img.width !== img.height) {
						errorMessage.innerHTML = __('Error: Image must be square.', 'learnpress');
						return;
					}

					preview.innerHTML = `
                    <img src="${e.target.result}" alt="` + __('Image preview', 'learnpress') + `">
                `;
					removeBtn.classList.add('enabled');
				};
			};

			reader.readAsDataURL(file);
		}
	});

	removeBtn.addEventListener('click', function () {
		preview.innerHTML = '';
		document.getElementById('ai-course-fi-field-logo-input').value = '';

		removeBtn.classList.remove('enabled');
	});
};

const mask = () => {
	if (!modal) {
		return;
	}
	const uploadBtn = modal.querySelector('#ai-course-edit-fi-field-mask');

	if (!uploadBtn) {
		return;
	}

	const input = modal.querySelector('#ai-course-fi-field-mask-input');
	const preview = modal.querySelector('#ai-course-fi-field-mask-preview');
	const removeBtn = modal.querySelector('#ai-course-remove-fi-field-mask');

	uploadBtn.addEventListener('click', function () {
		input.click();
	});

	input.addEventListener('change', function (event) {
		const file = this.files[0];
		const errorMessage = document.querySelector('#ai-course-fi-field-mask-error');
		errorMessage.innerHTML = '';

		if (file) {
			if (file.type !== 'image/png') {
				errorMessage.innerHTML = __('Error: File must be a PNG image.', 'learnpress');
				return;
			}

			if (file.size > 4 * 1024 * 1024) {
				errorMessage.innerHTML = __('Error: File must be less than 4MB.', 'learnpress');
				return;
			}

			const reader = new FileReader();
			reader.onload = function (e) {
				const img = new Image();
				img.src = e?.target?.result;

				img.onload = function () {
					if (img.width !== img.height) {
						errorMessage.innerHTML = __('Error: Image must be square.', 'learnpress');
						return;
					}

					preview.innerHTML = `
                    <img src="${e.target.result}" alt="` + __('Image preview', 'learnpress') + `">
                `;


					removeBtn.classList.add('enabled');
				};
			};

			reader.readAsDataURL(file);
		}
	});

	removeBtn.addEventListener('click', function () {
		preview.innerHTML = '';
		input.value = '';

		removeBtn.classList.remove('enabled');
	});
};

const createImageBtn = () => {
	const imageDiv = document.querySelector('body.post-type-lp_course #postimagediv');
	if (!imageDiv) {
		return;
	}

	imageDiv.insertAdjacentHTML('beforeend', `
	<div id="lp-ai-fi-button">
	<button type="button" class="button" id="lp-edit-ai-course-create-fi">` + __('Create new AI image', 'learnpress') + `</button>
	<button type="button" class="button" id="lp-edit-ai-course-edit-fi">` + __('Edit AI image', 'learnpress') + `</button>
	</div>`);
};

const openModal = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;

		if (target.id !== 'lp-edit-ai-course-edit-fi') {
			return;
		}

		modal.classList.add('active');
		target.disabled = false;

		document.querySelector('body').style.overflow = 'hidden';
	});
};

const closeModal = () => {
	const handleClose = () => {
		const openModalBtn = document.querySelector('#lp-edit-ai-course-edit-fi');

		modal.classList.remove('active');
		openModalBtn.disabled = false;

		document.querySelector('body').style.overflow = 'visible';
	};

	document.addEventListener('click', function(event) {
		const target = event.target;
		if (target.classList.contains('close-btn') && target.closest('#lp-ai-course-edit-fi-modal')) {
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
		if (!['lp-generate-course-edit-fi-btn', 'lp-re-generate-course-edit-fi'].includes(target.getAttribute('id'))) {
			return;
		}

		const modal = target.closest('#lp-ai-course-edit-fi-modal');

		if (!modal) {
			return;
		}

		const togglePromptBtnNode = modal.querySelector('.toggle-prompt');
		const promptOutputNode = modal.querySelector('.prompt-output');
		const courseFeatureImageOutputNode = modal.querySelector('.course-edit-fi-output');

		const contentNode = modal.querySelector('.content');
		const promptTextArea = promptOutputNode.querySelector('textarea');

		//Before generate
		(() => {
			target.disabled = true;
			togglePromptBtnNode.classList.remove('active', 'display');
			togglePromptBtnNode.innerHTML = __('Display prompt', 'learnpress');
			promptOutputNode.classList.remove('active');
			courseFeatureImageOutputNode.innerHTML = '';
			contentNode.style.opacity = 0.6;
		})();

		const styleNode = contentNode.querySelector('#ai-course-edit-fi-field-style');
		const iconNode = contentNode.querySelector('#ai-course-edit-fi-field-icon');
		const logoNode = contentNode.querySelector('#ai-course-fi-field-logo-preview img');
		const maskNode = contentNode.querySelector('#ai-course-fi-field-mask-preview img');
		const sizeNode = contentNode.querySelector('#ai-course-edit-fi-field-size');
		const outputsNode = contentNode.querySelector('#ai-course-edit-fi-field-outputs');
		const courseTitleNode = document.querySelector('#titlewrap input');

		const data = {
			style: Array.from(styleNode.selectedOptions).map((option) => option.value),
			icon: iconNode.value,
			logo: logoNode?.src ? logoNode.src : '',
			mask: maskNode?.src ? maskNode.src : '',
			size: sizeNode.value,
			outputs: outputsNode.value,
			title: courseTitleNode ? courseTitleNode.value: '',
		};

		if (target.getAttribute('id') === 'lp-re-generate-course-edit-fi') {
			data.prompt = promptTextArea ? promptTextArea.value : '';
		}


		wp.apiFetch({
			path: '/lp/v1/open-ai/edit-feature-image', method: 'POST', data,
		}).then((res) => {
			if (res.data.prompt && !data.prompt ) {
				promptOutputNode.innerHTML = res.data.prompt.replace(/\\n/g, '\n');
			}

			if (res.data.content) {
				let courseFeatureImage = '';
				[...res.data.content].map((content) => {
					const dataBase64 = content.b64_json;
					const src = `data:image/png;base64,${dataBase64}`;
					courseFeatureImage += `
					<div class="course-edit-fi-item">
						<div class="ai-result">
							<img src="${src}" alt="">
						</div>
						<div class="action">
							<button class="ai-save-image button">` + __('Save image', 'learnpress') + `</button>
						</div>
					</div>`;
				});
				courseFeatureImageOutputNode.innerHTML = courseFeatureImage;
			}

			if (res.msg && res.status === 'error') {
				courseFeatureImageOutputNode.innerHTML = `<div class="error"> ${res.msg} </div>`;
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

export default editFeatureImage;
