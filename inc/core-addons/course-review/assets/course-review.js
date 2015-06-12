/**
 * Created by foobla on 4/10/2015.
 */

jQuery(document).ready(function ($){
    var $review = $('#review');
    function close_form(){
        $(document.body).unblock_ui();
        $review.fadeOut('fast');
        $(document.body).unbind('click.close_review_form')
    }
	$(".write-a-review").click(function( event ){
        event.preventDefault();
        $(document.body).block_ui({
            backgroundColor: '#000',
            opacity: 0.5,
            position: 'fixed'
        });
        $('input, textarea', $review).val('');
        stars.removeClass('hover')
        $review.removeData('selected').fadeIn("fast", function(){
            $('input:first', $review).focus();
        });
        $(document.body).on('click.close_review_form', ">.block-ui", close_form)
    });

    $(".close", $review).click(function(evt){
        evt.preventDefault();
        close_form();
    });

    $('.cancel', $review).click(close_form);

    $(document).keyup(function(e) {
        if( e.keyCode == 27 && $review.is(':visible') ) {
            event.preventDefault();
            close_form();
        }
    });

    $('.review-title', $review).tipsy({gravity: 's'});
    var stars = $('.review-fields ul > li span', $review).each(function(i){
        $(this).hover(function(){
            stars.map(function(j){ j <= i ? $(this).addClass('hover') : $(this).removeClass('hover');})
        }, function(){
            var selected = $review.data('selected');
            stars.map(function(j){ j <= selected ? $(this).addClass('hover') : $(this).removeClass('hover');})
        }).click(function(e){
            e.preventDefault();
            $review.data('selected', i)
        });
    })

    $('.submit-review').click(function (event){
    	event.preventDefault();

    	var $review_title    = $('input[name="review-title"]');
    	var $review_content  = $('textarea[name="review-content"]');
    	var review_rate     = $review.data('selected');
    	var course_id       = $(this).attr('data-id');

        if( 0 == $review_title.val().length ){
            alert('Please enter the review title')
            $review_title.focus();
            return;
        }

        if( 0 == $review_content.val().length ){
            alert('Please enter the review content')
            $review_content.focus();
            return;
        }

        if( review_rate == undefined ){
            alert('Please select your rating')
            return;
        }
        $review.block_ui();
        $('.submitting', $review).show();
    	$.ajax({
    		type   : "POST",
            dataType: 'html',
			url    : ajaxurl,
			data   : {
				action  		: 'learn_press_add_course_review',
				review_rate     : parseInt( review_rate ) + 1,
				review_title	: $review_title.val(),
				review_content	: $review_content.val(),
				course_id		: course_id
			},			
			success: function (html) {				
				$('.course-rate').replaceWith($(html))
                $('.submitting', $review).hide();
                $(".close", $review).trigger('click');
                $('button.write-a-review').remove();
			}
    	})
    })
})
