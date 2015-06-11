;(function($){
    $.fn.block_ui = function( args ){
        var position = $(this).css("position");
        args = $.extend( {
            position: 'absolute',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            opacity: 0.5,
            backgroundColor: '#FFF',
            zIndex: 1000
        }, args || {} );
        $(this).attr("data-position", position);

        //.css("position", "relative");
        var $block = $('<div class="block-ui" />').css(args).appendTo($(this));
    }

    $.fn.unblock_ui = function(){
        var $block = $( '.block-ui', this).fadeOut(function(){$(this).remove();});
        var position = $(this).data("position", position);
        $(this).css("position", position);
    }
})(jQuery);