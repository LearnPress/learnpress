const Hook = {
	hooks: { action: {}, filter: {} },
	addAction( action, callable, priority, tag ) {
		this.addHook( 'action', action, callable, priority, tag );
		return this;
	},
	addFilter( action, callable, priority, tag ) {
		this.addHook( 'filter', action, callable, priority, tag );
		return this;
	},
	doAction( action ) {
		return this.doHook( 'action', action, arguments );
	},
	applyFilters( action ) {
		return this.doHook( 'filter', action, arguments );
	},
	removeAction( action, tag ) {
		this.removeHook( 'action', action, tag );
		return this;
	},
	removeFilter( action, priority, tag ) {
		this.removeHook( 'filter', action, priority, tag );
		return this;
	},
	addHook( hookType, action, callable, priority, tag ) {
		if ( undefined === this.hooks[ hookType ][ action ] ) {
			this.hooks[ hookType ][ action ] = [];
		}
		const hooks = this.hooks[ hookType ][ action ];
		if ( undefined === tag ) {
			tag = action + '_' + hooks.length;
		}
		this.hooks[ hookType ][ action ].push( { tag, callable, priority } );
		return this;
	},
	doHook( hookType, action, args ) {
		args = Array.prototype.slice.call( args, 1 );

		if ( undefined !== this.hooks[ hookType ][ action ] ) {
			let hooks = this.hooks[ hookType ][ action ],
				hook;

			hooks.sort( function( a, b ) {
				return a.priority - b.priority;
			} );

			for ( let i = 0; i < hooks.length; i++ ) {
				hook = hooks[ i ].callable;
				if ( typeof hook !== 'function' ) {
					hook = window[ hook ];
				}

				if ( 'action' === hookType ) {
					args[ i ] = hook.apply( null, args );
				} else {
					args[ 0 ] = hook.apply( null, args );
				}
			}
		}

		if ( 'filter' === hookType ) {
			return args[ 0 ];
		}
		return args;
	},
	removeHook( hookType, action, priority, tag ) {
		if ( undefined !== this.hooks[ hookType ][ action ] ) {
			const hooks = this.hooks[ hookType ][ action ];
			for ( let i = hooks.length - 1; i >= 0; i-- ) {
				if ( ( undefined === tag || tag === hooks[ i ].tag ) && ( undefined === priority || priority === hooks[ i ].priority ) ) {
					hooks.splice( i, 1 );
				}
			}
		}
		return this;
	},
};

export default Hook;
