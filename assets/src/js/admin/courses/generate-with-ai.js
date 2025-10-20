/**
 * Create course with AI
 */

import * as lpUtils from './../../utils.js';
import SweetAlert from 'sweetalert2';
import TomSelect from 'tom-select';

export class CreateCourseViaAI {
	constructor() {
		this.init();
	}
	init() {
		lpUtils.lpOnElementReady( '.page-title-action', ( el ) => {
			el.insertAdjacentHTML(
				'afterend',
				`<button type="button" class="lp-btn-generate-course-with-ai lp-button button-primary ">Generate with AI</button>`
			);
		} );

		this.events();
	}

	events() {
		document.addEventListener( 'click', ( e ) => {
			const target = e.target;
			if ( target.classList.contains( 'lp-btn-generate-course-with-ai' ) ) {
				this.showPopupCreateFullCourse();
			}
		} );
	}

	showPopupCreateFullCourse() {
		const modalTemplate = document.querySelector(
			'#lp-ai-course-modal-template'
		);

		if ( ! modalTemplate ) {
			console.error( 'AI Create Full Course Modal Template not found!' );
			return;
		}
		const modalHtml = modalTemplate.innerHTML;

		SweetAlert.fire( {
			title: lpDataAdmin.i18n.createFullCourse,
			html: modalHtml,
			showConfirmButton: false,
			confirmButtonText: lpDataAdmin.i18n.generate,
			customClass: {
				popup: 'create-full-course-modal',
				confirmButton: 'generate-button',
				actions: 'input-section',
			},
			width: '80%',
			showCloseButton: true,
			didOpen: () => {
				const popup = SweetAlert.getPopup();
				const steps = popup.querySelectorAll( '.step-content' );
				const stepperItems = popup.querySelectorAll( '.stepper-item' );
				const nextButtons = popup.querySelectorAll( '.next-btn' );
				const prevButtons = popup.querySelectorAll( '.prev-btn' );
				const generatePromptBtn = popup.querySelector(
					'#generate-prompt-btn'
				);
				const generateCourseBtn = popup.querySelector(
					'#generate-course-btn'
				);
				const approveCourseBtn = popup.querySelector(
					'#lp-ai-approve-course'
				);
				const regenerateCourseBtn = popup.querySelector(
					'#lp-ai-regenerate-course'
				);

				const promptPreview = popup.querySelector( '#prompt-preview' );

				let currentStep = 1;

				const updateSteps = () => {
					steps.forEach( ( step ) => {
						step.classList.remove( 'active' );
					} );
					const currentStepElement = popup.querySelector(
						`#step-${ currentStep }`
					);
					if ( currentStepElement ) {
						currentStepElement.classList.add( 'active' );
					}

					stepperItems.forEach( ( item, index ) => {
						item.classList.toggle(
							'active',
							index + 1 === currentStep
						);
					} );
				};

				nextButtons.forEach( ( button ) => {
					button.addEventListener( 'click', () => {
						if ( currentStep < steps.length ) {
							currentStep++;
							updateSteps();
						}
					} );
				} );

				prevButtons.forEach( ( button ) => {
					button.addEventListener( 'click', () => {
						if ( currentStep > 1 ) {
							currentStep--;
							updateSteps();
						}
					} );
				} );

				if ( generatePromptBtn ) {
					generatePromptBtn.addEventListener( 'click', () => {
						//show loading
						document.querySelector(
							'#generate-prompt-btn-loading'
						).style.display = 'block';

						const popup = SweetAlert.getPopup();
						const formData = {
							role_persona:
							popup.querySelector( '#role-persona' ).value,
							target_audience:
								popup
									.querySelector( '#swal-audience' )
									?.tomselect?.getValue() ?? [],
							course_objective:
							popup.querySelector( '#course-objective' )
								.value,
							tone: popup
								.querySelector( '#swal-tone' )
								?.tomselect?.getValue(),
							language: popup
								.querySelector( '#swal-language' )
								?.tomselect?.getValue(),
							lesson_length: parseInt(
								popup.querySelector( '#lesson-length' ).value,
								10
							),
							reading_level:
								popup
									.querySelector( '#swal-levels' )
									?.tomselect?.getValue() ?? [],
							seo_emphasis:
							popup.querySelector( '#seo-emphasis' ).value,
							target_keywords:
							popup.querySelector( '#target-keywords' ).value,
							sections: parseInt(
								popup.querySelector( '#sections' ).value,
								10
							),
							lessons_per_section: parseInt(
								popup.querySelector( '#lessons-per-section' )
									.value,
								10
							),
							quizzes_per_section: parseInt(
								popup.querySelector( '#quizzes-per-section' )
									.value,
								10
							),
							questions_per_quiz: parseInt(
								popup.querySelector( '#questions-per-quiz' )
									.value,
								10
							),
						};

						try {
							localStorage.setItem(
								'lp_ai_audience',
								JSON.stringify( formData.target_audience )
							);
							localStorage.setItem(
								'lp_ai_tone',
								JSON.stringify( formData.tone )
							);
							localStorage.setItem(
								'lp_ai_lang',
								formData.language
							);
							localStorage.setItem(
								'lp_ai_level',
								formData.reading_level
							);
						} catch ( e ) {
							console.error(
								'Lỗi khi lưu cài đặt AI vào localStorage:',
								e
							);
						}

						this.generatePromptFullCourse(
							formData,
							promptPreview,
							generateCourseBtn
						).catch( ( err ) => {
							SweetAlert.showValidationMessage(
								`Request failed: ${ err.message }`
							);
						} );
					} );
				}

				if ( generateCourseBtn ) {
					generateCourseBtn.addEventListener( 'click', () => {
						if ( currentStep === 3 ) {
							currentStep++;
							updateSteps();
						}
						const popup = SweetAlert.getPopup();

						this.toggleLoading( 'show', popup );
						approveCourseBtn.disabled = true;
						regenerateCourseBtn.disabled = true;

						const formData = {
							role_persona:
							popup.querySelector( '#role-persona' ).value,
							target_audience:
								popup
									.querySelector( '#swal-audience' )
									?.tomselect?.getValue() ?? [],
							course_objective:
							popup.querySelector( '#course-objective' )
								.value,
							tone: popup
								.querySelector( '#swal-tone' )
								?.tomselect?.getValue(),
							language: popup
								.querySelector( '#swal-language' )
								?.tomselect?.getValue(),
							lesson_length: parseInt(
								popup.querySelector( '#lesson-length' ).value,
								10
							),
							reading_level:
								popup
									.querySelector( '#swal-levels' )
									?.tomselect?.getValue() ?? [],
							seo_emphasis:
							popup.querySelector( '#seo-emphasis' ).value,
							target_keywords:
							popup.querySelector( '#target-keywords' ).value,
							sections: parseInt(
								popup.querySelector( '#sections' ).value,
								10
							),
							lessons_per_section: parseInt(
								popup.querySelector( '#lessons-per-section' )
									.value,
								10
							),
							quizzes_per_section: parseInt(
								popup.querySelector( '#quizzes-per-section' )
									.value,
								10
							),
							questions_per_quiz: parseInt(
								popup.querySelector( '#questions-per-quiz' )
									.value,
								10
							),
							prompt: popup.querySelector( '#prompt-preview' )
								.value,
						};

						this.generateFullCourse( formData )
							.then( ( res ) => {
								//hide loading
								this.toggleLoading( 'hide', SweetAlert.getPopup() );
								//enable btn
								generateCourseBtn.disabled = false;
								approveCourseBtn.disabled = false;
								regenerateCourseBtn.disabled = false;
								if ( res.success ) {
									const responseData = res.data;
									//show course-details
									const elCourseDetails =
										popup.querySelector(
											'.course-details'
										);
									elCourseDetails.style.display = 'block';
									//show summary-list
									const elSummaryList =
										popup.querySelector( '.summary-list' );
									elSummaryList.style.display = 'block';
									const courseData = responseData.course;
									//add data course in input
									const inputDataCourse = popup.querySelector(
										'#lp-ai-full-course-data'
									);
									inputDataCourse.value =
										JSON.stringify( courseData );
									const number_section =
										responseData.number_section ?? 0;
									const number_lesson =
										responseData.number_lesson ?? 0;
									const number_quiz =
										responseData.number_quiz ?? 0;
									const number_question =
										responseData.number_question ?? 0;

									//
									const courseTitleEl = popup.querySelector(
										'#lp-ai-full-course-title'
									);
									const courseDescriptionEl =
										popup.querySelector(
											'#lp-ai-full-course-description'
										);
									const courseCurriculumEl =
										popup.querySelector(
											'#lp-ai-full-course-curriculum'
										);
									const courseNumberSectionEl =
										popup.querySelector(
											'#lp-ai-full-course-number-section'
										);
									const courseNumberLessonEl =
										popup.querySelector(
											'#lp-ai-full-course-number-lesson'
										);
									const courseNumberQuizEl =
										popup.querySelector(
											'#lp-ai-full-course-number-quiz'
										);
									const courseNumberQuestionEl =
										popup.querySelector(
											'#lp-ai-full-course-number-question'
										);

									if (
										! courseData.sections ||
										courseData.sections.length === 0
									) {
										return '';
									}
									let index = 0;

									courseTitleEl.innerHTML =
										courseData.course_title;
									courseDescriptionEl.innerHTML =
										courseData.course_description;
									courseCurriculumEl.innerHTML =
										courseData.sections
											.map( ( section ) => {
												const lessonsHtml =
													section.lessons
														?.map(
															( lesson ) =>
																`<li>${ lesson.lesson_title }</li>`
														)
														.join( '' ) || '';

												const quizzesHtml =
													section.quizzes
														?.map( ( quiz ) => {
															const questionCount =
																quiz.questions
																	?.length ||
																0;
															return `<li>${ quiz.quiz_title } (${ questionCount } questions)</li>`;
														} )
														.join( '' ) || '';
												index++;
												return `
												<div class="course-section-block">
													<h4>Section ${ index } —${ section.section_title }</h4>
													<ul>
														${ lessonsHtml }
														${ quizzesHtml }
													</ul>
												</div>
											`;
											} )
											.join( '' );
									courseNumberSectionEl.innerHTML =
										number_section;
									courseNumberLessonEl.innerHTML =
										number_lesson;
									courseNumberQuizEl.innerHTML = number_quiz;
									courseNumberQuestionEl.innerHTML =
										number_question;
								} else {
									console.log(
										res.message || 'Unknown error'
									);
								}
							} )
							.catch( ( err ) => {
								console.log( err.message );
							} );
					} );
				}

				if ( approveCourseBtn ) {
					approveCourseBtn.addEventListener( 'click', () => {
						const popup = SweetAlert.getPopup();
						approveCourseBtn.disabled = true;
						const formData = {
							course: popup.querySelector(
								'#lp-ai-full-course-data'
							).value,
						};
						this.approveSaveCourse( formData ).then( ( res ) => {
							if ( res.success ) {
								approveCourseBtn.disabled = false;
							}
						} );
					} );
				}

				if ( regenerateCourseBtn ) {
					regenerateCourseBtn.addEventListener( 'click', () => {
						this.toggleLoading( 'show', SweetAlert.getPopup() );
						//disable button
						generateCourseBtn.disabled = true;
						approveCourseBtn.disabled = true;
						regenerateCourseBtn.disabled = true;
						//hide course-details
						const elCourseDetails =
							popup.querySelector( '.course-details' );
						elCourseDetails.style.display = 'none';
						//hide summary-list
						const elSummaryList =
							popup.querySelector( '.summary-list' );
						elSummaryList.style.display = 'none';
						generateCourseBtn.click();
					} );
				}

				updateSteps();

				const audienceSelect = new TomSelect( '#swal-audience', {
					plugins: [ 'remove_button', 'dropdown_input' ],
				} );
				const toneSelect = new TomSelect( '#swal-tone', {
					plugins: [ 'remove_button' ],
				} );
				const languageSelect = new TomSelect( '#swal-language', {
					plugins: [ 'remove_button' ],
				} );
				const levelSelect = new TomSelect( '#swal-levels', {
					plugins: [ 'remove_button' ],
				} );

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

					const savedLevel = localStorage.getItem( 'lp_ai_level' );
					if ( savedLevel ) {
						levelSelect.setValue( savedLevel );
					}
				} catch ( e ) {
					console.error(
						'Lỗi khi tải cài đặt AI từ localStorage:',
						e
					);
				}
			},
			preConfirm: () => {
				return false;
			},
		} ).then( ( result ) => {} );
	}
}
