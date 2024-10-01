let modal;
const {__} = wp.i18n;
let questionData = [];
const quizQuestion = () => {
	modal = document.querySelector('#lp-ai-quiz-question-modal');
	if (!modal) {
		return;
	}

	openModal();
	closeModal();
	generate();
	addQuestion();
	addAllQuestions();
};

const addAllQuestions = () => {
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

const addQuestion = () => {
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


const openModal = () => {
	document.addEventListener('click', function (event) {
		const target = event.target;

		if (target.id !== 'lp-edit-ai-quiz-question') {
			return;
		}

		modal.classList.add('active');
		target.disabled = false;
		document.querySelector('body').style.overflow = 'hidden';
	});
};

const closeModal = () => {
	const handleClose = () => {
		const openModalBtn = document.querySelector('#lp-edit-ai-quiz-question');

		modal.classList.remove('active');

		if (openModalBtn) {
			openModalBtn.disabled = false;
		}

		document.querySelector('body').style.overflow = 'visible';
	};

	document.addEventListener('click', function (event) {
		const target = event.target;
		if (target.classList.contains('close-btn') && target.closest('#lp-ai-quiz-question-modal')) {
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

		if (!['lp-generate-quiz-question-btn', 'lp-re-generate-quiz-question'].includes(target.getAttribute('id'))) {
			return;
		}

		const modal = target.closest('#lp-ai-quiz-question-modal');

		if (!modal) {
			return;
		}

		const togglePromptBtnNode = modal.querySelector('.toggle-prompt');
		const promptOutputNode = modal.querySelector('.prompt-output');
		const quizQuestionOutputNode = modal.querySelector('.quiz-question-output');

		const contentNode = modal.querySelector('.content');
		const promptTextArea = promptOutputNode.querySelector('textarea');

		//Before generate
		(() => {
			target.disabled = true;
			togglePromptBtnNode.classList.remove('active', 'display');
			togglePromptBtnNode.innerHTML = __('Display prompt', 'learnpress');
			promptOutputNode.classList.remove('active');
			quizQuestionOutputNode.innerHTML = '';
			contentNode.style.opacity = 0.6;
		})();


		const topicNode = contentNode.querySelector('#ai-quiz-question-field-topic');
		const goalNode = contentNode.querySelector('#ai-quiz-question-field-goal');
		const audienceNode = contentNode.querySelector('#ai-quiz-question-field-audience');
		const toneNode = contentNode.querySelector('#ai-quiz-question-field-tone');
		const typeNode = contentNode.querySelector('#ai-quiz-question-field-type');
		const questionNumberNode = contentNode.querySelector('#ai-quiz-question-field-number');
		const languageNode = contentNode.querySelector('#ai-quiz-question-field-language');
		const outputsNode = contentNode.querySelector('#ai-quiz-question-field-outputs');
		const quizTitleNode = document.querySelector('#titlewrap input');
		const editor = tinyMCE.get('content');

		let data = {
			type: 'quiz-question',
			topic: topicNode.value,
			goal: goalNode.value,
			audience: Array.from(audienceNode.selectedOptions).map((option) => option.value),
			tone: Array.from(toneNode.selectedOptions).map((option) => option.value),
			question_type: Array.from(typeNode.selectedOptions).map((option) => ({
				'type': option.value,
				'text': option.text,
			})),
			number: questionNumberNode.value,
			lang: Array.from(languageNode.selectedOptions).map((option) => option.value),
			outputs: outputsNode ? outputsNode.value : 1,
			title: quizTitleNode ? quizTitleNode.value : '',
			data_return: 'json',
		};

		if (!!editor) {
			data.description = editor.getContent()
		}

		if (target.getAttribute('id') === 'lp-re-generate-quiz-question') {
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
					let questionContent = '';
					const quizQuestion = quizQuestions[i];

					if (!quizQuestion) {
						continue;
					}

					const questions = quizQuestion?.questions || [];
					questionData = [];
					questionData = [...questions];

					for (let j = 0; j < questions.length; j++) {
						const question = questions[j];
						if (!question) {
							continue;
						}

						questionContent += `
						<div class="question" data-question-type="${question.question_type}" data-order ="${j}">`;
						switch (question.question_type) {
							case 'single_choice':
								questionContent += generateSingleChoice(question);
								break;
							case 'multi_choice':
								questionContent += generateMultiChoice(question);
								break;
							case 'true_or_false':
								questionContent += generateTrueOrFalse(question);
								break;
							case 'fill_in_blanks':
								questionContent += generateFillInBlanks(question);
								break;
							default:
								break;
						}
						questionContent +=
							`<button class="add-question button">` + __('Add question', 'learnpress') + `</button>
						</div>`;
					}

					quizQuestionContent += `
					<div class="course-quiz-question-item">
						<div class="ai-result">
							${questionContent}
						</div>
						<div class="action">
							<button class="add-all-questions button">` + __('Add all questions', 'learnpress') + `</button>
						</div>
					</div>`;
				}

				quizQuestionOutputNode.innerHTML = quizQuestionContent;
			}

			if (res.msg && res.status === 'error') {
				quizQuestionOutputNode.innerHTML = `<div class="error"> ${res.msg} </div>`;
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

export default quizQuestion;
