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
	/*	const endTime = getData( 'endTime' );

	const d1 = new Date( endTime.replace( /-/g, '/' ) );
	const d2 = new Date();
	const tz = new Date().getTimezoneOffset();
	const t = parseInt( ( d1.getTime() / 1000 ) - ( ( d2.getTime() / 1000 ) + ( tz * 60 ) ) );*/

	const [ seconds, setSeconds ] = useState( totalTime );
	let [ timeSpend, setTimeSpend ] = useState( 0 );
	const limitTime = totalTime > 0;

	useEffect( () => {
		const myInterval = setInterval( () => {
			if ( limitTime ) {
				let remainSeconds = seconds;
				remainSeconds -= 1;

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
			<i className="fas fa-stopwatch"></i>
			<span>{ formatTime() }</span>
			<input type="hidden" name="lp-quiz-time-spend" value={ timeSpend } />
		</div>
	);
};

export default Timer;
