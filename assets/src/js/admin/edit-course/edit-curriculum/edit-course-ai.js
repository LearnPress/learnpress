/**
 *
 * @since 4.2.8.6
 * @version 1.3.2
 */

import TomSelect from 'tom-select';
import Swal from 'sweetalert2';
import * as sectionEdit from './edit-section.js';
import * as lpEditCurriculumShare from './share.js';
import {
	addItemToSection,
	className as itemClassName,
} from './edit-section-item.js';

const buildSelectOptions = ( options, defaultValue = '' ) => {
	let html = `<option value="">${ defaultValue }</option>`;
	for ( const [ key, value ] of Object.entries( options ) ) {
		html += `<option value="${ key }">${ value }</option>`;
	}
	return html;
};

export class CourseAI {
	constructor() {
		this.init();
	}

	showPopupCreateTitle() {
		const modalTemplate = document.querySelector(
			'#lp-ai-title-modal-template'
		);

		if ( ! modalTemplate ) {
			console.error( 'AI Title Modal Template not found!' );
			return;
		}
		const modalHtml = modalTemplate.innerHTML;

		Swal.fire( {
			title: lpDataAdmin.i18n.createCourseTitle,
			html: modalHtml,
			showConfirmButton: true,
			confirmButtonText: lpDataAdmin.i18n.generate,
			customClass: {
				popup: 'create-course-modal',
				confirmButton: 'generate-button',
				actions: 'input-section',
			},
			width: '80%',
			showCloseButton: true,
			didOpen: () => {
				const audienceSelect = new TomSelect( '#swal-audience', {
					plugins: [ 'remove_button' ],
				} );
				const toneSelect = new TomSelect( '#swal-tone', {
					plugins: [ 'remove_button' ],
				} );
				const languageSelect = new TomSelect( '#swal-language', {} );

				try {
					const savedAudience =
						localStorage.getItem( 'lp_ai_audience' );
					if ( savedAudience ) {
						audienceSelect.setValue( JSON.parse( savedAudience ) );
					}

					const savedTone = localStorage.getItem( 'lp_ai_tone' );
					if ( savedTone ) {
						toneSelect.setValue( JSON.parse( savedTone ) );
					}

					const savedLang = localStorage.getItem( 'lp_ai_lang' );
					if ( savedLang ) {
						languageSelect.setValue( savedLang );
					}
				} catch ( e ) {
					console.error(
						'Lỗi khi tải cài đặt AI từ localStorage:',
						e
					);
				}

				const actionsContainer = Swal.getActions();
				const inputSection = Swal.getPopup().querySelector(
					'.outputs-control-content'
				);
				if ( actionsContainer && inputSection ) {
					inputSection.appendChild( actionsContainer );
				}
			},
			preConfirm: () => {
				const popup = Swal.getPopup();
				this.toggleLoading( 'show', popup );
				this.toggleBtnActionGenerate( 'hide', popup );
				const formData = {
					topic: popup.querySelector( '#swal-course-topic' ).value,
					goal: popup.querySelector( '#swal-course-goals' ).value,
					audience:
						popup
							.querySelector( '#swal-audience' )
							?.tomselect?.getValue() ?? [],
					tone: popup
						.querySelector( '#swal-tone' )
						?.tomselect?.getValue(),
					lang: [
						popup
							.querySelector( '#swal-language' )
							?.tomselect?.getValue(),
					],
					outputs: parseInt(
						popup.querySelector( '#lp-ai-output-count' ).value,
						10
					),
					characters: popup.querySelector(
						'#lp-ai-course-title-characters'
					).value,
				};

				try {
					localStorage.setItem(
						'lp_ai_audience',
						JSON.stringify( formData.audience )
					);
					localStorage.setItem(
						'lp_ai_tone',
						JSON.stringify( formData.tone )
					);
					localStorage.setItem( 'lp_ai_lang', formData.lang );
				} catch ( e ) {
					console.error(
						'Lỗi khi lưu cài đặt AI vào localStorage:',
						e
					);
				}

				this.generateContent(
					'course-title',
					formData,
					this.showResultPopup,
					this.applyTitleAI
				).catch( ( err ) => {
					Swal.showValidationMessage(
						`Request failed: ${ err.message }`
					);
				} );
				return false;
			},
		} ).then( ( result ) => {} );
	}

	applyTitleAI( e, { text } ) {
		const titleNode = document.querySelector( '#post-body-content #title' );
		const titleLabelNode = document.querySelector(
			'#post-body-content #title-prompt-text'
		);
		if ( titleLabelNode ) {
			titleLabelNode.classList.add( 'screen-reader-text' );
		}
		if ( titleNode ) {
			titleNode.value = text.trim();
			Swal.fire( {
				title: lpDataAdmin.i18n.apply,
				icon: 'success',
				timer: 1000,
				showConfirmButton: false,
			} );
		}
	}

	// For Description
	showPopupCreateDescription() {
		const modalTemplate = document.querySelector(
			'#lp-ai-description-modal-template'
		);

		if ( ! modalTemplate ) {
			console.error( 'AI Description Modal Template not found!' );
			return;
		}
		const modalHtml = modalTemplate.innerHTML;

		Swal.fire( {
			title: lpDataAdmin.i18n.createCourseDescription,
			html: modalHtml,
			showConfirmButton: true,
			confirmButtonText: lpDataAdmin.i18n.generate,
			customClass: {
				popup: 'create-course-modal',
				confirmButton: 'generate-button',
				actions: 'input-section',
			},
			width: '80%',
			showCloseButton: true,
			didOpen: () => {
				const audienceSelect = new TomSelect( '#swal-audience', {
					plugins: [ 'remove_button' ],
				} );
				const toneSelect = new TomSelect( '#swal-tone', {
					plugins: [ 'remove_button' ],
				} );
				const languageSelect = new TomSelect( '#swal-language', {} );

				try {
					const savedAudience =
						localStorage.getItem( 'lp_ai_audience' );
					if ( savedAudience ) {
						audienceSelect.setValue( JSON.parse( savedAudience ) );
					}

					const savedTone = localStorage.getItem( 'lp_ai_tone' );
					if ( savedTone ) {
						toneSelect.setValue( JSON.parse( savedTone ) );
					}

					const savedLang = localStorage.getItem( 'lp_ai_lang' );
					if ( savedLang ) {
						languageSelect.setValue( savedLang );
					}
				} catch ( e ) {
					console.error(
						'Lỗi khi tải cài đặt AI từ localStorage:',
						e
					);
				}

				const actionsContainer = Swal.getActions();
				const inputSection = Swal.getPopup().querySelector(
					'.outputs-control-content'
				);
				if ( actionsContainer && inputSection ) {
					inputSection.appendChild( actionsContainer );
				}
			},
			preConfirm: () => {
				const popup = Swal.getPopup();
				//show loading
				this.toggleLoading( 'show', popup );
				this.toggleBtnActionGenerate( 'hide', popup );

				const titleCourse = document.querySelector(
					'#post-body-content #title'
				);
				const formData = {
					topic: popup.querySelector( '#swal-course-desc' ).value,
					audience:
						popup
							.querySelector( '#swal-audience' )
							?.tomselect?.getValue() ?? [],
					tone: popup
						.querySelector( '#swal-tone' )
						?.tomselect?.getValue(),
					lang: [
						popup
							.querySelector( '#swal-language' )
							?.tomselect?.getValue(),
					],
					outputs: parseInt(
						popup.querySelector( '#lp-ai-output-count' ).value,
						10
					),
					title: titleCourse.value ?? '',
					characters: popup.querySelector(
						'#lp-ai-course-desc-characters'
					).value,
				};

				try {
					localStorage.setItem(
						'lp_ai_audience',
						JSON.stringify( formData.audience )
					);
					localStorage.setItem(
						'lp_ai_tone',
						JSON.stringify( formData.tone )
					);
					localStorage.setItem( 'lp_ai_lang', formData.lang );
				} catch ( e ) {
					console.error(
						'Lỗi khi lưu cài đặt AI vào localStorage:',
						e
					);
				}

				this.generateContent(
					'course-description',
					formData,
					this.showResultPopup,
					this.applyDescriptionAI
				).catch( ( err ) => {
					Swal.showValidationMessage(
						`Request failed: ${ err.message }`
					);
				} );
				return false;
			},
		} ).then( ( result ) => {} );
	}

	setWPEditorContent( htmlContent ) {
		if (
			window.wp &&
			window.wp.data &&
			window.wp.data.dispatch( 'core/block-editor' )
		) {
			const { createBlock } = window.wp.blocks;
			const { dispatch } = window.wp.data;

			const newBlock = createBlock( 'core/html', {
				content: htmlContent,
			} );
			dispatch( 'core/block-editor' ).resetBlocks( [ newBlock ] );
		} else if ( window.tinymce && window.tinymce.get( 'content' ) ) {
			const editor = window.tinymce.get( 'content' );
			editor.setContent( htmlContent );
		} else {
			const rawEditor = document.getElementById( 'content' );
			if ( rawEditor ) {
				rawEditor.value = htmlContent;
			} else {
				console.warn(
					'Không tìm thấy trình soạn thảo nào (Block, Classic, hoặc textarea#content).'
				);
			}
		}
	}

	getWPEditorContent() {
		if (
			window.wp &&
			window.wp.data &&
			window.wp.data.select( 'core/editor' )
		) {
			return window.wp.data
				.select( 'core/editor' )
				.getEditedPostContent();
		} else if ( window.tinymce && window.tinymce.get( 'content' ) ) {
			const editor = window.tinymce.get( 'content' );
			return editor.getContent();
		}
		const rawEditor = document.getElementById( 'content' );
		if ( rawEditor ) {
			return rawEditor.value;
		}

		return '';
	}

	applyDescriptionAI( e, { text } ) {
		this.setWPEditorContent( text.trim() );
		Swal.fire( {
			title: lpDataAdmin.i18n.apply,
			icon: 'success',
			timer: 1000,
			showConfirmButton: false,
		} );
	}

	// For Feature Image
	showPopupFeatureImage() {
		const modalTemplate = document.querySelector(
			'#lp-ai-course-feature-image-modal-template'
		);

		if ( ! modalTemplate ) {
			console.error( 'AI Feature Image Modal Template not found!' );
			return;
		}
		const modalHtml = modalTemplate.innerHTML;

		Swal.fire( {
			title: lpDataAdmin.i18n.createFeaturedImage,
			html: modalHtml,
			showConfirmButton: true,
			confirmButtonText: lpDataAdmin.i18n.generate,
			customClass: {
				popup: 'create-course-modal',
				confirmButton: 'generate-button',
				actions: 'input-section',
			},
			width: '1000px',
			showCloseButton: true,
			didOpen: () => {
				const imageStyle = new TomSelect( '#lp-ai-image-style', {
					plugins: [ 'remove_button' ],
				} );
				const imageSize = new TomSelect( '#lp-ai-image-size', {
					plugins: [ 'remove_button' ],
				} );
				const elImageQuality = document.querySelector(
					'#lp-ai-image-quality'
				);
				let imageQuality;
				if ( elImageQuality ) {
					imageQuality = new TomSelect( '#lp-ai-image-quality', {
						plugins: [ 'remove_button' ],
					} );
				}

				try {
					const savedImageStyle =
						localStorage.getItem( 'lp_ai_image_style' );
					if ( savedImageStyle ) {
						imageStyle.setValue( JSON.parse( savedImageStyle ) );
					}

					const savedImageSize =
						localStorage.getItem( 'lp_ai_image_size' );
					if ( savedImageSize ) {
						imageSize.setValue( savedImageSize );
					}

					const savedImageQuality = localStorage.getItem(
						'lp_ai_image_quality'
					);
					if ( savedImageQuality ) {
						if ( elImageQuality ) {
							imageQuality.setValue( savedImageQuality );
						}
					}
				} catch ( e ) {
					console.error(
						'Lỗi khi tải cài đặt AI từ localStorage:',
						e
					);
				}

				const actionsContainer = Swal.getActions();
				const inputSection = Swal.getPopup().querySelector(
					'.outputs-control-content'
				);
				if ( actionsContainer && inputSection ) {
					inputSection.appendChild( actionsContainer );
				}
			},
			preConfirm: () => {
				const popup = Swal.getPopup();
				//show loading
				this.toggleLoading( 'show', popup );
				this.toggleBtnActionGenerate( 'hide', popup );

				const titleCourse = document.querySelector(
					'#post-body-content #title'
				);
				const descriptionCourse = document.querySelector(
					'#post-body-content #content'
				);
				const prompt = popup.querySelector(
					'#lp-ai-output-prompt-desc'
				).value;
				const maskLogoInput = popup.querySelector( '#lp-ai-mask-logo' );
				const formData = {
					topic: popup.querySelector( '#lp-ai-image-desc' ).value,
					audience:
						popup
							.querySelector( '#swal-audience' )
							?.tomselect?.getValue() ?? [],
					outputs: parseInt(
						popup.querySelector( '#lp-ai-output-count' ).value,
						10
					),
					title: titleCourse.value ?? '',
					style:
						popup
							.querySelector( '#lp-ai-image-style' )
							?.tomselect?.getValue() ?? [],
					size:
						popup
							.querySelector( '#lp-ai-image-size' )
							?.tomselect?.getValue() ?? [],
					quality:
						popup
							.querySelector( '#lp-ai-image-quality' )
							?.tomselect?.getValue() ?? [],
					description: descriptionCourse.value ?? '',
					maskLogo:
						maskLogoInput && maskLogoInput.files.length > 0
							? maskLogoInput.files[ 0 ]
							: null,
					post_id: document.querySelector( '#post_ID' )?.value || 0,
				};
				if ( prompt ) {
					formData.prompt = prompt;
				}

				try {
					localStorage.setItem(
						'lp_ai_image_style',
						JSON.stringify( formData.style )
					);
					localStorage.setItem( 'lp_ai_image_size', formData.size );
					localStorage.setItem(
						'lp_ai_image_quality',
						formData.quality
					);
				} catch ( e ) {
					console.error(
						'Lỗi khi lưu cài đặt AI vào localStorage:',
						e
					);
				}

				this.generateContent(
					'course-feature-image',
					formData,
					this.showImageResultsPopup,
					this.applyFeatureImageAI
				).catch( ( err ) => {
					Swal.showValidationMessage(
						`Request failed: ${ err.message }`
					);
				} );
				return false;
			},
		} ).then( ( result ) => {} );
	}

	showImageResultsPopup( type, callbackData, applyCallback ) {
		const { prompt, content, extraData } = callbackData;

		const promptTextarea = document.querySelector(
			'#lp-ai-output-prompt-desc'
		);

		if ( promptTextarea && prompt ) {
			promptTextarea.value = prompt;

			// add btn re-generate
			const outputPromptElm = document.querySelector(
				'#lp-ai-output-prompt'
			);
			if ( ! document.querySelector( '#reGenerateBtn' ) ) {
				const reBtn = document.createElement( 'button' );
				reBtn.textContent = 'Re-generate';
				reBtn.id = 'reGenerateBtn';
				reBtn.classList.add( 'generate-button' );
				reBtn.addEventListener( 'click', () => {
					const popup = Swal.getPopup();
					//show loading
					this.toggleLoading( 'show', popup );
					this.toggleBtnActionGenerate( 'hide', popup );
					const formData = {
						outputs: parseInt(
							popup.querySelector( '#lp-ai-output-count' ).value,
							10
						),
						prompt: promptTextarea.value,
					};
					this.generateContent(
						type,
						formData,
						this.showResultPopup,
						applyCallback
					).catch( ( err ) => {
						Swal.showValidationMessage(
							`Request failed: ${ err.message }`
						);
					} );
				} );
				outputPromptElm.appendChild( reBtn );
			}
		}
		const resultsHtml = content
			.map(
				( url ) => `
			<div class="output-placeholder" style="background-image: url('${ url }');">
				<div class="output-actions">
					<button class="action-button apply-button" data-apply-url="${ url }">${ lpDataAdmin.i18n.apply }</button>
					<button class="action-button copy-button" data-apply-url="${ url }">${ lpDataAdmin.i18n.copy }</button>
				</div>
			</div>
		`
			)
			.join( '' );

		const suggestionContainer = document.querySelector(
			'#lp-ai-output-suggestion'
		);
		if ( suggestionContainer ) {
			suggestionContainer.innerHTML = resultsHtml;
			suggestionContainer
				.querySelectorAll( '.apply-button' )
				.forEach( ( button ) => {
					button.addEventListener( 'click', ( e ) => {
						const target = e.currentTarget;
						const imageUrl =
							target.getAttribute( 'data-apply-url' );
						const applyData = { imageUrl, extraData };
						applyCallback.call( this, e, applyData );
					} );
				} );

			suggestionContainer
				.querySelectorAll( '.copy-button' )
				.forEach( ( button ) => {
					button.addEventListener( 'click', ( e ) => {
						const textToCopy = e.currentTarget
							.closest( '.output-item' )
							.querySelector( 'textarea' ).value;
						navigator.clipboard
							.writeText( textToCopy )
							.then( () => {
								Swal.fire( {
									title: lpDataAdmin.i18n.copy,
									icon: 'success',
									timer: 1000,
									showConfirmButton: false,
								} );
							} );
					} );
				} );
		}

		//show loading
		this.toggleLoading( 'hide', Swal.getPopup() );
		this.toggleBtnActionGenerate( 'show', Swal.getPopup() );
	}

	applyFeatureImageAI( e, applyData ) {
		Swal.fire( {
			title: 'Apply upload image ...',
			allowOutsideClick: false,
			didOpen: () => Swal.showLoading(),
		} );

		const post_id = document.querySelector( '#post_ID' )?.value || 0;

		const formData = new FormData();
		formData.append( 'action', 'lp_ajax' );
		formData.append( 'lp-load-ajax', 'save_feature_image' );
		formData.append( 'nonce', lpDataAdmin.nonce );
		formData.append( 'image_url', applyData.imageUrl );
		formData.append( 'post_id', post_id );

		fetch( lpDataAdmin.lpAjaxUrl, {
			method: 'POST',
			body: formData,
		} )
			.then( ( response ) => response.json() )
			.then( ( res ) => {
				if ( res.success ) {
					Swal.fire( {
						title: lpDataAdmin.i18n.applied,
						text: 'Reloading page...',
						icon: 'success',
						timer: 1500,
						showConfirmButton: false,
					} ).then( () => location.reload() );
				} else {
					Swal.fire(
						'Error',
						res.message || 'Unknown error',
						'error'
					);
				}
			} )
			.catch( ( err ) => {
				Swal.fire( 'Error', err.message, 'error' );
			} );
	}

	// For Curriculum
	showPopupCreateCurriculum() {
		const modalTemplate = document.querySelector(
			'#lp-ai-course-curriculum-modal-template'
		);

		if ( ! modalTemplate ) {
			console.error( 'AI curriculum Modal Template not found!' );
			return;
		}
		const modalHtml = modalTemplate.innerHTML;
		Swal.fire( {
			title: lpDataAdmin.i18n.createCourseCurriculum,
			html: modalHtml,
			confirmButtonText: lpDataAdmin.i18n.generate,
			showConfirmButton: true,
			showCancelButton: true,
			customClass: {
				popup: 'create-course-modal',
				confirmButton: 'generate-button',
				actions: 'input-section',
			},
			width: '80%',
			showCloseButton: true,
			didOpen: () => {
				const titleCourse = document.querySelector(
					'#post-body-content #title'
				).value;
				const descriptionCourse = this.getWPEditorContent();
				const titleInput = document.querySelector(
					'#swal-curriculum-title'
				);
				const descriptionInput = document.querySelector(
					'#swal-curriculum-description'
				);

				const levelSelect = new TomSelect( '#swal-levels', {
					plugins: [ 'remove_button' ],
				} );
				const languageSelect = new TomSelect( '#swal-language', {} );

				try {
					const savedLevel = localStorage.getItem( 'lp_ai_level' );
					if ( savedLevel ) {
						levelSelect.setValue( savedLevel );
					}

					const savedLang = localStorage.getItem( 'lp_ai_lang' );
					if ( savedLang ) {
						languageSelect.setValue( savedLang );
					}
				} catch ( e ) {
					console.error(
						'Lỗi khi tải cài đặt AI từ localStorage:',
						e
					);
				}

				if ( ! titleCourse || ! descriptionCourse ) {
					const notice = document.querySelector(
						'#lp-ai-course-curriculum-notice'
					);
					if ( notice ) {
						notice.style.display = 'block';
					}
				} else {
					//add value title and description
					titleInput.value = titleCourse;
					descriptionInput.value = descriptionCourse;
				}
			},
			preConfirm: () => {
				const popup = Swal.getPopup();

				//show loading
				this.toggleLoading( 'show', popup );
				this.toggleBtnActionGenerate( 'hide', popup );

				const titleCourse = document.querySelector(
					'#post-body-content #title'
				);
				const descriptionCourse = this.getWPEditorContent();
				const prompt = popup.querySelector(
					'#lp-ai-output-prompt-desc'
				).value;

				if ( ! titleCourse.value || ! descriptionCourse ) {
					lpEditCurriculumShare.showToast(
						'No title/description found. Please go back and generate them' +
							' first. Back to Generate Title & Description',
						'error'
					);
					return;
				}
				const formData = {
					topic: popup.querySelector( '#swal-curriculum-topics' )
						.value,
					audience:
						popup
							.querySelector( '#swal-audience' )
							?.tomselect?.getValue() ?? [],
					level: popup
						.querySelector( '#swal-levels' )
						?.tomselect?.getValue(),
					lang: [
						popup
							.querySelector( '#swal-language' )
							?.tomselect?.getValue(),
					],
					outputs: parseInt(
						popup.querySelector( '#lp-ai-output-count' ).value,
						10
					),
					type: 'course-curriculum',
					section_number:
						popup.querySelector( '#swal-curriculum-sections' )
							.value ?? '',
					quiz_number:
						popup.querySelector( '#swal-curriculum-quiz' ).value ??
						'',
					less_per_section:
						popup.querySelector( '#swal-curriculum-lessons' )
							.value ?? '',
					question_per_quiz:
						popup.querySelector( '#swal-curriculum-questions' )
							.value ?? '',
					title: titleCourse.value ?? '',
					description: descriptionCourse ?? '',
				};
				if ( prompt ) {
					formData.prompt = prompt;
				}

				try {
					localStorage.setItem( 'lp_ai_level', formData.level );
					localStorage.setItem( 'lp_ai_lang', formData.lang );
				} catch ( e ) {
					console.error(
						'Lỗi khi lưu cài đặt AI vào localStorage:',
						e
					);
				}

				this.generateContent(
					'course-curriculum',
					formData,
					this.showResultPopup,
					this.applyCurriculumAI
				).catch( ( err ) => {
					Swal.showValidationMessage(
						`Request failed: ${ err.message }`
					);
				} );
				return false;
			},
		} ).then( ( result ) => {} );
	}

	applyCurriculumAI( e, dataJSON ) {
		const selectedCurriculum = dataJSON.extraData[ dataJSON.index ];
		const elEditCurriculum = document.querySelector(
			lpEditCurriculumShare.className.idElEditCurriculum
		);
		if ( ! elEditCurriculum ) {
			return;
		}
		const dataSend = window.lpAJAXG.getDataSetCurrent(
			document.querySelector( lpEditCurriculumShare.className.LPTarget )
		);

		lpEditCurriculumShare.setVariables( {
			elEditCurriculum,
			courseId: dataSend.args.course_id,
			elCurriculumSections: elEditCurriculum.querySelector(
				lpEditCurriculumShare.className.elCurriculumSections
			),
		} );
		sectionEdit.init();

		if ( ! selectedCurriculum || ! selectedCurriculum.sections ) {
			return;
		}

		const sectionsToCreate = selectedCurriculum.sections;

		if ( sectionsToCreate.length === 0 ) {
			return;
		}

		Swal.fire( {
			title: 'Curriculum handle ....',
			allowOutsideClick: false,
			didOpen: () => {
				Swal.showLoading();
			},
		} );

		const processSectionAtIndex = ( index ) => {
			if ( index >= sectionsToCreate.length ) {
				Swal.fire( {
					title: 'Successfully!',
					icon: 'success',
					timer: 1500,
					showConfirmButton: false,
				} );
				return;
			}

			const sectionData = sectionsToCreate[ index ];
			const mainTarget = document.querySelector( '.lp-btn-add-section' );

			const callBackAddItems = {
				success: ( elNewSection ) => {
					const elAddItemTypeClone = elNewSection.querySelector(
						`${ itemClassName.elAddItemTypeClone }`
					);
					const sectionActions = elNewSection.querySelector(
						`${ itemClassName.elSectionActions }`
					);

					if (
						sectionData.section_description &&
						sectionData.section_description.trim() !== ''
					) {
						const elSectionDesInput = elNewSection.querySelector(
							'.lp-section-description-input'
						);
						const elBtnUpdateDes = elNewSection.querySelector(
							'.lp-btn-update-section-description'
						);

						if ( elSectionDesInput && elBtnUpdateDes ) {
							elSectionDesInput.value =
								sectionData.section_description;
							elBtnUpdateDes.click();
						}
					}

					if ( ! elAddItemTypeClone ) {
						processSectionAtIndex( index + 1 );
						return;
					}
					if ( ! sectionActions ) {
						processSectionAtIndex( index + 1 );
						return;
					}

					const lessons = sectionData.lessons || [];
					const quizzes = sectionData.quizzes || [];

					let itemPromiseChain = Promise.resolve();

					lessons.forEach( ( lesson ) => {
						itemPromiseChain = itemPromiseChain.then(
							() =>
								new Promise( ( resolve ) => {
									const elTempAddItem =
										elAddItemTypeClone.cloneNode( true );
									elTempAddItem.classList.remove( 'clone' );
									elTempAddItem.style.display = 'none';

									const elTempInput =
										elTempAddItem.querySelector(
											`${ itemClassName.elAddItemTypeTitleInput }`
										);
									elTempInput.value = lesson.lesson_title;
									elTempInput.dataset.itemType = 'lp_lesson';

									const elTempBtnAdd =
										elTempAddItem.querySelector(
											`${ itemClassName.elBtnAddItem }`
										);

									sectionActions.insertAdjacentElement(
										'beforebegin',
										elTempAddItem
									);

									addItemToSection( e, elTempBtnAdd );

									setTimeout( resolve, 500 );
								} )
						);
					} );

					if ( quizzes ) {
						quizzes.forEach( ( quiz ) => {
							itemPromiseChain = itemPromiseChain.then(
								() =>
									new Promise( ( resolve ) => {
										const elTempAddItem =
											elAddItemTypeClone.cloneNode(
												true
											);
										elTempAddItem.classList.remove(
											'clone'
										);
										elTempAddItem.style.display = 'none';

										const elTempInput =
											elTempAddItem.querySelector(
												`${ itemClassName.elAddItemTypeTitleInput }`
											);
										elTempInput.value = quiz.quiz_title;
										elTempInput.dataset.itemType =
											'lp_quiz';

										const elTempBtnAdd =
											elTempAddItem.querySelector(
												`${ itemClassName.elBtnAddItem }`
											);

										sectionActions.insertAdjacentElement(
											'beforebegin',
											elTempAddItem
										);

										addItemToSection( e, elTempBtnAdd );

										setTimeout( resolve, 500 );

										//call ajax create question
									} )
							);
						} );
					}

					itemPromiseChain.then( () => {
						processSectionAtIndex( index + 1 );
					} );
				},
			};

			const elSectionTitleNewInput = document.querySelector(
				'.lp-section-title-new-input'
			);
			elSectionTitleNewInput.value = sectionData.section_title;
			sectionEdit.addSection( e, mainTarget, callBackAddItems );
		};

		processSectionAtIndex( 0 );
	}

	// Generic Content Generation using AJAX
	generateContent( type, formData, resultsCallback, applyCallback ) {
		const isImage = type === 'course-feature-image';
		const ajaxAction = isImage
			? 'create_course_feature_image'
			: 'generate_text';

		const data = new FormData();
		data.append( 'action', 'lp_ajax' );
		data.append( 'lp-load-ajax', ajaxAction );
		data.append( 'nonce', lpDataAdmin.nonce );
		data.append( 'type', type );

		for ( const key in formData ) {
			if ( Array.isArray( formData[ key ] ) ) {
				formData[ key ].forEach( ( value ) =>
					data.append( `${ key }[]`, value )
				);
			} else {
				data.append( key, formData[ key ] );
			}
		}

		return fetch( lpDataAdmin.lpAjaxUrl, {
			method: 'POST',
			body: data,
		} )
			.then( ( response ) => response.json() )
			.then( ( res ) => {
				if ( res.success ) {
					const responseData = res.data;
					const callbackData = {
						prompt: responseData.prompt,
						content: isImage
							? responseData.urls
							: responseData.content,
						extraData: isImage ? null : responseData.sections,
					};
					resultsCallback.call(
						this,
						type,
						callbackData,
						applyCallback
					);
				} else {
					Swal.fire(
						'Error',
						res.message || 'Unknown error',
						'error'
					);
				}
			} )
			.catch( ( err ) => {
				Swal.fire( 'Error', err.message, 'error' );
			} );
	}

	generatePromptFullCourse( formData, promptPreview, generateCourseBtn ) {
		const ajaxAction = 'generate_full_course';

		const data = new FormData();
		data.append( 'action', 'lp_ajax' );
		data.append( 'lp-load-ajax', ajaxAction );
		data.append( 'nonce', lpDataAdmin.nonce );
		data.append( 'type', 'generate-full-course' );

		for ( const key in formData ) {
			if ( Array.isArray( formData[ key ] ) ) {
				formData[ key ].forEach( ( value ) =>
					data.append( `${ key }[]`, value )
				);
			} else {
				data.append( key, formData[ key ] );
			}
		}

		return fetch( lpDataAdmin.lpAjaxUrl, {
			method: 'POST',
			body: data,
		} )
			.then( ( response ) => response.json() )
			.then( ( res ) => {
				if ( res.success ) {
					//hide loading
					document.querySelector(
						'#generate-prompt-btn-loading'
					).style.display = 'none';
					promptPreview.value = res.data.prompt;
					generateCourseBtn.disabled = false;
					generateCourseBtn.classList.replace(
						'btn-secondary',
						'btn-primary'
					);
				} else {
					console.log( res.message || 'Unknown error' );
				}
			} )
			.catch( ( err ) => {
				Swal.fire( 'Error', err.message, 'error' );
			} );
	}

	generateFullCourse( formData ) {
		const data = new FormData();
		data.append( 'action', 'lp_ajax' );
		data.append( 'lp-load-ajax', 'generate_full_course' );
		data.append( 'nonce', lpDataAdmin.nonce );
		data.append( 'type', 'generate-full-course' );

		for ( const key in formData ) {
			if ( Array.isArray( formData[ key ] ) ) {
				formData[ key ].forEach( ( value ) =>
					data.append( `${ key }[]`, value )
				);
			} else {
				data.append( key, formData[ key ] );
			}
		}

		return fetch( lpDataAdmin.lpAjaxUrl, {
			method: 'POST',
			body: data,
		} )
			.then( ( response ) => response.json() )
			.catch( ( err ) => {
				console.log( err.message );
			} );
	}

	approveSaveCourse( formData ) {
		Swal.fire( {
			title: 'Handle Approve Save Course!',
			allowOutsideClick: false,
			didOpen: () => {
				Swal.showLoading();
			},
		} );

		const data = new FormData();
		data.append( 'action', 'lp_ajax' );
		data.append( 'lp-load-ajax', 'save_course' );
		data.append( 'nonce', lpDataAdmin.nonce );

		for ( const key in formData ) {
			if ( Array.isArray( formData[ key ] ) ) {
				formData[ key ].forEach( ( value ) =>
					data.append( `${ key }[]`, value )
				);
			} else {
				data.append( key, formData[ key ] );
			}
		}

		return fetch( lpDataAdmin.lpAjaxUrl, {
			method: 'POST',
			body: data,
		} )
			.then( ( response ) => response.json() )
			.then( ( res ) => {
				if ( res.success ) {
					Swal.fire( {
						title: 'Successfully!',
						icon: 'success',
						timer: 1500,
						showConfirmButton: false,
					} ).then( () => {
						window.location.href = res.data.edit_link.replace(
							/&amp;/g,
							'&'
						);
					} );
				}
			} )
			.catch( ( err ) => {
				Swal.fire( {
					title: err.message,
					icon: 'error',
					timer: 1500,
					showConfirmButton: false,
				} );
				console.log( err.message );
			} );
	}

	// Generic Popups for Results
	showResultPopup( type, callbackData, applyCallback ) {
		const { prompt, content, extraData } = callbackData;

		const promptTextarea = document.querySelector(
			'#lp-ai-output-prompt-desc'
		);

		if ( promptTextarea && prompt ) {
			promptTextarea.value = prompt;

			// add btn re-generate
			const outputPromptElm = document.querySelector(
				'#lp-ai-output-prompt'
			);
			if (
				! document.querySelector( '#reGenerateBtn' ) &&
				type !== 'course-curriculum'
			) {
				const reBtn = document.createElement( 'button' );
				reBtn.textContent = 'Re-generate';
				reBtn.id = 'reGenerateBtn';
				reBtn.classList.add( 'generate-button' );
				reBtn.addEventListener( 'click', () => {
					const popup = Swal.getPopup();
					const formData = {
						outputs: parseInt(
							popup.querySelector( '#lp-ai-output-count' ).value,
							10
						),
						prompt: promptTextarea.value,
					};
					//show loading
					this.toggleLoading( 'show', Swal.getPopup() );
					this.toggleBtnActionGenerate( 'hide', Swal.getPopup() );

					this.generateContent(
						type,
						formData,
						this.showResultPopup,
						applyCallback
					).catch( ( err ) => {
						Swal.showValidationMessage(
							`Request failed: ${ err.message }`
						);
					} );
				} );
				outputPromptElm.appendChild( reBtn );
			}
		}
		if ( type === 'course-curriculum' ) {
			//hide notify and show
			document.querySelector( '#lp-ai-output-notify' ).style.display =
				'none';
			document.querySelector( '#lp-ai-output-suggestion' ).style.display =
				'inline';
		}

		const resultsHtml = content
			.map(
				( item, index ) => `
        <div class="output-item output-suggestion">
            <textarea>${ item }</textarea>
            <div class="output-actions">
                <button type="button" class="action-button copy-button">${ lpDataAdmin.i18n.copy }</button>
                <button type="button" class="action-button apply-button" data-index="${ index }">${ lpDataAdmin.i18n.apply }</button>
            </div>
        </div>
    `
			)
			.join( '' );

		const suggestionContainer = document.querySelector(
			'#lp-ai-output-suggestion'
		);
		if ( suggestionContainer ) {
			suggestionContainer.innerHTML = resultsHtml;
			suggestionContainer
				.querySelectorAll( '.apply-button' )
				.forEach( ( button ) => {
					button.addEventListener( 'click', ( e ) => {
						const target = e.currentTarget;
						const index = target.getAttribute( 'data-index' );
						const text = target
							.closest( '.output-item' )
							.querySelector( 'textarea' ).value;
						const applyData = { text, index, extraData };
						applyCallback.call( this, e, applyData );
					} );
				} );

			suggestionContainer
				.querySelectorAll( '.copy-button' )
				.forEach( ( button ) => {
					button.addEventListener( 'click', ( e ) => {
						const textToCopy = e.currentTarget
							.closest( '.output-item' )
							.querySelector( 'textarea' ).value;
						navigator.clipboard
							.writeText( textToCopy )
							.then( () => {
								lpEditCurriculumShare.showToast(
									'Copied',
									'success'
								);
							} );
					} );
				} );
		}
		this.toggleLoading( 'hide', Swal.getPopup() );
		this.toggleBtnActionGenerate( 'show', Swal.getPopup() );
	}

	toggleLoading( type, popup ) {
		const loadingEl = popup.querySelectorAll( '.fui-loading-spinner-3' );
		if ( loadingEl ) {
			loadingEl.forEach( ( e ) => {
				if ( type === 'show' ) {
					e.style.display = 'inline-block';
				} else {
					e.style.display = 'none';
				}
			} );
		}
	}

	toggleBtnActionGenerate( type, popup ) {
		const btnGenerateEl = popup.querySelector( '.swal2-actions' );
		if ( btnGenerateEl ) {
			if ( type === 'show' ) {
				btnGenerateEl.style.display = 'flex';
			} else {
				btnGenerateEl.style.display = 'none';
			}
		}
		const btnReGenerateEl = popup.querySelector( '#reGenerateBtn' );
		if ( btnReGenerateEl ) {
			if ( type === 'show' ) {
				btnReGenerateEl.style.display = 'inline-block';
			} else {
				btnReGenerateEl.style.display = 'none';
			}
		}
	}

	// Main Init and Events
	init() {
		this.tomSelect();
		this.addAIBtns();
		this.events();
	}

	tomSelect = () => {
		const tomSelectNodes = document.querySelectorAll(
			'select.lp-tom-select'
		);

		tomSelectNodes.forEach( ( tomSelectNode ) => {
			if ( tomSelectNode.tomselect ) {
				tomSelectNode.tomselect.destroy();
			}

			const settings = {
				maxOptions: null,
				plugins: tomSelectNode.multiple
					? [
						'no_backspace_delete',
						'remove_button',
						'dropdown_input',
						'change_listener',
					  ]
					: [ 'dropdown_input' ],
			};

			new TomSelect( tomSelectNode, settings );
		} );
	};

	addAIBtns() {
		const __ = wp.i18n.__ || ( ( text ) => text );

		const titleWrap = document.querySelector(
			'body.post-type-lp_course #titlewrap'
		);
		if (
			titleWrap &&
			! document.getElementById( 'lp-edit-ai-course-title' )
		) {
			titleWrap.insertAdjacentHTML(
				'afterend',
				`<button type="button" class="button" id="lp-edit-ai-course-title">${ __(
					'Edit with AI',
					'learnpress'
				) }</button>`
			);
		}

		const btnAddMedia = document.querySelector(
			'body.post-type-lp_course #insert-media-button'
		);
		if (
			btnAddMedia &&
			! document.getElementById( 'lp-edit-ai-course-description' )
		) {
			btnAddMedia.insertAdjacentHTML(
				'afterend',
				`<button type="button" class="button" id="lp-edit-ai-course-description">${ __(
					'Edit with AI',
					'learnpress'
				) }</button>`
			);
		}

		const btnAddFeatureImage = document.querySelector(
			'body.post-type-lp_course #set-post-thumbnail'
		);
		if (
			btnAddFeatureImage &&
			! document.getElementById( 'lp-edit-ai-course-feature-image' )
		) {
			btnAddFeatureImage.insertAdjacentHTML(
				'afterend',
				`<button type="button" class="button" id="lp-edit-ai-course-feature-image">${ __(
					'Edit with AI',
					'learnpress'
				) }</button>`
			);
		}

		const editorBox = document.querySelector( '#course-editor' );
		if ( editorBox ) {
			setTimeout( () => {
				if (
					! editorBox.querySelector( '#lp-edit-ai-course-curriculum' )
				) {
					const handleActions =
						editorBox.querySelector( '.handle-actions' );
					if ( handleActions ) {
						const btn = document.createElement( 'button' );
						btn.type = 'button';
						btn.className = 'button';
						btn.id = 'lp-edit-ai-course-curriculum';
						btn.textContent = __( 'Edit with AI', 'learnpress' );
						handleActions.prepend( btn );
					}
				}
			}, 1500 );
		}
	}

	events() {
		document.addEventListener( 'click', ( e ) => {
			const target = e.target;
			const actions = {
				'lp-edit-ai-course-title': this.showPopupCreateTitle,
				'lp-edit-ai-course-description':
					this.showPopupCreateDescription,
				'lp-edit-ai-course-feature-image': this.showPopupFeatureImage,
				'lp-edit-ai-course-curriculum': this.showPopupCreateCurriculum,
				'lp-course-ai': this.showPopupCreateFullCourse,
			};
			if ( actions[ target.id ] ) {
				e.preventDefault();
				actions[ target.id ].call( this );
			}
		} );
	}
}

document.addEventListener( 'DOMContentLoaded', () => {
	if (
		typeof lpDataAdmin.lpAi !== 'undefined' &&
		( lpDataAdmin.current_screen === 'edit-lp_course' ||
			lpDataAdmin.current_screen === 'lp_course' )
	) {
		new CourseAI();
	}
} );
