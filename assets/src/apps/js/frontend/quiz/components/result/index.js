/**
 * Quizz Result.
 * Edit: Use React hook.
 *
 * @author Nhamdv - ThimPress
 */
import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { getResponse } from '../../../single-curriculum/components/items-progress';

const { debounce } = lodash;
const Result = () => {
	const [ percentage, setPercentage ] = useState( 0 );
	const [ done, setDone ] = useState( false );
	const QuizID = useSelect( ( select ) => {
		return select( 'learnpress/quiz' ).getData( 'id' );
	}, [] );
	const results = useSelect( ( select ) => {
		return select( 'learnpress/quiz' ).getData( 'results' );
	}, [] );

	const passingGrade = useSelect( ( select ) => {
		return select( 'learnpress/quiz' ).getData( 'passingGrade' );
	}, [] );

	const submitting = useSelect( ( select ) => {
		return select( 'learnpress/quiz' ).getData( 'submitting' );
	}, [] );

	useEffect( () => {
		animate();

		let graduation = '';
		if ( results.graduation ) {
			graduation = results.graduation;
		} else if ( results.result >= passingGradeValue.replace( /[^0-9\.]+/g, '' ) ) {
			graduation = 'passed';
		} else {
			graduation = 'failed';
		}

		if ( graduation ) {
			const ele = document.querySelector( `.course-curriculum .course-item.course-item-${ QuizID }` );

			if ( ele ) {
				ele.classList.remove( 'failed', 'passed' );
				ele.classList.add( 'has-status', 'status-completed', graduation );
			}
		}

		const item = [ ...document.querySelectorAll( '#popup-header .items-progress' ) ][ 0 ];
		const elCurriculumSections = document.querySelector( '.curriculum-sections' );

		if ( item && elCurriculumSections ) {
			const totalItems = item.dataset.totalItems;

			const itemCompleted = item.querySelector( '.items-completed' );
			const elProgress = item.querySelector( '.learn-press-progress__active' );

			if ( itemCompleted ) {
				// const number = parseInt( itemCompleted.textContent );

				const allItemCompleted = document.querySelectorAll( '#popup-sidebar .course-curriculum .course-item.status-completed' );

				itemCompleted.textContent = parseInt( allItemCompleted.length );

				// Set progress
				const perCent = parseInt( allItemCompleted.length ) * 100 / parseInt( totalItems );
				const percentSet = 100 - perCent;

				elProgress.style.left = '-' + percentSet + '%';
			}
		}
	}, [ results ] );

	useEffect( () => {
		if ( submitting !== undefined ) {
			updateItemsProgress();
		}
	}, [ submitting ] );

	const updateItemsProgress = () => {
		const elements = document.querySelectorAll( '.popup-header__inner' );

		if ( elements.length > 0 && elements[ 0 ].querySelectorAll( 'form.form-button-finish-course' ).length === 0 ) {
			getResponse( elements[ 0 ] );
		}
	};

	const animate = () => {
		setPercentage( 0 );
		setDone( false );

		jQuery.easing._customEasing = function( e, f, a, h, g ) {
			return ( h * Math.sqrt( 1 - ( ( f = ( f / g ) - 1 ) * f ) ) ) + a;
		};

		debounce( () => {
			const $el = jQuery( '<span />' ).css( {
				width: 1,
				height: 1,
			} ).appendTo( document.body );

			$el.css( 'left', 0 ).animate( { left: results.result }, {
				duration: 1500,
				step: ( now, fx ) => {
					setPercentage( now );
				},
				done: () => {
					setDone( true );
					$el.remove();

					jQuery( '#quizResultGrade' ).css( {
						transform: 'scale(1.3)',
						transition: 'all 0.25s',
					} );

					debounce( () => {
						jQuery( '#quizResultGrade' ).css( {
							transform: 'scale(1)',
						} );
					}, 500 )();
				},
				easing: '_customEasing',
			} );
		}, results.result > 0 ? 1000 : 10 )();
	};

	/**
	 * Render HTML elements.
	 *
	 */

	let percentResult = percentage;

	if ( ! Number.isInteger( percentage ) ) {
		percentResult = parseFloat( percentage ).toFixed( 2 );
	}

	const border = 10;
	const width = 200;
	const radius = width / 2;
	const r = ( width - border ) / 2;
	const circumference = r * 2 * Math.PI;
	const offset = circumference - ( percentResult / 100 * circumference );
	const styles = {
		strokeDasharray: `${ circumference } ${ circumference }`,
		strokeDashoffset: offset,
	};
	const passingGradeValue = results.passingGrade || passingGrade;

	let graduation = '';
	if ( results.graduation ) {
		graduation = results.graduation;
	} else if ( percentResult >= passingGradeValue.replace( /[^0-9\.]+/g, '' ) ) {
		graduation = 'passed';
	} else {
		graduation = 'failed';
	}

	let message = '';
	if ( results.graduationText ) {
		message = results.graduationText;
	} else if ( graduation === 'passed' ) {
		message = __( 'Passed', 'learnpress' );
	} else {
		message = __( 'Failed', 'learnpress' );
	}

	const classNames = [ 'quiz-result', graduation ];

	//Re-write value to results.timeSpend
	/*if ( lpQuizSettings.checkNorequizenroll === 1 ) {
		const timespendStart = window.localStorage.getItem( 'quiz_start_' + QuizID ),
			timespendEnd = window.localStorage.getItem( 'quiz_end_' + QuizID );
		if ( timespendStart && timespendEnd ) {
			results.timeSpend = timeDifference( timespendStart, timespendEnd ).duration;
		}
	}*/
	return (
		<div className={ classNames.join( ' ' ) }>
			<h3 className="result-heading">{ __( 'Your Result', 'learnpress' ) }</h3>

			<div id="quizResultGrade" className="result-grade">
				<svg className="circle-progress-bar" width={ width } height={ width }>
					<circle className="circle-progress-bar__circle" stroke="" strokeWidth={ border } style={ styles }
						fill="transparent" r={ r } cx={ radius } cy={ radius }></circle>
				</svg>

				<span className="result-achieved">{ `${ percentResult }%` }</span>
				<span className="result-require">
					{ passingGradeValue || '-' }
				</span>
			</div>

			{ done && <p className="result-message">{ message }</p> }

			<ul className="result-statistic">
				<li className="result-statistic-field result-time-spend">
					<span>{ __( 'Time spent', 'learnpress' ) }</span>
					<p>{ results.timeSpend }</p>
				</li>
				<li className="result-statistic-field result-point">
					<span>{ __( 'Points', 'learnpress' ) }</span>
					<p>{ results.userMark } / { results.mark }</p>
				</li>
				<li className="result-statistic-field result-questions">
					<span>{ __( 'Questions', 'learnpress' ) }</span>
					<p>{ results.questionCount }</p>
				</li>
				<li className="result-statistic-field result-questions-correct">
					<span>{ __( 'Correct', 'learnpress' ) }</span>
					<p>{ results.questionCorrect }</p>
				</li>
				<li className="result-statistic-field result-questions-wrong">
					<span>{ __( 'Wrong', 'learnpress' ) }</span>
					<p>{ results.questionWrong }</p>
				</li>
				<li className="result-statistic-field result-questions-skipped">
					<span>{ __( 'Skipped', 'learnpress' ) }</span>
					<p>{ results.questionEmpty }</p>
				</li>
			</ul>
		</div>
	);

	function timeDifference( earlierDate, laterDate ) {
		const oDiff = new Object();

		//  Calculate Differences
		//  -------------------------------------------------------------------  //
		let nTotalDiff = laterDate - earlierDate;

		oDiff.days = Math.floor( nTotalDiff / 1000 / 60 / 60 / 24 );
		nTotalDiff -= oDiff.days * 1000 * 60 * 60 * 24;

		oDiff.hours = Math.floor( nTotalDiff / 1000 / 60 / 60 );
		nTotalDiff -= oDiff.hours * 1000 * 60 * 60;

		oDiff.minutes = Math.floor( nTotalDiff / 1000 / 60 );
		nTotalDiff -= oDiff.minutes * 1000 * 60;

		oDiff.seconds = Math.floor( nTotalDiff / 1000 );
		//  -------------------------------------------------------------------  //

		//  Format Duration
		//  -------------------------------------------------------------------  //
		//  Format Hours
		let hourtext = '00';
		if ( oDiff.days > 0 ) {
			hourtext = String( oDiff.days );
		}
		if ( hourtext.length == 1 ) {
			hourtext = '0' + hourtext;
		}

		//  Format Minutes
		let mintext = '00';
		if ( oDiff.minutes > 0 ) {
			mintext = String( oDiff.minutes );
		}
		if ( mintext.length == 1 ) {
			mintext = '0' + mintext;
		}

		//  Format Seconds
		let sectext = '00';
		if ( oDiff.seconds > 0 ) {
			sectext = String( oDiff.seconds );
		}
		if ( sectext.length == 1 ) {
			sectext = '0' + sectext;
		}
		//  Set Duration
		const sDuration = hourtext + ':' + mintext + ':' + sectext;
		oDiff.duration = sDuration;
		//  -------------------------------------------------------------------  //

		return oDiff;
	}
};

export default Result;
