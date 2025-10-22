/**
 * Create course with AI
 */

import * as lpUtils from './../../utils.js';
import SweetAlert from 'sweetalert2';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

const argsToastify = {
	text: '',
	gravity: lpDataAdmin.toast.gravity, // `top` or `bottom`
	position: lpDataAdmin.toast.position, // `left`, `center` or `right`
	className: `${ lpDataAdmin.toast.classPrefix }`,
	close: lpDataAdmin.toast.close == 1,
	stopOnFocus: lpDataAdmin.toast.stopOnFocus == 1,
	duration: lpDataAdmin.toast.duration,
};
const showToast = ( message, status = 'success' ) => {
	const toastify = new Toastify( {
		...argsToastify,
		text: message,
		className: `${ lpDataAdmin.toast.classPrefix } ${ status }`,
	} );
	toastify.showToast();
};

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
		lpUtils.eventHandlers( 'click', [
			{
				selector: '.lp-btn-generate-course-with-ai',
				callBack: this.showPopupCreateFullCourse,
			},
			{
				selector: '.lp-btn-step',
				callBack: this.showStep,
			},
			{
				selector: '.lp-btn-generate-prompt',
				callBack: this.generatePrompt,
			},
		] );
	}

	showPopupCreateFullCourse() {
		const modalTemplate = document.querySelector(
			'#lp-tmpl-create-course-ai'
		);

		if ( ! modalTemplate ) {
			console.error( 'AI Create Full Course Modal Template not found!' );
			return;
		}

		SweetAlert.fire( {
			html: modalTemplate.innerHTML,
			width: '60%',
			showCloseButton: true,
			showConfirmButton: false,
			didOpen: () => {
				const popup = SweetAlert.getPopup();
				const elCreateCourseWrap = popup.querySelector( '.lp-create-course-ai-wrap' );
				// Click to show tomSelect style
				elCreateCourseWrap.click();

				/*const nextButtons = popup.querySelectorAll( '.lp-btn-next-step' );

				nextButtons.forEach( ( button ) => {
					button.addEventListener( 'click', () => {
						if ( currentStep < steps.length ) {
							currentStep++;
							updateSteps();
						}
					} );
				} );*/

				return;
				const stepperItems = popup.querySelectorAll( '.stepper-item' );

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
		} ).then( ( result ) => {
			if ( result.isDismissed ) {}
		} );
	}

	showStep( e, target ) {
		e.preventDefault();

		const elBtnActions = target.closest( '.button-actions' );
		const elCreateCourseAIWrap = elBtnActions.closest( '.lp-create-course-ai-wrap' );
		let step = parseInt( elBtnActions.dataset.step );
		const stepMax = parseInt( elBtnActions.dataset.stepMax );
		const elBtnNext = elBtnActions.querySelector( '.lp-btn-step[data-action=next]' );
		const elBtnPrev = elBtnActions.querySelector( '.lp-btn-step[data-action=prev]' );
		const elBtnGeneratePrompt = elBtnActions.querySelector( '.lp-btn-generate-prompt' );

		const stepAction = target.dataset.action;
		if ( stepAction === 'next' ) {
			step++;
		} else if ( stepAction === 'prev' ) {
			step--;
		}

		elBtnActions.dataset.step = step;
		const elForm = target.closest( 'form' );
		const elContentStep = elForm.querySelector( `.step-content[data-step="${ step }"]` );
		const elItemStep = elCreateCourseAIWrap.querySelector( `.step-item[data-step="${ step }"]` );
		elForm.querySelectorAll( '.step-content' ).forEach( ( el ) => el.classList.remove( 'active' ) );
		elContentStep.classList.add( 'active' );
		elCreateCourseAIWrap.querySelectorAll( '.step-item' ).forEach( ( el ) => el.classList.remove( 'active' ) );
		elItemStep.classList.add( 'active' );

		if ( step === 1 ) {
			lpUtils.lpShowHideEl( elBtnPrev, 0 );
		} else {
			lpUtils.lpShowHideEl( elBtnPrev, 1 );
		}

		if ( step === stepMax || step > stepMax ) {
			lpUtils.lpShowHideEl( elBtnNext, 0 );
		} else {
			lpUtils.lpShowHideEl( elBtnNext, 1 );
		}

		if ( step === 3 ) {
			lpUtils.lpShowHideEl( elBtnNext, 0 );
			lpUtils.lpShowHideEl( elBtnGeneratePrompt, 1 );
		} else {
			lpUtils.lpShowHideEl( elBtnGeneratePrompt, 0 );
		}
	}

	generatePrompt( e, target ) {
		e.preventDefault();
		lpUtils.lpSetLoadingEl( target, true );

		// Get dataSend
		const form = target.closest( 'form' );
		const dataSend = lpUtils.getDataOfForm( form );
		dataSend.action = target.dataset.action;

		// Ajax to generate prompt
		const callBack = {
			success: ( response ) => {
				const { message, status, data } = response;

				showToast( message, status );

                console.log(data);

				if ( status === 'success' ) {

				}
			},
			error: ( error ) => {
				showToast( error, 'error' );
			},
			completed: () => {},
		};

		window.lpAJAXG.fetchAJAX( dataSend, callBack );
	}
}
