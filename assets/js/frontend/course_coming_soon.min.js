( function ( $ ) {
    $.fn.lp_course_countdown = function () {
        var countdowns = this;

        for ( var i = 0; i < countdowns.length; i++ ) {
            var _countdown = $( countdowns[i] ),
                    speed = _countdown.attr( 'data-speed' ),
                    // gmt = _countdown.attr( 'data-gmt' ),
                    time = _countdown.attr( 'data-time' ),
                    showtext = _countdown.attr( 'data-showtext' )
            expiryDate = new Date( time ),
                    gmt = -expiryDate.getTimezoneOffset() / 60;

            var options = {
                expiryDate: expiryDate,
                speed: speed ? speed : 500,
                gmt: parseFloat( gmt ),
                showText: parseInt( showtext ),
            };
            _countdown.mbComingsoon( options );
        }
    }

    $( document ).ready( function () {
        $( '.learnpress-course-coming-soon' ).lp_course_countdown();
    } );

} )( jQuery );