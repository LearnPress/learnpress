/**
 * Edit: React hook.
 *
 * @author Nhamdv - ThimPress
 */
import { useEffect, useState } from '@wordpress/element';
import { select, dispatch } from '@wordpress/data';

const Timer = () => {
	const { getData } = select( 'learnpress/quiz' );
	const { submitQuiz } = dispatch( 'learnpress/quiz' );
	const totalTime = getData( 'totalTime' );
	const durationTime = getData( 'duration' );
	const [ seconds, setSeconds ] = useState( totalTime );
	let [ timeSpend, setTimeSpend ] = useState( 0 );

	useEffect( () => {
		const myInterval = setInterval( () => {
			if ( durationTime > 0 ) {
				let remainSeconds = seconds;
				remainSeconds -= 1;

				remainSeconds = wp.hooks.applyFilters( 'js-lp-quiz-remaining_time', remainSeconds, durationTime );

				if ( remainSeconds > 0 ) {
					setSeconds( remainSeconds );
					timeSpend++;
					setTimeSpend( durationTime - remainSeconds );
				} else {
					clearInterval( myInterval );
					submitQuiz();
				}
			} else { // Apply when set duration = 0
				timeSpend++;
				setTimeSpend( timeSpend );
				setSeconds( timeSpend );
			}
		}, 1000 );

		return () => clearInterval( myInterval );
	}, [ seconds, timeSpend ] );

	const formatTime = ( separator = ':' ) => {
		const t = [];
		let m;

		if ( totalTime < 3600 ) {
			t.push( ( seconds - ( seconds % 60 ) ) / 60 );
			t.push( seconds % 60 );
		} else if ( totalTime ) {
			t.push( ( seconds - ( seconds % 3600 ) ) / 3600 );
			m = seconds % 3600;
			t.push( ( m - ( m % 60 ) ) / 60 );
			t.push( m % 60 );
		}

		return t.map( ( a ) => {
			return a < 10 ? `0${ a }` : a;
		} ).join( separator );
	};

	return (
		<div className="countdown">
			<i className="lp-icon-stopwatch"></i>
			<span>{ formatTime() }</span>
			<input type="hidden" name="lp-quiz-time-spend" value={ timeSpend } />
		</div>
	);
};

export default Timer;
