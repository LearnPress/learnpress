import { select } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { default as formatDuration } from '../duration';

/**
 * Displays list of all attempt from a quiz.
 */
const Attempts = () => {
	const attempts = select( 'learnpress/quiz' ).getData( 'attempts' ) || [];

	const hasAttempts = attempts && !! attempts.length;
	return (
		! hasAttempts ? false : <>
			<div className="quiz-attempts">
				<h4 className="attempts-heading">{ __( 'Last Attempt', 'learnpress' ) }</h4>

				{ hasAttempts && (
					<table>
						<thead>
							<tr>
								<th className="quiz-attempts__questions">{ __( 'Questions', 'learnpress' ) }</th>
								<th className="quiz-attempts__spend">{ __( 'Time spent', 'learnpress' ) }</th>
								<th className="quiz-attempts__marks">{ __( 'Marks', 'learnpress' ) }</th>
								<th className="quiz-attempts__grade">{ __( 'Passing grade', 'learnpress' ) }</th>
								<th className="quiz-attempts__result">{ __( 'Result', 'learnpress' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ attempts.map( ( row, key ) => {
								// Re-write value to attempts.timeSpend
								/*if ( lpQuizSettings.checkNorequizenroll === 1 ) {
									const timespendStart = window.localStorage.getItem( 'quiz_start_' + lpQuizSettings.id ),
										timespendEnd = window.localStorage.getItem( 'quiz_end_' + lpQuizSettings.id );
									if ( timespendStart && timespendEnd ) {
										row.timeSpend = timeDifference( timespendStart, timespendEnd ).duration;
									}
								}*/
								return (
									<tr key={ `attempt-${ key }` }>
										<td className="quiz-attempts__questions">{ `${ row.questionCorrect } / ${ row.questionCount }` }</td>
										<td className="quiz-attempts__spend">{ row.timeSpend || '--' }</td>
										<td className="quiz-attempts__marks">{ `${ row.userMark } / ${ row.mark }` }</td>
										<td className="quiz-attempts__grade">{ row.passingGrade || '-' }</td>
										<td className="quiz-attempts__result">{ `${ parseFloat( row.result ).toFixed( 2 ) }%` } <span>{ row.graduationText }</span></td>
									</tr>
								);
							} ) }
						</tbody>
					</table>
				) }
			</div>
		</>
	);
};
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
export default Attempts;
