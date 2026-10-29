/**
 * User quiz - guest (not logged in, course no required enroll).
 *
 * Server saves nothing for guest, so the state is kept on localStorage of browser:
 * - Guest: start time (keep time when reload page), Guest_Retaken: retaken count.
 * - QuestionChecked, Result, CheckedHtml: instant check, same data as server
 *   (meta _lp_question_checked, result questions[ id ] { answered, mark, correct }).
 * - Completed: screen result after submit.
 *
 * @since 4.5.0
 * @version 1.0.0
 */
export class UserQuizGuest {
	/**
	 * @param {Object} quiz instance of UserQuiz
	 */
	constructor( quiz ) {
		this.quiz = quiz;
	}

	get selectors() {
		return this.quiz.constructor.selectors;
	}

	get isGuest() {
		return this.quiz.isGuest;
	}

	// ---------------------------------------------------------------------
	// Start, resume, retake
	// ---------------------------------------------------------------------

	getState() {
		try {
			return JSON.parse( this.quiz.storageGet( this.quiz.getStorageKey( 'Guest' ) ) || 'null' );
		} catch ( e ) {
			return null;
		}
	}

	/**
	 * Run when start / retake / resume completed.
	 *
	 * @param {boolean} isRetake
	 * @param {boolean} isResume reload page when doing quiz
	 */
	onStart( isRetake, isResume ) {
		if ( ! this.isGuest ) {
			return;
		}

		if ( isRetake ) {
			const retaken = ( parseInt( this.quiz.storageGet( this.quiz.getStorageKey( 'Guest_Retaken' ) ) ) || 0 ) + 1;
			this.quiz.storageSet( this.quiz.getStorageKey( 'Guest_Retaken' ), String( retaken ) );
		}

		if ( ! isResume ) {
			this.quiz.storageSet( this.quiz.getStorageKey( 'Guest' ), JSON.stringify( { startAt: Date.now() } ) );
		}
	}

	/**
	 * Guest reload page when doing quiz: server render intro (not save), start again to keep time and answers.
	 */
	maybeResume() {
		if ( ! this.isGuest || ! this.getState()?.startAt ) {
			return;
		}

		const elBtn = this.quiz.elWrap.querySelector( `${ this.selectors.elBtnStart }:not(${ this.selectors.elBtnRetake })` );
		if ( elBtn ) {
			this.quiz.doStart( elBtn, false, true );
		}
	}

	/**
	 * Time spent (seconds) from start time saved on browser.
	 *
	 * @return {number|null} null when not guest or not started
	 */
	getTimeSpend() {
		const state = this.isGuest ? this.getState() : null;
		return state?.startAt ? Math.max( 0, Math.floor( ( Date.now() - state.startAt ) / 1000 ) ) : null;
	}

	/**
	 * Retaken count saved on browser, show remaining on button retake, hide when no remaining.
	 */
	updateRetake() {
		if ( ! this.isGuest ) {
			return;
		}

		const elBtn = this.quiz.elWrap.querySelector( this.selectors.elBtnRetake );
		const retakeMax = parseInt( elBtn?.dataset.retakeMax ) || 0;
		if ( ! elBtn || retakeMax <= 0 ) {
			return;
		}

		const remaining = retakeMax - ( parseInt( this.quiz.storageGet( this.quiz.getStorageKey( 'Guest_Retaken' ) ) ) || 0 );
		if ( remaining <= 0 ) {
			elBtn.remove();
			return;
		}

		elBtn.textContent = `${ elBtn.textContent.replace( /\s*\(\d+\)$/, '' ) } (${ remaining })`;
	}

	// ---------------------------------------------------------------------
	// Submit
	// ---------------------------------------------------------------------

	/**
	 * Guest submit quiz: result is not saved on server, keep the screen completed on browser to show again when reload page.
	 * Cleared when retake.
	 *
	 * @param {Object} data response of submit
	 */
	onSubmit( data ) {
		if ( ! this.isGuest ) {
			return;
		}

		this.quiz.storageRemove( this.quiz.getStorageKey( 'Guest' ) );
		this.quiz.storageSet(
			this.quiz.getStorageKey( 'Completed' ),
			JSON.stringify( {
				status: data.status,
				graduation: data.graduation || '',
				results: data.results || {},
				content: data.content,
			} )
		);
	}

	/**
	 * Guest reload page after submit: server render intro (nothing saved), show the result saved on browser.
	 */
	maybeRestoreCompleted() {
		if ( ! this.isGuest || this.quiz.elWrap.querySelector( this.selectors.elDoing ) ) {
			return;
		}

		let completed = null;
		try {
			completed = JSON.parse( this.quiz.storageGet( this.quiz.getStorageKey( 'Completed' ) ) || 'null' );
		} catch ( e ) {
			return;
		}

		if ( ! completed?.content ) {
			return;
		}

		this.quiz.renderScreen( completed.content, completed.status );
	}

	// ---------------------------------------------------------------------
	// Instant check
	// ---------------------------------------------------------------------

	/**
	 * Ids of questions checked, same as server meta _lp_question_checked.
	 *
	 * @return {number[]} ids
	 */
	getCheckedIds() {
		if ( ! this.isGuest ) {
			return [];
		}

		try {
			const ids = JSON.parse( this.quiz.storageGet( this.quiz.getStorageKey( 'QuestionChecked' ) ) || '[]' );
			return Array.isArray( ids ) ? ids.map( ( id ) => parseInt( id ) ) : [];
		} catch ( e ) {
			return [];
		}
	}

	/**
	 * Result of questions checked, same as server result: { questions: { id: { answered, mark, correct } } }.
	 *
	 * @return {Object} result saved
	 */
	getResult() {
		try {
			const result = JSON.parse( this.quiz.storageGet( this.quiz.getStorageKey( 'Result' ) ) || '{}' );
			return result && typeof result === 'object' && result.questions ? result : { questions: {} };
		} catch ( e ) {
			return { questions: {} };
		}
	}

	/**
	 * @param {string|number} questionId
	 * @return {*} answer saved when checked, null when not checked
	 */
	getCheckedAnswer( questionId ) {
		return this.isGuest ? this.getResult().questions?.[ questionId ]?.answered ?? null : null;
	}

	/**
	 * Save question checked: state checked, result and HTML to restore when reload page.
	 *
	 * @param {string|number} questionId
	 * @param {*}             answer     answer sent
	 * @param {Object}        data       response of check question
	 */
	saveChecked( questionId, answer, data ) {
		questionId = parseInt( questionId ) || 0;
		if ( ! this.isGuest || ! questionId ) {
			return;
		}

		const { quiz } = this;
		const ids = this.getCheckedIds();
		if ( ! ids.includes( questionId ) ) {
			ids.push( questionId );
		}
		quiz.storageSet( quiz.getStorageKey( 'QuestionChecked' ), JSON.stringify( ids ) );

		const result = this.getResult();
		result.questions[ questionId ] = {
			answered: answer,
			mark: parseFloat( data.mark ) || 0,
			correct: !! data.correct,
			minus: parseFloat( data.minus ) || 0,
		};
		quiz.storageSet( quiz.getStorageKey( 'Result' ), JSON.stringify( result ) );

		let html = {};
		try {
			html = JSON.parse( quiz.storageGet( quiz.getStorageKey( 'CheckedHtml' ) ) || '{}' ) || {};
		} catch ( e ) {}
		html[ questionId ] = data.content;
		quiz.storageSet( quiz.getStorageKey( 'CheckedHtml' ), JSON.stringify( html ) );
	}

	/**
	 * Guest reload page: server render questions not checked, replace by HTML of questions checked and update Earned Point.
	 */
	restoreChecked() {
		const ids = this.getCheckedIds();
		if ( ! ids.length ) {
			return;
		}

		let html = {};
		try {
			html = JSON.parse( this.quiz.storageGet( this.quiz.getStorageKey( 'CheckedHtml' ) ) || '{}' ) || {};
		} catch ( e ) {
			return;
		}

		const { elDoing, elQuestion: selQuestion } = this.selectors;
		const questions = this.getResult().questions;
		ids.forEach( ( id ) => {
			const elQuestion = this.quiz.elWrap.querySelector( `${ elDoing } ${ selQuestion }[data-id="${ id }"]` );
			if ( ! elQuestion || elQuestion.querySelector( '.question-response' ) || ! html[ id ] ) {
				return;
			}

			const elTemp = document.createElement( 'div' );
			elTemp.innerHTML = html[ id ];
			if ( ! elTemp.firstElementChild ) {
				return;
			}

			// Page visibility is set by showPage() after restore.
			elQuestion.replaceWith( elTemp.firstElementChild );
			this.quiz.updateEarnedPoint( questions[ id ] || {} );
		} );
	}

	/**
	 * Remove data of the attempt (start, retake, submit).
	 */
	clear() {
		[ 'QuestionChecked', 'Result', 'CheckedHtml', 'Completed' ].forEach( ( suffix ) => this.quiz.storageRemove( this.quiz.getStorageKey( suffix ) ) );
	}
}
