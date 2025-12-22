import * as lpUtils from 'lpAssetsJsPath/utils.js';
import * as lpToastify from 'lpAssetsJsPath/lpToastify.js';

export class BuilderEditLesson {
	constructor() {
		this.init();
	}

	static selectors = {
		elDataLesson: '.cb-section__lesson-edit',
		elBtnUpdateLesson: '.cb-btn-update__lesson',
		elBtnPublishLesson: '.cb-btn-publish__lesson',
		elBtnTrashLesson: '.cb-btn-trash__lesson',
		elLessonStatus: '.lesson-status',
		idTitle: 'title',
		idDescEditor: 'lesson_description_editor',
		elFormSetting: '.lp-form-setting-lesson',
	};

	init() {
		this.events();
	}

	events() {
		if ( BuilderEditLesson._loadedEvents ) {
			return;
		}
		BuilderEditLesson._loadedEvents = true;

		lpUtils.eventHandlers( 'click', [
			{
				selector: BuilderEditLesson.selectors.elBtnUpdateLesson,
				class: this,
				callBack: this.updateLesson.name,
			},
			{
				selector: BuilderEditLesson.selectors.elBtnTrashLesson,
				class: this,
				callBack: this.trashLesson.name,
			},
		] );
	}

	getLessonDataForUpdate() {
		const data = {};

		const wrapperEl = document.querySelector( BuilderEditLesson.selectors.elDataLesson );
		data.lesson_id = wrapperEl ? parseInt( wrapperEl.dataset.lessonId ) || 0 : 0;

		const titleInput = document.getElementById( BuilderEditLesson.selectors.idTitle );
		data.lesson_title = titleInput ? titleInput.value : '';

		const descEditor = document.getElementById( BuilderEditLesson.selectors.idDescEditor );
		data.lesson_description = descEditor ? descEditor.value : '';

		if ( typeof tinymce !== 'undefined' ) {
			const editor = tinymce.get( BuilderEditLesson.selectors.idDescEditor );
			if ( editor ) {
				data.lesson_description = editor.getContent();
			}
		}

		const elFormSetting = document.querySelector( BuilderEditLesson.selectors.elFormSetting );

		if ( elFormSetting ) {
			data.lesson_settings = true;
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

	updateLesson( args ) {
		const { target } = args;
		const elBtnUpdateLesson = target.closest( BuilderEditLesson.selectors.elBtnUpdateLesson );

		if ( ! elBtnUpdateLesson ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnUpdateLesson, 1 );

		const lessonData = this.getLessonDataForUpdate();

		const dataSend = {
			...lessonData,
			action: 'builder_update_lesson',
			args: {
				id_url: 'builder-update-lesson',
			},
			lesson_status: 'publish',
		};

		if ( typeof lpLessonBuilder !== 'undefined' && lpLessonBuilder.nonce ) {
			dataSend.nonce = lpLessonBuilder.nonce;
		}

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( data?.button_title ) {
					elBtnUpdateLesson.textContent = data.button_title;
				}

				if ( data?.lesson_id_new ) {
					const currentUrl = window.location.href;
					window.location.href = currentUrl.replace( /post-new\/?/, `${ data.lesson_id_new }/` );
				}

				if ( data?.status ) {
					const elStatus = document.querySelector( BuilderEditLesson.selectors.elLessonStatus );
					if ( elStatus ) {
						elStatus.className = 'lesson-status ' + data.status;
						elStatus.textContent = data.status;
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elBtnUpdateLesson, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}

	trashLesson( args ) {
		const { target } = args;
		const elBtnTrashLesson = target.closest( BuilderEditLesson.selectors.elBtnTrashLesson );

		if ( ! elBtnTrashLesson ) {
			return;
		}

		lpUtils.lpSetLoadingEl( elBtnTrashLesson, 1 );

		const lessonData = this.getLessonDataForUpdate();
		const dataSend = {
			action: 'move_trash_lesson',
			args: {
				id_url: 'move-trash-lesson',
			},
			lesson_id: lessonData.lesson_id,
		};

		const callBack = {
			success: ( response ) => {
				const { status, message, data } = response;
				lpToastify.show( message, status );

				if ( data?.button_title ) {
					const elBtnUpdateLesson = document.querySelector(
						BuilderEditLesson.selectors.elBtnUpdateLesson
					);
					if ( elBtnUpdateLesson ) {
						elBtnUpdateLesson.textContent = data.button_title;
					}
				}

				if ( data?.status ) {
					const elStatus = document.querySelector( BuilderEditLesson.selectors.elLessonStatus );
					if ( elStatus ) {
						elStatus.className = 'lesson-status ' + data.status;
						elStatus.textContent = data.status;
					}
				}
			},
			error: ( error ) => {
				lpToastify.show( error.message || error, 'error' );
			},
			completed: () => {
				lpUtils.lpSetLoadingEl( elBtnTrashLesson, 0 );
			},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
}
