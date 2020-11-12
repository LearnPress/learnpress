window.$Vue = window.$Vue || Vue;

jQuery( function( $ ) {
	if ( ! $( '#learn-press-reset-course-users' ).length ) {
		return;
	}

	new $Vue( {
		el: '#learn-press-reset-course-users',
		data: {
			s: '',
			status: false,
			courses: [],
		},
		methods: {
			resetActionClass( course ) {
				return {
					'dashicons-trash': ! course.status,
					'dashicons-yes': course.status === 'done',
					'dashicons-update': course.status === 'resetting',
				};
			},
			updateSearch( e ) {
				this.s = e.target.value;
				this.status = false;
				e.preventDefault();
			},
			search( e ) {
				e.preventDefault();

				const that = this;
				this.s = $( this.$el ).find( 'input[name="s"]' ).val();

				if ( this.s.length < 3 ) {
					return;
				}

				this.status = 'searching';
				this.courses = [];

				$.ajax( {
					url: '',
					data: {
						'lp-ajax': 'rs-search-courses',
						s: this.s,
					},
					success( response ) {
						that.courses = LP.parseJSON( response );
						that.status = 'result';
					},
				} );
			},

			reset( e, course ) {
				e.preventDefault();

				if ( ! confirm( 'Are you sure to reset course progress of all users enrolled this course?' ) ) {
					return;
				}
				const that = this;
				course.status = 'resetting';
				$.ajax( {
					url: '',
					data: {
						'lp-ajax': 'rs-reset-course-users',
						id: course.id,
					},
					success( response ) {
						response = LP.parseJSON( response );
						if ( response.id == course.id ) {
							for ( let i = 0, n = that.courses.length; i < n; i++ ) {
								if ( that.courses[ i ].id === course.id ) {
									that.courses[ i ].status = 'done';
									break;
								}
							}
						}
					},
				} );
			},
		},
	} );
} );
