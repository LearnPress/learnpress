import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';
import 'lpAssetsJsPath/admin/edit-quiz';

export class BuilderEditQuiz {
	constructor() {
		this.init();
	}

	static selectors = {
		elDataQuiz: '.cb-section__quiz-edit',
		elBtnUpdateQuiz: '.cb-btn-update__quiz',
		elBtnTrashQuiz: '.cb-btn-trash__quiz',
		elQuizStatus: '.quiz-status',
		idTitle: 'title',
		idDescEditor: 'quiz_description_editor',
		elFormSetting: '.lp-form-setting-quiz',
	};

	init() {
		this.events();
	}

	events() {
		if ( BuilderEditQuiz._loadedEvents ) {
			return;
		}
		BuilderEditQuiz._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: BuilderEditQuiz.selectors.elBtnUpdateQuiz,
				class: this,
				callBack: this.updateQuiz.name,
			},
			{
				selector: BuilderEditQuiz.selectors.elBtnTrashQuiz,
				class: this,
				callBack: this.trashQuiz.name,
			},
		] );
	}

	getQuizDataForUpdate() {
		const data = {};
		const wrapperEl = document.querySelector( BuilderEditQuiz.selectors.elDataQuiz );
		data.quiz_id = wrapperEl ? parseInt( wrapperEl.dataset.quizId ) || 0 : 0;

		const titleInput = document.getElementById( BuilderEditQuiz.selectors.idTitle );
		data.quiz_title = titleInput ? titleInput.value : '';

		const descEditor = document.getElementById( BuilderEditQuiz.selectors.idDescEditor );
		data.quiz_description = descEditor ? descEditor.value : '';

		if ( typeof tinymce !== 'undefined' ) {
			const editor = tinymce.get( BuilderEditQuiz.selectors.idDescEditor );
			if ( editor ) {
				data.quiz_description = editor.getContent();
			}
		}

		const elFormSetting = document.querySelector( BuilderEditQuiz.selectors.elFormSetting );

		if ( elFormSetting ) {
			data.quiz_settings = true;
			const formElements = elFormSetting.querySelectorAll( 'input, select, textarea' );

			formElements.forEach( ( element ) => {
				const name = element.name || element.id;

				if ( ! name || name === 'learnpress_meta_box_nonce' || name === '_wp_http_referer' ) {
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

	updateQuiz( args ) {
		const { target } = args;
		const elBtnUpdateQuiz = target.closest( BuilderEditQuiz.selectors.elBtnUpdateQuiz );

		if ( ! elBtnUpdateQuiz ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnUpdateQuiz, 1 );

		const quizData = this.getQuizDataForUpdate();

		const dataSend = {
			...quizData,
			action: 'update_quiz',
			args: {
				id_url: 'update-quiz',
			},
			quiz_status: 'publish',
		};

		if ( typeof lpQuizBuilder !== 'undefined' && lpQuizBuilder.nonce ) {
			dataSend.nonce = lpQuizBuilder.nonce;
		}

		if ( quizData.quiz_categories && quizData.quiz_categories.length > 0 ) {
			dataSend.quiz_categories = quizData.quiz_categories.join( ',' );
		}

		if ( quizData.quiz_tags && quizData.quiz_tags.length > 0 ) {
			dataSend.quiz_tags = quizData.quiz_tags.join( ',' );
		}

		if ( quizData.quiz_thumbnail_id ) {
			dataSend.quiz_thumbnail_id = quizData.quiz_thumbnail_id;
		}

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( data?.button_title ) {
					elBtnUpdateQuiz.textContent = data.button_title;
				}

				if ( data?.quiz_id_new ) {
					const currentUrl = window.location.href;
					window.location.href = currentUrl.replace( /post-new\/?/, `${ data.quiz_id_new }/` );
				}

				if ( data?.status ) {
					const elStatus = document.querySelector( BuilderEditQuiz.selectors.elQuizStatus );
					if ( elStatus ) {
						elStatus.className = 'quiz-status ' + data.status;
						elStatus.textContent = data.status;
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elBtnUpdateQuiz, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	trashQuiz( args ) {
		const { target } = args;
		const elBtnTrashQuiz = target.closest( BuilderEditQuiz.selectors.elBtnTrashQuiz );
		if ( ! elBtnTrashQuiz ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnTrashQuiz, 1 );

		const quizData = this.getQuizDataForUpdate();
		const dataSend = {
			action: 'move_trash_quiz',
			args: {
				id_url: 'move-trash-quiz',
			},
			quiz_id: quizData.quiz_id || 0,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( data?.button_title ) {
					const elBtnUpdateQuiz = document.querySelector(
						BuilderEditQuiz.selectors.elBtnUpdateQuiz
					);
					if ( elBtnUpdateQuiz ) {
						elBtnUpdateQuiz.textContent = data.button_title;
					}
				}

				if ( data?.status ) {
					const elStatus = document.querySelector( BuilderEditQuiz.selectors.elQuizStatus );
					if ( elStatus ) {
						elStatus.className = 'quiz-status ' + data.status;
						elStatus.textContent = data.status;
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elBtnTrashQuiz, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
}
