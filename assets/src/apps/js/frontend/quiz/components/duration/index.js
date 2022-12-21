const formatDuration = ( seconds ) => {
	let d;

	const dayInSeconds = 3600 * 24;

	if ( seconds > dayInSeconds ) {
		d = ( seconds - ( seconds % dayInSeconds ) ) / dayInSeconds;
		seconds = seconds % dayInSeconds;
	} else if ( seconds == dayInSeconds ) {
		return '24:00';
	}

	const x = ( new Date( seconds * 1000 ).toUTCString() ).match( /\d{2}:\d{2}:\d{2}/ )[ 0 ].split( ':' );

	if ( d ) {
		x[ 0 ] = parseInt( x[ 0 ] ) + ( d * 24 );
	}

	const html = x.join( ':' );

	return html;
};

export default formatDuration;
