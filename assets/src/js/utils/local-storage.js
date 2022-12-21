const _localStorage = {
	__key: 'LP',
	set( name, value ) {
		const data = _localStorage.get();
		const { set } = lodash;

		set( data, name, value );

		localStorage.setItem( _localStorage.__key, JSON.stringify( data ) );
	},

	get( name, def ) {
		const data = JSON.parse( localStorage.getItem( _localStorage.__key ) || '{}' );
		const { get } = lodash;
		const value = get( data, name );

		return ! name ? data : ( value !== undefined ? value : def );
	},

	exists( name ) {
		const data = _localStorage.get();

		// return data.hasOwnProperty( name );
		return name in data;
	},

	remove( name ) {
		const data = _localStorage.get();
		const newData = lodash.omit( data, name );

		_localStorage.__set( newData );
	},
	__get() {
		return localStorage.getItem( _localStorage.__key );
	},
	__set( data ) {
		localStorage.setItem( _localStorage.__key, JSON.stringify( data || '{}' ) );
	},
};

export default _localStorage;
