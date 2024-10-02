let modal;
const {__} = wp.i18n;
let activeOpenModalBtn;
let quizData = [];

const curriculumQuiz = () => {
	modal = document.querySelector('#lp-ai-curriculum-quiz-modal');

	if (!modal) {
		return;
	}

	openModal();
	closeModal();
	generate();
	addQuiz();
	addAllQuizzes();
};

const addAllQuizzes = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;
		if (!target.classList.contains('add-all-questions')) {
			return;
		}

		const modal = target.closest('#lp-ai-quiz-question-modal');
		if (!modal) {
			return;
		}

		const data = {
			quiz_id: modal.getAttribute('data-quiz-id'),
			questions: questionData,
		};

		target.disabled = true;
		wp.apiFetch({
			path: '/lp/v1/open-ai/add-question', method: 'POST', data,
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
}

const addQuiz = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;
		if (!target.classList.contains('add-question')) {
			return;
		}

		const modal = target.closest('#lp-ai-quiz-question-modal');
		if (!modal) {
			return;
		}


		const questionNode = target.closest('.question')
		const order = questionNode.getAttribute('data-order');
		const questions = questionData[order] || {};
		const data = {
			quiz_id: modal.getAttribute('data-quiz-id'),
			questions: [questions],
		};

		target.disabled = true;
		wp.apiFetch({
			path: '/lp/v1/open-ai/add-question', method: 'POST', data,
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
			section_id: sectionId,
			section_order: sectionOrder,
			title: quizItem.querySelector('.ai-result').innerHTML,
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
		const section = target.closest('.section');
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
		if (activeOpenModalBtn) {
			activeOpenModalBtn.disabled = false;
		}

		document.querySelector('body').style.overflow = 'visible';
	};

	document.addEventListener('click', function (event) {
		const target = event.target;
		if (target.classList.contains('close-btn') && target.closest('#lp-ai-curriculum-quiz-modal')) {
			handleClose();
		}

		if (target.classList.contains('ai-overlay')) {
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
		const curriculumQuizOutputNode = modal.querySelector('.curriculum-quiz-output');

		const contentNode = modal.querySelector('.content');
		const promptTextArea = promptOutputNode.querySelector('textarea');

		//Before generate
		(() => {
			target.disabled = true;
			togglePromptBtnNode.classList.remove('active', 'display');
			togglePromptBtnNode.innerHTML = __('Display prompt', 'learnpress');
			promptOutputNode.classList.remove('active');
			curriculumQuizOutputNode.innerHTML = '';
			contentNode.style.opacity = 0.6;
		})();

		const topicNode = contentNode.querySelector('#ai-curriculum-quiz-field-topic');
		const goalNode = contentNode.querySelector('#ai-curriculum-quiz-field-goal');
		const audienceNode = contentNode.querySelector('#ai-curriculum-quiz-field-audience');
		const toneNode = contentNode.querySelector('#ai-curriculum-quiz-field-tone');
		const quizNumNode = contentNode.querySelector('#ai-curriculum-quiz-field-quiz-numbers');
		const questionPerQuizNumNode = contentNode.querySelector('#ai-curriculum-quiz-field-question-numbers');
		const langNode = contentNode.querySelector('#ai-curriculum-quiz-field-language');
		const courseTitleNode = document.querySelector('#titlewrap input');

		let data = {
			type: 'curriculum-quiz',
			topic: topicNode.value,
			goal: goalNode.value,
			audience: Array.from(audienceNode.selectedOptions).map((option) => option.value),
			tone: Array.from(toneNode.selectedOptions).map((option) => option.value),
			lang: Array.from(langNode.selectedOptions).map((option) => option.value),
			quiz_num: quizNumNode.value,
			question_per_quiz_number: questionPerQuizNumNode.value,
			course_title: courseTitleNode ? courseTitleNode.value : '',
			data_return: 'json',
		};

		if (target.getAttribute('id') === 'lp-re-generate-curriculum-quiz') {
			data.prompt = promptTextArea ? promptTextArea.value : '';
		}

		wp.apiFetch({
			path: '/lp/v1/open-ai/generate-text', method: 'POST', data,
		}).then((res) => {
			if (res.data.prompt && !data.prompt) {
				promptOutputNode.innerHTML = res.data.prompt.replace(/\\n/g, '\n');
			}

			if (res.data.content) {
				let quizQuestionContent = '';
				const quizQuestions = res.data.content;

				for (let i = 0; i < quizQuestions.length; i++) { // loop quiz questions
					const quizzes = quizQuestions[i];
					quizData = [];
					quizData = [...quizzes];

					for (let j = 0; j < quizzes.length; j++) {
						const quiz = quizzes[j];

						if (!quiz) {
							continue;
						}

						const questions = quiz?.questions || [];
						quizQuestionContent += `
						<div class="quiz" data-order ="${j}">
							<div class="quiz-title">
								<div><b>`+__('Quiz title:', 'learnpress')+`</b> </div>
								<div>${quiz.quiz_title}</div>
							</div>`;
							for (let k = 0; k < questions.length; k++) {
								const question = questions[k];
								if (!question) {
									continue;
								}
								quizQuestionContent += `
								<div class="quiz-question">
									<div><b>`+__('Questions:', 'learnpress')+`</b> </div>
									<div class="question" data-question-type="${question.question_type}">`;
										switch (question.question_type) {
											case 'single_choice':
												quizQuestionContent += generateSingleChoice(question);
												break;
											case 'multi_choice':
												quizQuestionContent += generateMultiChoice(question);
												break;
											case 'true_or_false':
												quizQuestionContent += generateTrueOrFalse(question);
												break;
											case 'fill_in_blanks':
												quizQuestionContent += generateFillInBlanks(question);
												break;
											default:
												break;
										}
							}
						quizQuestionContent +=
									`</div>
								</div>
							<button class="add-quiz button">` + __('Add quiz', 'learnpress') + `</button>
						</div>`;
					}

					quizQuestionContent += `
					<div class="course-curriculum-quiz-item">
						<div class="ai-result">
							${quizQuestionContent}
						</div>
						<div class="action">
							<button class="add-all-questions button">` + __('Add all quizzes', 'learnpress') + `</button>
						</div>
					</div>`;
				}

				curriculumQuizOutputNode.innerHTML = quizQuestionContent;
			}

			if (res.msg && res.status === 'error') {
				curriculumQuizOutputNode.innerHTML = `<div class="error"> ${res.msg} </div>`;
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

const generateSingleChoice = (question) => {
	let options = question.options || []
	let optionHTML = '';
	options.map((option) => {
		optionHTML += `<li>${option}</li>`;
	});

	return `
	<ul class="question-list-item">
		<li class="question-title">
		  <div class="title">` + __('Title', 'learnpress') + `</div>
		  <div class="value">${question.question_title}</div>
		</li>
		<li class="question-type">
		 <div class="title">` + __('Type', 'learnpress') + `</div>
		  <div class="value">` + __('Single Choice', 'learnpress') + `</div>
		</li>
		<li class="question-options">
		 <div class="title">` + __('Options', 'learnpress') + `</div>
		 <ul class="value">
			${optionHTML}
		</ul>
		</li>
		<li class="question-answer">
		 <div class="title">` + __('Answer', 'learnpress') + `</div>
		 <div class="value">${question.answer}</div>
		</li>
	</ul>`;
}

const generateMultiChoice = (question) => {
	let options = question.options || []
	let optionHTML = '';
	options.map((option) => {
		optionHTML += `<li>${option}</li>`;
	});

	let answer = question.answer;
	let answerHTML = ''
	if (answer.length) {
		answer.map((el) => {
			answerHTML += `<li>${el}</li>`;
		});
	}
	return `
	<ul class="question-list-item">
		<li class="question-title">
		  <div class="title">` + __('Title', 'learnpress') + `</div>
		  <div class="value">${question.question_title}</div>
		</li>
		<li class="question-type">
		 <div class="title">` + __('Type', 'learnpress') + `</div>
		  <div class="value">` + __('Multiple Choice', 'learnpress') + `</div>
		</li>
		<li class="question-options">
		 <div class="title">` + __('Options', 'learnpress') + `</div>
		 <ul class="value">
			${optionHTML}
		</ul>
		</li>
		<li class="question-answer">
		 <div class="title">` + __('Answer', 'learnpress') + `</div>
		 <ul class="value">${answerHTML}</ul>
		</li>
	</ul>`;
}

const generateTrueOrFalse = (question) => {
	let options = question.options || []
	let optionHTML = '';
	options.map((option) => {
		optionHTML += `<li>${option}</li>`;
	});

	return `
	<ul class="question-list-item">
		<li class="question-title">
		  <div class="title">` + __('Title', 'learnpress') + `</div>
		  <div class="value">${question.question_title}</div>
		</li>
		<li class="question-type">
		 <div class="title">` + __('Type', 'learnpress') + `</div>
		  <div class="value">` + __('True or False', 'learnpress') + `</div>
		</li>
		<li class="question-options">
		 <div class="title">` + __('Options', 'learnpress') + `</div>
		 <ul class="value">
			${optionHTML}
		</ul>
		</li>
		<li class="question-answer">
		 <div class="title">` + __('Answer', 'learnpress') + `</div>
		 <div class="value">${question.answer}</div>
		</li>
	</ul>`;
}

const generateFillInBlanks = (question) => {
	let options = question.options || []
	let optionHTML = '';
	options.map((option) => {
		optionHTML += `<li>${option}</li>`;
	});

	let answer = question.answer;
	let answerHTML = ''
	if (answer.length) {
		answer.map((el) => {
			answerHTML += `<li>${el}</li>`;
		});
	}
	return `
		<ul class="question-list-item">
			<li class="question-title">
			  <div class="title">` + __('Title', 'learnpress') + `</div>
			  <div class="value">${question.question_title}</div>
			</li>
			<li class="question-type">
			 <div class="title">` + __('Type', 'learnpress') + `</div>
			  <div class="value">` + __('Fill in Blanks', 'learnpress') + `</div>
			</li>
			<li class="question-content">
			 <div class="title">` + __('Content', 'learnpress') + `</div>
			 <div class="value">
				${question.question_content}
			</div>
			</li>
			<li class="question-answer">
			 <div class="title">` + __('Answer', 'learnpress') + `</div>
			 <div class="value">${question.answer}</div>
			</li>
		</ul>`;
}

export default curriculumQuiz;
