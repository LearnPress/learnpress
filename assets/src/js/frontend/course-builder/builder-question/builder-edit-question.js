import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import { EditQuestion } from 'lpAssetsJsPath/admin/edit-question.js';

export class BuilderEditQuestion {
	constructor() {
		this.editQuestion = null;
	}

	static selectors = {
		elDataQuestion: '.cb-section__question-edit',
		elBtnUpdateQuestion: '.cb-btn-update__question',
		elBtnTrashQuestion: '.cb-btn-trash__question',
		elQuestionStatus: '.question-status',
		idTitle: 'title',
		idDescEditor: 'question_description_editor',
		elFormSetting: '.lp-form-setting-question',
		// Question edit selectors
		elEditQuestionWrap: '.lp-edit-question-wrap',
		elQuestionEditMain: '.lp-question-edit-main',
	};

	init() {
		this.initQuestionAnswersSettings();
		this.events();
	}

	/**
	 * Initialize Question Answers Settings
	 * This will init EditQuestion class for the question answer management
	 */
	initQuestionAnswersSettings() {
		lpUtils.lpOnElementReady(
			BuilderEditQuestion.selectors.elQuestionEditMain,
			( elQuestionEditMain ) => {
				// Initialize EditQuestion for question answer editing
				if ( ! this.editQuestion ) {
					this.editQuestion = new EditQuestion();
					this.editQuestion.init();
				}

				// Init sortable for question answers
				if ( this.editQuestion ) {
					this.editQuestion.sortAbleQuestionAnswer( elQuestionEditMain );
				}
			}
		);
	}

	/**
	 * Re-initialize when question type changes
	 */
	reinitQuestionHandlers( elQuestionEditMain ) {
		if ( this.editQuestion && elQuestionEditMain ) {
			this.editQuestion.sortAbleQuestionAnswer( elQuestionEditMain );
			this.editQuestion.initTinyMCE();
		}
	}

	/**
	 * Re-initialize for popup context
	 * This is called when popup is opened multiple times to ensure
	 * TinyMCE and other handlers are properly re-initialized
	 * 
	 * @param {HTMLElement} container - The popup container element
	 */
	reinit( container ) {
		const elQuestionEditMain = container 
			? container.querySelector( BuilderEditQuestion.selectors.elQuestionEditMain )
			: document.querySelector( BuilderEditQuestion.selectors.elQuestionEditMain );

		if ( ! elQuestionEditMain ) {
			return;
		}

		// Re-create EditQuestion instance to ensure fresh initialization
		// This is necessary because TinyMCE instances were destroyed when popup closed
		if ( this.editQuestion ) {
			// Destroy existing TinyMCE instances in the container first
			if ( typeof tinymce !== 'undefined' && container ) {
				const textareas = container.querySelectorAll( 'textarea.lp-meta-box__editor' );
				textareas.forEach( ( textarea ) => {
					const editorId = textarea.id;
					if ( editorId ) {
						const editor = tinymce.get( editorId );
						if ( editor ) {
							editor.remove();
						}
						if ( typeof wp !== 'undefined' && wp.editor && wp.editor.remove ) {
							wp.editor.remove( editorId );
						}
					}
				} );
			}
		}

		// Create fresh EditQuestion instance
		this.editQuestion = new EditQuestion();
		this.editQuestion.init();

		// Re-init sortable and TinyMCE
		this.editQuestion.sortAbleQuestionAnswer( elQuestionEditMain );
		
		// Use setTimeout to ensure DOM is ready for TinyMCE
		setTimeout( () => {
			if ( this.editQuestion ) {
				this.editQuestion.initTinyMCE();
			}
		}, 100 );
	}

	events() {
		if ( BuilderEditQuestion._loadedEvents ) {
			return;
		}
		BuilderEditQuestion._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: BuilderEditQuestion.selectors.elBtnUpdateQuestion,
				class: this,
				callBack: this.updateQuestion.name,
			},
			{
				selector: BuilderEditQuestion.selectors.elBtnTrashQuestion,
				class: this,
				callBack: this.trashQuestion.name,
			},
		] );
	}

	getQuestionDataForUpdate() {
		const data = {};

		const wrapperEl = document.querySelector( BuilderEditQuestion.selectors.elDataQuestion );

		data.question_id = wrapperEl ? parseInt( wrapperEl.dataset.questionId ) || 0 : 0;

		const titleInput = document.getElementById( BuilderEditQuestion.selectors.idTitle );
		data.question_title = titleInput ? titleInput.value : '';

		const descEditor = document.getElementById( BuilderEditQuestion.selectors.idDescEditor );
		data.question_description = descEditor ? descEditor.value : '';

		if ( typeof tinymce !== 'undefined' ) {
			const editor = tinymce.get( BuilderEditQuestion.selectors.idDescEditor );
			if ( editor ) {
				data.question_description = editor.getContent();
			}
		}

		const elFormSetting = document.querySelector( BuilderEditQuestion.selectors.elFormSetting );

		if ( elFormSetting ) {
			data.question_settings = true;
			const formElements = elFormSetting.querySelectorAll( 'input, select, textarea' );

			formElements.forEach( ( element ) => {
				const name = element.name || element.id;

				if ( ! name ) {
					return;
				}

				if ( name === 'learnpress_meta_box_nonce' || name === '_wp_http_referer' ) {
					return;
				}

				if ( element.type === 'checkbox' ) {
					const fieldName = name.replace( '[]', '' );
					if ( ! data.hasOwnProperty( fieldName ) ) {
						data[ fieldName ] = element.checked ? 'yes' : 'no';
					}
				} else if ( element.type === 'radio' ) {
					if ( element.checked ) {
						const fieldName = name.replace( '[]', '' );
						data[ fieldName ] = element.value;
					}
				} else if ( element.type === 'file' ) {
					const fieldName = name.replace( '[]', '' );
					if ( element.files && element.files.length > 0 ) {
						data[ fieldName ] = element.files;
					}
				} else {
					const fieldName = name.replace( '[]', '' );

					if ( name.endsWith( '[]' ) ) {
						if ( ! data.hasOwnProperty( fieldName ) ) {
							data[ fieldName ] = [];
						}

						if ( Array.isArray( data[ fieldName ] ) ) {
							data[ fieldName ].push( element.value );
						}
					} else {
						if ( ! data.hasOwnProperty( fieldName ) ) {
							data[ fieldName ] = element.value;
						}
					}
				}
			} );

			Object.keys( data ).forEach( ( key ) => {
				if ( Array.isArray( data[ key ] ) ) {
					data[ key ] = data[ key ].join( ',' );
				}
			} );
		}

		return data;
	}

	updateQuestion( args ) {
		const { target } = args;
		const elBtnUpdateQuestion = target.closest( BuilderEditQuestion.selectors.elBtnUpdateQuestion );

		if ( ! elBtnUpdateQuestion ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnUpdateQuestion, 1 );

		const questionData = this.getQuestionDataForUpdate();

		const dataSend = {
			...questionData,
			action: 'builder_update_question',
			args: {
				id_url: 'builder-update-question',
			},
			question_status: 'publish',
		};

		if ( typeof lpQuestionBuilder !== 'undefined' && lpQuestionBuilder.nonce ) {
			dataSend.nonce = lpQuestionBuilder.nonce;
		}

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( data?.button_title ) {
					elBtnUpdateQuestion.textContent = data.button_title;
				}

				if ( data?.question_id_new ) {
					const currentUrl = window.location.href;
					window.location.href = currentUrl.replace( /post-new\/?/, `${ data.question_id_new }/` );
				}

				if ( data?.status ) {
					const elStatus = document.querySelector( BuilderEditQuestion.selectors.elQuestionStatus );
					if ( elStatus ) {
						elStatus.className = 'question-status ' + data.status;
						elStatus.textContent = data.status;
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elBtnUpdateQuestion, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	trashQuestion( args ) {
		const { target } = args;
		const elBtnTrashQuestion = target.closest( BuilderEditQuestion.selectors.elBtnTrashQuestion );
		if ( ! elBtnTrashQuestion ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnTrashQuestion, 1 );

		const questionData = this.getQuestionDataForUpdate();
		const dataSend = {
			action: 'move_trash_question',
			args: {
				id_url: 'move-trash-question',
			},
			question_id: questionData.question_id || 0,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( data?.button_title ) {
					const elBtnUpdateQuestion = document.querySelector(
						BuilderEditQuestion.selectors.elBtnUpdateQuestion
					);
					if ( elBtnUpdateQuestion ) {
						elBtnUpdateQuestion.textContent = data.button_title;
					}
				}

				if ( data?.status ) {
					const elStatus = document.querySelector( BuilderEditQuestion.selectors.elQuestionStatus );
					if ( elStatus ) {
						elStatus.className = 'question-status ' + data.status;
						elStatus.textContent = data.status;
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elBtnTrashQuestion, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
}
