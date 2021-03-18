import { select } from '@wordpress/data';
import { __, _x } from '@wordpress/i18n';

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
				<h4 className="attempts-heading">{ __( 'Last Attempted', 'learnpress' ) }</h4>

				{ hasAttempts && (
					<table>
						<thead>
							<tr>
								<th className="quiz-attempts__questions">{ __( 'Questions', 'learnpress' ) }</th>
								<th className="quiz-attempts__spend">{ __( 'Time spend', 'learnpress' ) }</th>
								<th className="quiz-attempts__marks">{ __( 'Marks', 'learnpress' ) }</th>
								<th className="quiz-attempts__grade">{ __( 'Passing grade', 'learnpress' ) }</th>
								<th className="quiz-attempts__result">{ __( 'Result', 'learnpress' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ attempts.map( ( row, key ) => {
								return (
									<tr key={ `attempt-${ key }` }>
										<td className="quiz-attempts__questions">{ `${ row.questionCorrect } / ${ row.questionCount }` }</td>
										<td className="quiz-attempts__spend">{ row.timeSpend || '--' }</td>
										<td className="quiz-attempts__marks">{ `${ row.userMark } / ${ row.mark }` }</td>
										<td className="quiz-attempts__grade">{ row.passingGrade || _x( '-', 'unknown passing grade value', 'learnpress' ) }</td>
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

export default Attempts;
