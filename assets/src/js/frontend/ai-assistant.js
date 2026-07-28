/**
 * LP AI Assistant - frontend chat widget.
 *
 * LearnPress runtime implementation:
 * - ES6 class module.
 * - Delegated events via lpUtils.eventHandlers.
 * - Boot via lpUtils.lpOnElementReady.
 * - AJAX transport via window.lpAJAXG.fetchAJAX.
 *
 * @since   4.3.5
 * @version 1.0.0
 */
import * as lpUtils from '../utils.js';
import SweetAlert from 'sweetalert2';

export class AIAssistantWidget {
	constructor() {
		this.config = null;
		this.root = null;
		this.elements = {};
		this.storageKey = '';
		this.history = [];
		this.activeQuizState = null;
		this.isRequesting = false;
		this.quizHookBound = false;
		this.lastQuizReviewSignature = '';
	}

	static selectors = {
		root: '#lp-ai-assistant',
		toggleBtn: '.lp-ai-assistant__toggle',
		panel: '.lp-ai-assistant__panel',
		closeBtn: '.lp-ai-assistant__close-btn',
		clearBtn: '.lp-ai-assistant__clear-btn',
		msgList: '.lp-ai-assistant__messages',
		inputEl: '.lp-ai-assistant__input',
		sendBtn: '.lp-ai-assistant__send-btn',
		quickBtn: '.lp-ai-assistant__quick-btn',
		quickActions: '.lp-ai-assistant__quick-actions',
		inputArea: '.lp-ai-assistant__input-area',
		smartReviewBtn: '.lp-ai-assistant__smart-review-btn',
		quizCard: '.lp-ai-assistant__quiz-card',
		quizOptionBtn: '.lp-ai-assistant__quiz-option',
	};

	/**
	 * Curriculum item types the assistant supports.
	 *
	 * Mirrors AIAssistantController::get_supported_item_types(). The server re-validates,
	 * so this only avoids pointless requests.
	 */
	static itemTypes = [ 'lp_lesson', 'lp_quiz' ];

	init() {
		if ( ! this.validateConfig() ) {
			return;
		}

		this.root = document.querySelector( AIAssistantWidget.selectors.root );
		if ( ! this.root ) {
			return;
		}

		this.cacheElements();
		if ( ! this.validateDOM() ) {
			return;
		}

		this.storageKey = `lp_ai_chat_${ this.config.context }_${ this.config.itemId }`;
		this.applyInitialState();
		this.loadHistory();
		this.renderHistoryToDOM();
		this.bindQuizCompletedHook();
		this.events();
	}

	validateConfig() {
		if ( typeof window.lpAIAssistant !== 'object' || ! window.lpAIAssistant ) {
			return false;
		}

		this.config = window.lpAIAssistant;
		if ( ! this.config.enabled ) {
			return false;
		}

		const requiredString = [ 'nonce', 'ajaxUrl' ];
		for ( const key of requiredString ) {
			if ( typeof this.config[ key ] !== 'string' || ! this.config[ key ] ) {
				return false;
			}
		}

		const itemId = Number.isInteger( this.config.itemId ) ? this.config.itemId : this.config.lessonId;
		if ( ! Number.isInteger( itemId ) || itemId <= 0 ) {
			return false;
		}

		if ( ! Number.isInteger( this.config.courseId ) || this.config.courseId <= 0 ) {
			return false;
		}

		/**
		 * itemType is required and must come from the server-localized config. It is
		 * never guessed from context, lessonId or the numeric ID: a course item is
		 * identified by (courseId, itemType, itemId), and inferring one leg of that
		 * tuple on the client would let a lesson request address a quiz record.
		 */
		if ( ! AIAssistantWidget.itemTypes.includes( this.config.itemType ) ) {
			return false;
		}

		this.config.itemId = itemId;
		this.config.lessonId = itemId; // Backward compatibility for existing AJAX contract.
		this.config.context = this.config.context === 'quiz' ? 'quiz' : 'lesson';
		this.config.quizCompleted = !! this.config.quizCompleted;
		this.config.enabledActions = {
			summarize: true,
			explain: true,
			quick_quiz: true,
			smart_review: true,
			...( this.config.enabledActions || {} ),
		};

		this.config.i18n = {
			you: this.config?.i18n?.you || 'You',
			assistant: this.config?.i18n?.assistant || 'AI Assistant',
			thinking: this.config?.i18n?.thinking || 'Thinking...',
			sendError: this.config?.i18n?.sendError || 'An error occurred. Please try again.',
			clearConfirm: this.config?.i18n?.clearConfirm || 'Clear chat history?',
			explainPrompt: this.config?.i18n?.explainPrompt || 'Explain a concept from this lesson.',
			quizPrompt: this.config?.i18n?.quizPrompt || 'Create a quick quiz from this lesson.',
			summarizePrompt: this.config?.i18n?.summarizePrompt || 'Summarize this lesson with key points.',
			smartReviewPrompt: this.config?.i18n?.smartReviewPrompt || 'Give me a smart review of my quiz results.',
			quizCorrectTitle: this.config?.i18n?.quizCorrectTitle || 'Correct!',
			quizWrongTitle: this.config?.i18n?.quizWrongTitle || 'Not correct!',
		};

		return true;
	}

	cacheElements() {
		this.elements.toggleBtn = document.querySelector( AIAssistantWidget.selectors.toggleBtn );
		this.elements.panel = this.root.querySelector( AIAssistantWidget.selectors.panel );
		this.elements.closeBtn = this.root.querySelector( AIAssistantWidget.selectors.closeBtn );
		this.elements.clearBtn = this.root.querySelector( AIAssistantWidget.selectors.clearBtn );
		this.elements.msgList = this.root.querySelector( AIAssistantWidget.selectors.msgList );
		this.elements.inputEl = this.root.querySelector( AIAssistantWidget.selectors.inputEl );
		this.elements.sendBtn = this.root.querySelector( AIAssistantWidget.selectors.sendBtn );
		this.elements.inputArea = this.root.querySelector( AIAssistantWidget.selectors.inputArea );
		this.elements.quickActions = this.root.querySelector( AIAssistantWidget.selectors.quickActions );
		this.elements.smartReviewBtn = this.root.querySelector( AIAssistantWidget.selectors.smartReviewBtn );
	}

	validateDOM() {
		// inputEl and sendBtn are optional — absent when free chat is disabled.
		return !! (
			this.elements.toggleBtn &&
			this.elements.panel &&
			this.elements.msgList
		);
	}

	applyInitialState() {
		if ( this.elements.smartReviewBtn ) {
			const showSmartReview = this.config.context === 'quiz'
				? this.config.quizCompleted
				: !! this.config.enabledActions?.smart_review;
			this.elements.smartReviewBtn.hidden = ! showSmartReview;
		}

		this.setQuizInputMode( false );
	}

	bindQuizCompletedHook() {
		if ( this.config.context === 'quiz' ) {
			return;
		}

		if ( this.quizHookBound || ! this.elements.smartReviewBtn ) {
			return;
		}

		const hooks = window?.wp?.hooks;
		if ( ! hooks || typeof hooks.addAction !== 'function' ) {
			return;
		}

		hooks.addAction( 'lp-js-quiz-answer', 'learnpress/ai-assistant-smart-review', ( answered, status ) => {
			if ( String( status || '' ).toLowerCase() !== 'completed' ) {
				return;
			}

			this.config.quizCompleted = true;
			this.elements.smartReviewBtn.hidden = false;
		} );

		this.quizHookBound = true;
	}

	events() {
		if ( AIAssistantWidget._loadedEvents ) {
			return;
		}
		AIAssistantWidget._loadedEvents = this;

		lpUtils.eventHandlers( 'click', [
			{
				selector: AIAssistantWidget.selectors.toggleBtn,
				class: this,
				callBack: this.handleToggleClick.name,
			},
			{
				selector: `${ AIAssistantWidget.selectors.root } ${ AIAssistantWidget.selectors.closeBtn }`,
				class: this,
				callBack: this.handleCloseClick.name,
			},
			{
				selector: `${ AIAssistantWidget.selectors.root } ${ AIAssistantWidget.selectors.clearBtn }`,
				class: this,
				callBack: this.handleClearClick.name,
			},
			{
				selector: `${ AIAssistantWidget.selectors.root } ${ AIAssistantWidget.selectors.sendBtn }`,
				class: this,
				callBack: this.handleSendClick.name,
			},
			{
				selector: `${ AIAssistantWidget.selectors.root } ${ AIAssistantWidget.selectors.quickBtn }`,
				class: this,
				callBack: this.handleQuickActionClick.name,
			},
			{
				selector: `${ AIAssistantWidget.selectors.root } ${ AIAssistantWidget.selectors.quizOptionBtn }`,
				class: this,
				callBack: this.handleQuizOptionClick.name,
			},
		] );

		lpUtils.eventHandlers( 'keydown', [
			{
				selector: `${ AIAssistantWidget.selectors.root } ${ AIAssistantWidget.selectors.inputEl }`,
				class: this,
				callBack: this.handleInputKeydown.name,
			},
			{
				selector: 'body',
				class: this,
				callBack: this.handleEscapeKeydown.name,
			},
		] );
	}

	handleToggleClick( args ) {
		args.e.preventDefault();
		if ( this.elements.panel.hidden ) {
			this.openPanel();
		} else {
			this.closePanel();
		}
	}

	handleCloseClick( args ) {
		args.e.preventDefault();
		this.closePanel();
	}

	handleClearClick( args ) {
		args.e.preventDefault();
		SweetAlert.fire( {
			title: this.config.i18n.clearConfirm,
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: 'var(--lp-primary-color, #ffb606)',
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				this.clearHistory();
			}
		} );
	}

	handleSendClick( args ) {
		args.e.preventDefault();
		this.sendMessage( this.elements.inputEl?.value ?? '' );
	}

	handleQuickActionClick( args ) {
		args.e.preventDefault();

		if ( this.activeQuizState?.is_active ) {
			return;
		}

		const btn = args.target.closest( AIAssistantWidget.selectors.quickBtn );
		if ( ! btn ) {
			return;
		}

		const action = btn.dataset.lpAiAction;
		const prompts = {
			explain: this.config.i18n.explainPrompt,
			'quick-quiz': this.config.i18n.quizPrompt,
			summarize: this.config.i18n.summarizePrompt,
			'smart-review': this.config.i18n.smartReviewPrompt,
		};

		const prompt = prompts[ action ];
		if ( ! prompt ) {
			return;
		}

		this.openPanel();
		this.sendMessage( prompt, action );
	}

	handleQuizOptionClick( args ) {
		args.e.preventDefault();
		if ( this.isRequesting || ! this.activeQuizState?.is_active ) {
			return;
		}

		const btn = args.target.closest( AIAssistantWidget.selectors.quizOptionBtn );
		if ( ! btn ) {
			return;
		}

		const answerText = ( btn.dataset.option || btn.textContent || '' ).trim();
		if ( ! answerText ) {
			return;
		}

		this.sendMessage( answerText );
	}

	handleInputKeydown( args ) {
		if ( this.activeQuizState?.is_active ) {
			return;
		}

		if ( args.e.key === 'Enter' && ! args.e.shiftKey ) {
			args.e.preventDefault();
			this.sendMessage( this.elements.inputEl?.value ?? '' );
		}
	}

	handleEscapeKeydown( args ) {
		if ( args.e.key !== 'Escape' ) {
			return;
		}

		if ( this.elements.panel && ! this.elements.panel.hidden ) {
			this.closePanel();
		}
	}

	getAjaxHandle() {
		const ajaxHandle = window.lpAJAXG;
		if ( ! ajaxHandle || typeof ajaxHandle.fetchAJAX !== 'function' ) {
			return null;
		}

		return ajaxHandle;
	}

	openPanel() {
		this.elements.panel.hidden = false;
		this.root.setAttribute( 'aria-hidden', 'false' );
		this.elements.toggleBtn.setAttribute( 'aria-expanded', 'true' );
		this.elements.toggleBtn.classList.add( 'is-hidden' );
		this.elements.inputEl?.focus();

		if ( this.elements.msgList ) {
			this.elements.msgList.scrollTop = this.elements.msgList.scrollHeight;
		}
	}

	closePanel() {
		this.elements.panel.hidden = true;
		this.root.setAttribute( 'aria-hidden', 'true' );
		this.elements.toggleBtn.setAttribute( 'aria-expanded', 'false' );
		this.elements.toggleBtn.classList.remove( 'is-hidden' );
		this.elements.toggleBtn.focus();
	}

	setLoadingState( isLoading ) {
		this.isRequesting = isLoading;
		if ( this.elements.sendBtn ) {
			this.elements.sendBtn.disabled = isLoading;
		}
		if ( this.elements.inputEl ) {
			this.elements.inputEl.disabled = isLoading;
		}
	}

	setQuizInputMode( isQuizActive ) {
		if ( this.elements.inputArea ) {
			this.elements.inputArea.classList.toggle( 'lp-ai-assistant__input-area--hidden', isQuizActive );
		}

		if ( this.elements.quickActions ) {
			this.elements.quickActions.classList.toggle( 'lp-ai-assistant__quick-actions--disabled', isQuizActive );
		}
	}

	loadHistory() {
		try {
			const raw = localStorage.getItem( this.storageKey );
			this.history = raw ? JSON.parse( raw ) : [];
			if ( ! Array.isArray( this.history ) ) {
				this.history = [];
			}

			const lastReview = [ ...this.history ]
				.reverse()
				.find( ( item ) => item?.type === 'quiz_review' && item?.review );
			this.lastQuizReviewSignature = lastReview?.review
				? this.getQuizReviewKey( lastReview.review )
				: '';
		} catch ( _e ) {
			this.history = [];
			this.lastQuizReviewSignature = '';
		}
	}

	saveHistory() {
		try {
			localStorage.setItem( this.storageKey, JSON.stringify( this.history ) );
		} catch ( _e ) {
			// Ignore storage errors.
		}
	}

	clearHistory() {
		this.history = [];
		this.activeQuizState = null;
		this.lastQuizReviewSignature = '';
		this.elements.msgList.innerHTML = '';
		localStorage.removeItem( this.storageKey );
		this.setQuizInputMode( false );
	}

	/**
	 * Create an element with a class and plain text content.
	 *
	 * All assistant content originates from OpenAI output, so it is built through the
	 * DOM API only. Nothing on this path goes through innerHTML: text assigned via
	 * textContent can never become markup, and attributes set via the DOM API can never
	 * break out into a new attribute or event handler.
	 *
	 * @param {string} tag       Tag name.
	 * @param {string} className Class attribute.
	 * @param {string} text      Text content.
	 * @return {HTMLElement} The created element.
	 */
	createEl( tag, className, text = '' ) {
		const el = document.createElement( tag );
		if ( className ) {
			el.className = className;
		}
		if ( text !== '' ) {
			el.textContent = String( text );
		}
		return el;
	}

	appendMessage( role, text ) {
		const el = this.createEl( 'div', `lp-ai-assistant__msg lp-ai-assistant__msg--${ role }` );
		const label = role === 'user' ? this.config.i18n.you : this.config.i18n.assistant;

		el.appendChild( this.createEl( 'span', 'lp-ai-assistant__msg-label', label ) );
		el.appendChild( this.createEl( 'p', 'lp-ai-assistant__msg-text', text ) );

		this.elements.msgList.appendChild( el );
		this.elements.msgList.scrollTop = this.elements.msgList.scrollHeight;
		return el;
	}

	renderHistoryToDOM() {
		this.elements.msgList.innerHTML = '';
		this.history.forEach( ( message ) => {
			if ( message?.type === 'quiz_review' && message?.review ) {
				this.appendQuizReviewCard( message.review );
				return;
			}

			if ( ! message || ! [ 'user', 'assistant' ].includes( message.role ) ) {
				return;
			}

			this.appendMessage( message.role, message.content || '' );
		} );

		this.renderQuizState();
	}

	getQuizReviewFromState( quiz ) {
		if ( ! quiz || ! quiz.feedback || typeof quiz.feedback !== 'object' ) {
			return null;
		}

		const currentIndex = Number.parseInt( quiz.current_index || 0, 10 );
		const questionIndex = Math.max( 0, currentIndex - 1 );
		const question = quiz.questions?.[ questionIndex ];
		if ( ! question || ! Array.isArray( question.options ) ) {
			return null;
		}

		const selectedIndex = Number.parseInt( quiz.feedback.selected_index ?? -1, 10 );
		const correctIndex = Number.parseInt( quiz.feedback.correct_index ?? -1, 10 );

		return {
			question_index: questionIndex,
			total: Number.parseInt( quiz.total || question.options.length || 0, 10 ),
			question: question.question || '',
			options: question.options,
			selected_index: selectedIndex,
			correct_index: correctIndex,
			is_correct: !! quiz.feedback.is_correct,
			explanation: quiz.feedback.explanation || '',
		};
	}

	getQuizReviewKey( review ) {
		return [
			review.question_index,
			review.total,
			review.selected_index,
			review.correct_index,
			review.is_correct ? 1 : 0,
		].join( '|' );
	}

	pushQuizReviewToHistory( review ) {
		const reviewKey = this.getQuizReviewKey( review );
		if ( reviewKey === this.lastQuizReviewSignature ) {
			return false;
		}
		this.lastQuizReviewSignature = reviewKey;

		this.history.push( {
			type: 'quiz_review',
			review,
		} );
		this.saveHistory();

		return true;
	}

	/**
	 * Build one quiz option button.
	 *
	 * @param {string}   option       Option text from model output.
	 * @param {number}   index        Zero-based option index.
	 * @param {string[]} extraClasses Additional state classes.
	 * @return {HTMLButtonElement} The option button.
	 */
	buildQuizOption( option, index, extraClasses = [] ) {
		const classes = [ 'lp-ai-assistant__quiz-option', ...extraClasses ].join( ' ' );
		const letter = String.fromCharCode( 65 + index );

		const btn = this.createEl( 'button', classes, `${ letter }. ${ String( option ) }` );
		btn.type = 'button';

		return btn;
	}

	buildQuizReviewOptions( review ) {
		const options = Array.isArray( review.options ) ? review.options : [];

		return options.map( ( option, index ) => {
			const classes = [];
			if ( index === review.correct_index ) {
				classes.push( 'is-correct-answer' );
			}

			if ( index === review.selected_index ) {
				classes.push( review.is_correct ? 'is-selected-correct' : 'is-selected-wrong' );
			}

			const btn = this.buildQuizOption( option, index, classes );
			btn.disabled = true;

			return btn;
		} );
	}

	appendQuizReviewCard( review ) {
		const card = this.createEl( 'div', 'lp-ai-assistant__quiz-card lp-ai-assistant__quiz-card--review' );
		card.dataset.reviewKey = this.getQuizReviewKey( review );

		const optionCount = Array.isArray( review.options ) ? review.options.length : 0;
		const total = review.total || optionCount;

		card.appendChild(
			this.createEl( 'div', 'lp-ai-assistant__quiz-head', `Question ${ review.question_index + 1 }/${ total }` )
		);
		card.appendChild( this.createEl( 'div', 'lp-ai-assistant__quiz-question', review.question || '' ) );

		const optionsEl = this.createEl( 'div', 'lp-ai-assistant__quiz-options' );
		this.buildQuizReviewOptions( review ).forEach( ( btn ) => optionsEl.appendChild( btn ) );
		card.appendChild( optionsEl );

		const feedbackClass = review.is_correct ? 'is-correct' : 'is-wrong';
		const feedbackEl = this.createEl( 'div', `lp-ai-assistant__quiz-feedback ${ feedbackClass }` );
		feedbackEl.appendChild(
			this.createEl(
				'strong',
				'',
				review.is_correct ? this.config.i18n.quizCorrectTitle : this.config.i18n.quizWrongTitle
			)
		);

		if ( review.explanation ) {
			feedbackEl.appendChild( this.createEl( 'div', '', review.explanation ) );
		}

		card.appendChild( feedbackEl );

		this.elements.msgList.appendChild( card );
	}

	renderQuizState() {
		const oldActiveQuizCard = this.elements.msgList.querySelector( '.lp-ai-assistant__quiz-card--active' );
		if ( oldActiveQuizCard ) {
			oldActiveQuizCard.remove();
		}

		if ( ! this.activeQuizState || ! this.activeQuizState.questions ) {
			this.setQuizInputMode( false );
			return;
		}

		const quiz = this.activeQuizState;
		const review = this.getQuizReviewFromState( quiz );
		if ( review ) {
			if ( this.pushQuizReviewToHistory( review ) ) {
				this.appendQuizReviewCard( review );
			}
		}

		if ( ! quiz.is_active ) {
			this.setQuizInputMode( false );
			return;
		}

		const currentIndex = Number.parseInt( quiz.current_index || 0, 10 );
		const question = quiz.questions?.[ currentIndex ];
		if ( ! question ) {
			this.setQuizInputMode( false );
			return;
		}

		const card = this.createEl( 'div', 'lp-ai-assistant__quiz-card lp-ai-assistant__quiz-card--active' );
		const options = Array.isArray( question.options ) ? question.options : [];

		card.appendChild(
			this.createEl(
				'div',
				'lp-ai-assistant__quiz-head',
				`Question ${ currentIndex + 1 }/${ quiz.total || options.length }`
			)
		);
		card.appendChild( this.createEl( 'div', 'lp-ai-assistant__quiz-question', question.question || '' ) );

		const optionsEl = this.createEl( 'div', 'lp-ai-assistant__quiz-options' );
		options.forEach( ( option, index ) => {
			const btn = this.buildQuizOption( option, index );

			// Assigned through dataset, never interpolated into an HTML attribute — model
			// output containing a quote cannot open a new attribute or event handler.
			btn.dataset.index = String( index );
			btn.dataset.option = String( option );

			optionsEl.appendChild( btn );
		} );
		card.appendChild( optionsEl );

		this.elements.msgList.appendChild( card );
		this.setQuizInputMode( true );
	}

	scrollToMessageStart( messageEl ) {
		const msgList = this.elements.msgList;
		if ( ! msgList || ! messageEl || ! msgList.contains( messageEl ) ) {
			return;
		}

		msgList.scrollTop = Math.max( 0, messageEl.offsetTop - 8 );
	}

	sendMessage( message, actionHint = '' ) {
		const text = ( message || '' ).trim();
		if ( this.isRequesting || ! text ) {
			return;
		}

		const ajaxHandle = this.getAjaxHandle();
		if ( ! ajaxHandle ) {
			this.appendMessage( 'assistant', this.config.i18n.sendError );
			return;
		}

		this.appendMessage( 'user', text );

		const contextHistory = this.history.slice();
		this.history.push( { role: 'user', content: text } );
		this.saveHistory();
		if ( this.elements.inputEl ) {
			this.elements.inputEl.value = '';
		}

		const pendingEl = this.appendMessage( 'assistant', this.config.i18n.thinking );
		const pendingTextEl = pendingEl.querySelector( '.lp-ai-assistant__msg-text' );
		this.setLoadingState( true );

		const dataSend = {
			action: 'openai_assistant_chat',
			message: text,
			// Composite item identity — all three legs are required server-side.
			course_id: this.config.courseId,
			item_type: this.config.itemType,
			item_id: this.config.itemId,
			history: contextHistory,
			active_quiz_questions: this.activeQuizState || [],
			action_hint: typeof actionHint === 'string' ? actionHint : '',
		};

		const callBack = {
			success: ( response ) => {
				if ( response?.status === 'success' && response?.data ) {
					pendingTextEl.textContent = response.data.message || '';

					if ( response?.data?.type === 'quiz' ) {
						this.activeQuizState = response?.data?.quiz || null;
						this.renderQuizState();

						const isQuizCompleted = !! this.activeQuizState?.completed || this.activeQuizState?.is_active === false;
						if ( isQuizCompleted && this.elements.msgList?.contains( pendingEl ) ) {
							// Keep completion feedback after the last review card.
							this.elements.msgList.appendChild( pendingEl );
						}
					} else {
						this.activeQuizState = null;
						this.renderQuizState();
					}

					this.history.push( { role: 'assistant', content: response.data.message } );
					this.saveHistory();
				} else {
					this.activeQuizState = null;
					this.renderQuizState();
					pendingTextEl.textContent = response?.message || this.config.i18n.sendError;
				}
			},
			error: () => {
				this.activeQuizState = null;
				this.renderQuizState();
				pendingTextEl.textContent = this.config.i18n.sendError;
			},
			completed: () => {
				this.setLoadingState( false );
				this.scrollToMessageStart( pendingEl );
			},
		};

		ajaxHandle.fetchAJAX( dataSend, callBack );
	}
}

const aiAssistantWidget = new AIAssistantWidget();
lpUtils.lpOnElementReady( AIAssistantWidget.selectors.root, () => {
	aiAssistantWidget.init();
} );
