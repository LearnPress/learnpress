/**
 * Common functions/utils used in all page
 */
if( typeof window.LearnPress == 'undefined' ){
    window.LearnPress = {};
}
;(function($){
    window.LearnPress = $.extend( window.LearnPress, {
        reload: function( url ) {
            window.location.href = url || window.location.href;
        },
        parse_json: function (data){
            var m = data.match(/<!-- LPR_AJAX_START -->(.*)<!-- LPR_AJAX_END -->/);
            try {
                if (m) {
                    data = $.parseJSON(m[1]);
                } else {
                    data = $.parseJSON(data);
                }
            }catch(e){
                console.log(e);
                data = {};
            }
            return data;
        }
    });
})(jQuery);