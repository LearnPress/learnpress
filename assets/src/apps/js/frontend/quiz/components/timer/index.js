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

	const totalTime = getData( 'totalTime' ) ? getData( 'totalTime' ) : getData( 'duration' );
	const endTime = getData( 'endTime' );

	const d1 = new Date( endTime.replace( /-/g, '/' ) );
	const d2 = new Date();
	const tz = new Date().getTimezoneOffset();
	const t = parseInt( ( d1.getTime() / 1000 ) - ( ( d2.getTime() / 1000 ) + ( tz * 60 ) ) );

	const [ seconds, setSeconds ] = useState( t > 0 ? t : 0 );

	useEffect( () => {
		const myInterval = setInterval( () => {
			let remainSeconds = seconds;
			remainSeconds -= 1;

			if ( remainSeconds > 0 ) {
				setSeconds( remainSeconds );
			} else {
				clearInterval( myInterval );
				submitQuiz();
			}
		}, 1000 );

		return () => clearInterval( myInterval );
	}, [ seconds ] );

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
		</div>
	);
};

export default Timer;
