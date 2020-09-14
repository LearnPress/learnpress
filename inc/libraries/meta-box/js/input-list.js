jQuery( function ( $ ) {
	function toggleTree() {
		var $this = $( this ),
			$children = $this.closest( 'li' ).children( 'ul' );

		if ( $this.is( ':checked' ) ) {
			$children.removeClass( 'hidden' );
		} else {
			$children.addClass( 'hidden' ).find( 'input' ).prop( 'checked', false );
		}
	}

	$( '.rwmb-input' )
		.on( 'change', '.rwmb-input-list.rwmb-collapse input[type="checkbox"]', toggleTree )
		.on( 'clone', '.rwmb-input-list.rwmb-collapse input[type="checkbox"]', toggleTree );

	$( '.rwmb-input-list.rwmb-collapse input[type="checkbox"]' ).each( toggleTree );

	$( document ).on( 'click', '.rwmb-input-list-select-all-none', function(e) {
		e.preventDefault();

		var $this = $( this ),
			checked = $this.data( 'checked' );

		if ( undefined === checked ) {
			checked = true;
		}

		$this.parent().siblings( '.rwmb-input-list' ).find( 'input' ).prop( 'checked', checked );

		checked = ! checked;
		$this.data( 'checked', checked );
	} );
} );
