;
( function ( $, _, Backbone ) {

    var LP_Course_Duplicator = window.LP_Course_Duplicator = Backbone.View.extend( {
        id: 'learn-press-duplicate-course',
        template: null,
        course_id: null,
        course_title: null,
        _target: null,
        events: {
            'click .lp-duplicate-course' : '_duplicate'
        },
        initialize: function ( data ) {
            _.bindAll( this, 'render' );
            this.course_id = data.course_id;
            this.course_title = data.course_title;
            this._target = data._target;

            this.render();
        },

        /**
         * Render html template
         * 
         * @returns html
         */
        render: function() {
            var _this = this;
            this.template = wp.template( 'learn-press-duplicate-course' )({
                title: _this.course_title,
                id: _this.course_id
            });
            LP.MessageBox.show( this.template );
            $( '.learn-press-tooltip' ).tooltip({offset: [50, -10]});
        },

        /**
         * Event Click Duplicate course
         * @returns mixed
         */
        _duplicate: function() {
            
        },

        /**
         * Do ajax duplicate
         * 
         * @param object args
         * @returns mixed
         */
        _do_duplicate: function( args ) {
            $.ajax({
                
            }).always( function(){
                
            }).done( function(){
                
            });
        }

    } );

    $.fn.LP_Course_Duplicator = function ( args ) {
        args = $.extend( {}, {
            _target: null,
            course_id: null,
            course_title: null
        }, args );
        return new LP_Course_Duplicator( args );
    };

} )( jQuery, _, Backbone );