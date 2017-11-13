;
( function ( $, _, Backbone ) {

    var LP_Course_Duplicator = window.LP_Course_Duplicator = Backbone.View.extend( {
        el: 'learn-press-duplicate-course',
        tagName: 'div',
        template: null,
        course_id: null,
        course_title: null,
        _target: null,
        events: {
            'click .lp-duplicate-course' : '_duplicate',
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
            //// This line to set $el of this view to MessageBox inorder to makes events work
            this.setElement($('#learn-press-message-box-window'));
            this.delegateEvents();
            $( '.learn-press-tooltip' ).tooltip({offset: [50, -30]});
        },

        /**
         * Event Click Duplicate course
         * @returns mixed
         */
        _duplicate: function( e ) {
            e.preventDefault();
            var _this = $( e.currentTarget ),
                    nonce = _this.attr( 'data-nonce' ),
                    id = _this.attr( 'data-id' ),
                    text = _this.text(),
                    processing = _this.attr( 'data-text' ),
                    _class = _this.attr( 'class' ),
                    data = {
                        course_id: id,
                        _nonce: nonce,
                        action: 'learnpress_duplicate_course'
                    },
                    buttons = _this.parents( '#learn-press-duplicate-course' ).find( 'button' );
            data.content = _this.hasClass( 'all-content' ) ? 1 : 0;
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                dataType: 'json',
                beforeSend: function() {
                    _this.text( processing );
                    _this.attr( 'data-text', processing );
                    buttons.attr( 'disabled', true );
                }
            }).done(function( res ){
                if ( typeof res.redirect !== 'undefined' ) {
                    window.location.href = res.redirect;
                }
            });
            return false;
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