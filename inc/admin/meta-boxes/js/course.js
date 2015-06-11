jQuery(function ($) {
	'use strict';

	jQuery('.meta_box_course_lesson_quiz').sortable({
		opacity    : 0.6,
		revert     : true,
		placeholder: "dashed-placeholder",
		cursor     : 'move',
		handle     : '.handle'
	});
	jQuery('.meta_box_add_section').live('click', function (event) {
		event.preventDefault();
		var row = jQuery(this).siblings('.meta_box_course_lesson_quiz').find('li.section.hide');
		var last = jQuery(this).siblings('.meta_box_course_lesson_quiz').find('li:last-child');
        var index = jQuery(this).siblings('.meta_box_course_lesson_quiz').find('li.section.hide').attr("index");
		var clone = row.clone();
		clone.removeClass('hide');
		clone.find('input').val('');
		var name = clone.find('input').attr('rel-name');
        index++;
        row.attr('index', index);
		clone.find('input').attr('name', name);
        clone.find('input').val("Section " + index);
		last.after(clone);
        clone.find('input').focus().select();
	});

	jQuery('.meta_box_add_lesson').live('click', function (event) {
		event.preventDefault();
		var row = jQuery(this).siblings('.meta_box_course_lesson_quiz').find('li.lesson.hide');
		var last = jQuery(this).siblings('.meta_box_course_lesson_quiz').find('li:last-child');
		var clone = row.clone();
		clone.removeClass('hide');
		clone.find('select').val('');
		var name = clone.find('select').attr('rel-name');
		clone.find('select').attr('name', name);
		last.after(clone);
		clone.find('.select2-select').select2({"allowClear": true, "width": "resolve", "placeholder": "Select..."});
	});

	jQuery('.meta_box_add_quiz').live('click', function (event) {
		event.preventDefault();
		var row = jQuery(this).siblings('.meta_box_course_lesson_quiz').find('li.quiz.hide');
		var last = jQuery(this).siblings('.meta_box_course_lesson_quiz').find('li:last-child');
		var clone = row.clone();
		clone.removeClass('hide');
		clone.find('select').val('');
		var name = clone.find('select').attr('rel-name');
		clone.find('select').attr('name', name);
		last.after(clone);
		clone.find('.select2-select').select2({"allowClear": true, "width": "resolve", "placeholder": "Select..."});
	});


	jQuery('.meta_box_remove').live('click', function (event) {
		event.preventDefault();
		var repeatable = jQuery(this).closest('.meta_box_course_lesson_quiz');
		jQuery(this).closest('li').remove();
        if( jQuery(this).closest('li').hasClass('section') ) {
            var index = jQuery('.meta_box_course_lesson_quiz').find('li.section.hide').attr('index');
            index--;
            jQuery('.meta_box_course_lesson_quiz').find('li.section.hide').attr('index', index);
        }
		return false;
	});
});

/*
	TuNN added 21 03 2015
*/
;(function( $ ){

var $doc = $(document),
	$body = $(document.body),
    $window = $(window),
    $postbox = null,
	$section = null,
	$block	= null,
	$quizForm = null,
	$lessonForm = null,
	$quickQuizForm = null,
	$quickLessonForm = null,
    edittext = false,
	select2Options = {
		width: 300,
		containerCssClass: 'lpr-container-dropdown',
		dropdownCssClass: 'lpr-select-dropdown'
	},
	lesson_quiz_sort = {
		items: '>li:not(.lpr-empty-message)',
		connectWith: '.lpr-section-quiz-less',
		axis: 'y',
		revert: true,
        create: function(){
            if( $('li:not(.lpr-empty-message)', this).length ){
                $('.lpr-empty-message', this).hide();
            }
        },
        over: function(){
            //$(this).css("border", "1px solid #FF0000")
        },
        out: function(){
            //$(this).css("border", "none")
        },
        change: function(){
            $('.lpr-empty-message', this).html($(this).children().length );
        },
		start: function(){
			$(this).addClass('sorting');
		},
		sort: function(){
			$(this).children().filter(function(){
				if( $(this).hasClass('ui-sortable-helper') ) return false;
				if( $(this).hasClass('ui-sortable-placeholder') ) return false;
				
				return true;
			}).css({opacity: 0.2});	
		},
        stop: function(){
            $(this).removeClass('sorting');
            $(this).children().css("opacity", "");
        },
		update: function(e, ui){
			ajaxUpdate({
                success: function( res ){
                    toastr["success"]("The quiz/lesson ordering updated!")
                }
            });
		}
	};

function showBlock(){
	if( !$block ){
		$block = $('<div id="lpr-block" />').appendTo( $body ).hide();
		$block.click(hideBlock);
	}
	$block.show();
    $doc.unbind('keyup.lpr-process-short-cut-key keypress.lpr-process-short-cut-key');
    $doc.unbind('keyup.lpr-process-short-cut-key-2 keypress.lpr-process-short-cut-key-2');
	return $block;
}
function hideBlock(){
	if( !$block ) return;
	$block.hide();
	if( $block.data('form') ) $block.data('form').hide();
	$block.data('form', 0);
    $doc.bind('keyup.lpr-process-short-cut-key keypress.lpr-process-short-cut-key', processShortCutKey);
    $doc.bind('keyup.lpr-process-short-cut-key-2 keypress.lpr-process-short-cut-key-2', processShortCutKey2);
	return $block;
}
function sectionNameChange( evt ){
	evt.preventDefault();
	if( !this.value ) return;
    var $this   = $(this);
	var $li = $(this).closest('li').removeClass('lpr-empty');
	$('input.lpr-section-name', $li).attr('name', '_lpr_course_lesson_quiz[__SECTION__][name]');

	if( $section.children('.lpr-empty').length ){
        if( !$this.attr('updating') && evt.type == 'keyup' ) {
            var tabables = $(".lpr-section-name", $section);
            var index = tabables.index(this);
            tabables.eq(index+1).focus().select();
            if( $this.val() != $this.data('value') ) {
                $this.attr('updating', true);
                ajaxUpdate({
                    success: function () {
                        toastr['success']('Section updated')
                        $this.removeAttr('updating');
                    }
                })
            }
        }
        return;
    }

	var $newSection = $( wp.template( 'curriculum-section' )({}) );
	$section.append( $newSection );
	$newSection.find('.lpr-section-name').focus();
	
	$('select', $newSection).select2(select2Options);
	$('.lpr-section-quiz-less', $newSection).sortable(lesson_quiz_sort);

    ajaxUpdate({
        success: function( res ){
            toastr["success"](thim_course_localize.add_new_section)
            updateSectionState();
        }
    });

	return false;
}

function ajaxUpdate( args ){
    args = $.extend({
        success: function(){}
    }, args || {});
    var section = $section.children(':not(.lpr-empty)').clone();
    $(section).each(function( i, j ){
        var $sec = $(this),
            $inputs = $('input[name*="__SECTION__"]', $sec);

        $inputs.each( function(){
            var $input = $(this),
                name = $input.attr('name');

            name = name.replace(/__SECTION__/, i);
            //name = name.substr(1)
            $input.attr('name', name);

        })

    })

    var j = $('input', section).toJSON(),
        data = $.extend({
            action: 'lpr_update_course_curriculum',
            course_id: lpr_course_id
        }, j);

    $.post( ajaxurl, data, function(res){
        var json = {};

        if( res ){
            json = res.split('__LPR_JSON__');
            json = res[res.length - 1];
            try{
                json = JSON.parse( json );
            }catch(e){ json = {}}
        }

        $.isFunction( args.success ) && args.success( json );
    }, 'text').fail(function(){

    });
}

function hideForms(){
	if( $quizForm ) $quizForm.hide();
	if( $lessonForm ) $lessonForm.hide();
	hideBlock();
}

function updateLessonButtonState(){
    if( $('select option', '#lpr-lesson-form').length == 1 ){
        $('.lpr-curriculum-section button[data-action="add-lesson"]').addClass('disabled')
            .tipsy({
                title: function(){ return learnpress_admin_js_localize.lessons_is_not_available }
            });

    }else{
        $('.lpr-curriculum-section button[data-action="add-lesson"]').removeClass('disabled')
            .unbind('mouseenter mouseleave');
    }
}

function updateQuizButtonState(){
    if( $('#lpr-quiz-form select option').length == 1 ){
        $('.lpr-curriculum-section button[data-action="add-quiz"]').addClass('disabled')
            .tipsy({
                title: function(){ return learnpress_admin_js_localize.quizzes_is_not_available }
            });
    }else{
        $('.lpr-curriculum-section button[data-action="add-quiz"]').removeClass('disabled')
            .unbind('mouseenter mouseleave');
    }
}

function selectQuiz(e){
	if( !e.val ) return;
	var $item = $( wp.template('section-quiz-lesson')({title: e.object.text, id: e.val, type: 'quiz'}) );
	$('.lpr-section-quiz-less', $quizForm.data('section')).append($item);
	$(this).find('option[value="'+e.val+'"]').remove();
	$(this).val('');
	hideForms();
	hideBlock();
    updateQuizButtonState();
    ajaxUpdate({
        success: function( res ){
            toastr["success"](thim_course_localize.add_quiz_to_section)
        }
    });
}
function selectLesson(e){
	if( !e.val ) return;	
	var $item = $( wp.template('section-quiz-lesson')({title: e.object.text, id: e.val, type: 'lesson'}) );
	$('.lpr-section-quiz-less', $lessonForm.data('section') ).append($item);
	$(this).find('option[value="'+e.val+'"]').remove();
	$(this).val('')
	hideForms();
	hideBlock();
    updateLessonButtonState();
    ajaxUpdate({
        success: function( res ){
            toastr["success"](thim_course_localize.add_lesson_to_section)
        }
    });
}

function showQuizForm( args ){	
	hideForms();
	args = $.extend({}, args || {});
	if( !$quizForm ){
		
	
	}
	if( args.target ){
		var $target = $(args.target),
			position = $target.offset();
		$quizForm.css({
			left: position.left + $target.outerWidth() + 1,
			top: position.top
		});	
			
	}
	showBlock().data('form', $quizForm);
	$quizForm.data( 'section', args.section );
	$quizForm.show();
	$('select', $quizForm).select2('open');
}

function showLessonForm( args ){
	hideForms();
	args = $.extend({}, args || {});
	if( !$lessonForm ){
		
	
	}
	if( args.target ){
		var $target = $(args.target),
			position = $target.offset();
		$lessonForm.css({
			left: position.left + $target.outerWidth() + 1,
			top: position.top
		});		
	}
	showBlock().data('form', $lessonForm);
	$lessonForm.data( 'section', args.section );
	$lessonForm.show();
	$('select', $lessonForm).select2('open');
}

function showQuickAddLesson(){
	var $button = $(this),
        $wrap = $button.parent();
	if( !$quickLessonForm ){
		$quickLessonForm = $( wp.template('quick-add-lesson')({}) ).appendTo( $body );
		$('input', $quickLessonForm)
			.on('keyup', function(e){				
				if( e.keyCode == 13 ){
					$('button[data-action="add"]', $quickLessonForm).trigger('click');					
				}else if(e.keyCode == 27){
					hideBlock();
				}
			});
		$quickLessonForm.on('click', 'button', function(){
			var action = $(this).data('action');
			switch( action ){
				case 'cancel':
					hideBlock();
					break;
				case 'add':
					var $input = $(this).siblings('input')
					if( !$input.val() ) return;
					$quickLessonForm.addClass('working');
					$input.attr('disabled', true);
					ajaxAddLesson( $quickLessonForm.data('button').parent().siblings('.lpr-section-quiz-less'), $input.val())
					break;
			}	
		})
	}
    $quickLessonForm.data('button', $button);
	showBlock().data('form', $quickLessonForm);
	var position = $(this).offset();
	$quickLessonForm
		.show()
		.css({opacity: 0})
		.css({
			left: position.left,
			top: position.top - $quickLessonForm.outerHeight() - 5
		}).css({
			opacity: 1	
		});
	$('input', $quickLessonForm).removeAttr('disabled').focus().val('');

}
function showQuickAddQuiz(){
	var $button = $(this),
        $wrap = $button.parent();
	if( !$quickQuizForm ){
		$quickQuizForm = $( wp.template('quick-add-quiz')({}) ).appendTo( $body );
		$('input', $quickQuizForm)
			.on('keyup', function(e){		
				if( e.keyCode == 13 ){
					$('button[data-action="add"]', $quickQuizForm).trigger('click');
				}else if(e.keyCode == 27){
					hideBlock();

				}
			});
		$quickQuizForm.on('click', 'button', function(){
			var action = $(this).data('action');
			switch( action ){
				case 'cancel':
					hideBlock();
					break;
				case 'add':
					var $input = $(this).siblings('input')
					if( !$input.val() ) return;
					$quickQuizForm.addClass('working');
					$input.attr('disabled', true);
					ajaxAddQuiz( $quickQuizForm.data('button').parent().siblings('.lpr-section-quiz-less'), $input.val())
					break;
			}	
		})
	}
    $quickQuizForm.data('button', $button);
	showBlock().data('form', $quickQuizForm);
	var position = $(this).offset();
	$quickQuizForm
		.show()
		.css({opacity: 0})
		.css({
			left: position.left,
			top: position.top - $quickQuizForm.outerHeight() - 5
		}).css({
			opacity: 1	
		});
	$('input', $quickQuizForm).fadeIn(200, function(){ $(this).removeAttr('disabled').focus().val('');})

}

function ajaxAddLesson( $sec, name ){
	$.post( ajaxurl, {action : 'lpr_quick_add', type: 'lesson', name: name, course_id: course_id}, function(res){

        if( res ){
            res = res.split('__LPR_JSON__');
            res = res[res.length - 1];
            try{
                res = JSON.parse( res );
            }catch(e){ res = {}}
        }

        if( res.ID ){
			var $item = $( wp.template('section-quiz-lesson')({title: res.post_title, id: res.ID, type: 'lesson'}) ).hide();
			$sec.append($item);
			$item.fadeIn(750);
			$quickLessonForm.removeClass('working');
			hideBlock();

            ajaxUpdate({
                success: function( res ){
                    toastr["success"](thim_course_localize.add_new_section)
                }
            });

		}
	}, 'text')
}

function ajaxAddQuiz( $sec, name ){
	$.post(ajaxurl, {action : 'lpr_quick_add', type: 'quiz', name: name, course_id: course_id}, function(res){
        if( res ){
            res = res.split('__LPR_JSON__');
            res = res[res.length - 1];
            try{
                res = JSON.parse( res );
            }catch(e){ res = {}}
        }
		if( res.ID ){
			var $item = $( wp.template('section-quiz-lesson')({title: res.post_title, id: res.ID, type: 'quiz'}) ).hide();
			$sec.append($item);
			$item.fadeIn(750);
			$quickQuizForm.removeClass('working');
			hideBlock();

            ajaxUpdate({
                success: function( res ){
                    toastr["success"](thim_course_localize.add_new_quiz)
                }
            });

		}
	}, 'text')
}

function addActions( evt ){
	evt.preventDefault();
	var $button = $(this),
		action = $button.data('action');
    if( $button.hasClass('disabled') ) return;
	switch( action ){
		case 'add-quiz': 
			showQuizForm({
				target: $button,
				section: $button.closest('li.lpr-curriculum-section')
			});
			break;
		case 'add-lesson': 
			showLessonForm({
				target: $button,
				section: $button.closest('li.lpr-curriculum-section')
			});
			break;
		case 'quick-add-lesson':
			showQuickAddLesson.call(this);
			break;
		case 'quick-add-quiz':
			showQuickAddQuiz.call(this);
			break;
	}

}
function updateToggleSectionLink(){
    var state = [];
    sections = $('.lpr-curriculum-section:not(.lpr-empty) .lpr-curriculum-section-content', $postbox),
        n = sections.length,
        c = 0;
    sections.each(function(){
        var o = $(this).is(':hidden') ? 0 : 1;
        state.push(o);
        c = o ? c + 1 : c;
    });
    if( c == 0 ){
        $('.hndle a[data-action="close"]', $postbox).hide();
        $('.hndle a[data-action="expand"]', $postbox).show();
    }else if( c == n ){
        $('.hndle a[data-action="close"]', $postbox).show();
        $('.hndle a[data-action="expand"]', $postbox).hide();
    }
    return state;
}
function updateSectionState(){
    var data = {
            action: 'lpr_update_section_state',
            post_id: lpr_course_id ,
            section: updateToggleSectionLink()
        };
    $.post(ajaxurl, data, function(res){

    }, 'text');

}
function toggleSection( toggle, $sec ){
    if( !$sec ){
        $sec = $('.lpr-curriculum-section:not(.lpr-empty)', $postbox);
    }
    var n = $sec.length,
        i = 0;
    $('.lpr-curriculum-section-content', $sec)[toggle ? 'slideDown' : 'slideUp'](function(){
        var section = $(this),
            icon = $( '.lpr-toggle .dashicons', section.prev('h3')).removeClass('dashicons-minus dashicons-plus')
        if( section.is(":visible") ){
            icon.addClass('dashicons-minus');
        }else{
            icon.addClass('dashicons-plus');
        }
        if( i++ == n - 1){
            updateSectionState();
        }
    });
}
    //
function processShortCutKey(e){
    var $li = $('.lpr-curriculum-section.lpr-selected');

    // 38 = Up, 40 = DOWN, 9 = TAB
    console.log(e.keyCode)
    if(!e.shiftKey && ( e.keyCode == 38 || e.keyCode == 40 || e.keyCode == 9 ) ){
        var $next = null;
        if( $li.get(0) ) {
            if (e.keyCode == 38) {
                $next = $li.prev();
                if( !$next.get(0) ){
                    $next = $li.siblings().last();
                }
            } else {
                $next = $li.next();
                if( !$next.get(0) ){
                    $next = $li.siblings().first();
                }
            }
            if ($next.get(0)) {
                e.preventDefault();
                setTimeout(function() {
                    $next.trigger('click');
                    moveToSection($next);
                }, 100);

            }
            e.preventDefault();
            return false;
        }
    }else{
        if( edittext || !$li.get(0) ) return;

        switch(e.keyCode){
            case 108: // L - Chrome => Why?
            case 76: // Q - Firefox => Why?
                $('button[data-action="quick-add-lesson"]', $li).trigger('click')
                e.preventDefault();
                break;
            case 113: // L - Chrome => Why?
            case 81: // Q - Firefox => Why?
                $('button[data-action="quick-add-quiz"]', $li).trigger('click')
                e.preventDefault();
                break;
        }
    }
}
function processShortCutKey2(e){

    switch(e.keyCode) {
        case 13:
            if( $(this).hasClass('lpr-section-name') ) {
                sectionNameChange.call(this, e);
            }
            e.preventDefault();
            return false;
        case 27:
            if( $(this).hasClass('lpr-section-name') ) {
                $(this).val( $(this).data('value') );
            }
            e.preventDefault();
            return false;
    }
}
function moveToSection($sec){
    var top = $sec.offset().top,
        scrollTop = $doc.scrollTop();

    if(top < scrollTop){
        $doc.scrollTop(top - 50);
    }else{
        var winHeight = $window.height();
        if( scrollTop + winHeight < top){
            $doc.scrollTop( top - 50 );
        }
    }
}
function _ready(){
    $postbox = $('#course_curriculum');
	$section = $('.lpr-curriculum-sections');
    var $toggleAll = $('.lpr-course-curriculum-toggle', $postbox).css("display", "inline-block");
    $('a', $toggleAll).click(function(evt){
        evt.preventDefault();
        switch( $(this).data('action') ){
            case 'expand':
                toggleSection(true);
                break;
            case 'close':
                toggleSection(false);
                break;
        }
        $(this).hide().siblings().show();
        return false;
    });
    updateToggleSectionLink();

	$doc.on('change', '.lpr-section-name', sectionNameChange)
        .on('click', '.lpr-curriculum-section', function(evt){

            if( $(this).hasClass('lpr-selected') ) return;
            var $target = $(evt.target);
            var $sec = $(this).addClass('lpr-selected');
            $sec.siblings('.lpr-selected').removeClass('lpr-selected')
            if( $('.lpr-section-name', this).is(':focus') ) {
                $('#wpbody-content').focus();
            }else{
                if( $target.is('input') || $target.is('button') ){
                }else {
                    $('.lpr-section-name', this).focus()
                }
            }
        })
        .on('focus', '.lpr-curriculum-sections input', function(e){
            $(this).data('value', $(this).val()).select()
            $(this).closest('.lpr-curriculum-section').addClass('lpr-selected').siblings('.lpr-selected').removeClass('lpr-selected')

            edittext = true;
        })
        .on('blur', '.lpr-curriculum-sections input', function(e){
            edittext = false;
            ajaxUpdate({});
        })
		.on('keyup.lpr-process-short-cut-key-2 keypress.lpr-process-short-cut-key-2', '.lpr-curriculum-sections input', processShortCutKey2)
        .on('keyup.lpr-process-short-cut-key keypress.lpr-process-short-cut-key', processShortCutKey)
		.on('click', '.lpr-add-buttons button', addActions)
		.on('click', '.lpr-section-quiz-less > li a.lpr-remove', function(evt){
			evt.preventDefault();

			var $item = $(this).closest('li'),
				id = $item.data('id'),
				text = $('span.lpr-title', $item).text(),
				type = $item.data('type').replace(/lpr_/, ''),
				msg = type == 'lesson' ? thim_course_localize.confirm_remove_section_lesson : thim_course_localize.confirm_remove_section_quiz;
			if( !confirm ( msg ) ) return;
			var option = $('<option />');
			option.html( text ).attr( 'value', id );
			if( type.match(/quiz/) ){
				$('select', $quizForm).append( option );
				
			}else{
				$('select', $lessonForm).append( option );
			}
            $item.remove();
            updateLessonButtonState();
            updateQuizButtonState();
            $.post( ajaxurl, {action: 'lpr_remove_lesson_quiz', lesson_quiz_id: id}, function(res){

            });
		})
		.on('click', '.lpr-action.lpr-remove', function(evt){
            // remove section
			evt.preventDefault();
            if( !confirm( thim_course_localize.confirm_remove_section ) ) return;
			var $li = $(this).closest('li'),
                lesson_quiz = [];
            $('li', $li).each(function(){
                var id = $(this).data('id'),
                    text = $('.lpr-title', this).text(),
                    type = $(this).data('type');
                lesson_quiz.push({value: id, text: text, type: type});
            });
            $.each( lesson_quiz, function(){
                var option = $('<option />');
                option.html( this.text ).attr( 'value', this.value );
                if( this.type.match(/quiz/) ){
                    $('select', $quizForm).append( option );

                }else{
                    $('select', $lessonForm).append( option );
                }
            })
            $li.remove()
            ajaxUpdate({
                success: function( res ){
                    toastr["success"](thim_course_localize.remove_section);
                    updateSectionState();
                }
            });
		})
		.on('click', '.lpr-toggle', function(evt){
			evt.preventDefault();
			$(this).closest('li').find('.lpr-curriculum-section-content').slideToggle(updateSectionState);
			$('i', this).toggleClass('dashicons-minus').toggleClass('dashicons-plus');
		})
        .on('click', '.lpr-title', function(){
            var $this = $(this).hide(),
                $input = $('<input type="text" />').val($this.text()).select();
            function _update( apply ){
                apply = $.type(apply) == 'undefined' ? true : false;
                var oldText = $this.text(),
                    newText = $input.val();
                $this.show();
                $input.remove();
                $doc.unbind('click.hide_editable_quiz_lesson');
                if( oldText == newText || !apply ) return;
                $this.text(newText);

                var id = $this.closest('li').data('id')
                $.post(ajaxurl, {id: id, action: 'lpr_quick_edit_lesson_quiz_name', name: newText}, function(res){
                    if( res ){
                        res = res.split('__LPR_JSON__');
                        res = res[res.length - 1];
                        try{
                            res = JSON.parse( res );
                        }catch(e){ res = {}}
                    }
                    $doc.unbind('click.hide_editable_quiz_lesson')
                    $input.remove();
                    toastr["success"](thim_course_localize.update_lesson_quiz.replace(/%s/, res.post_type == 'lpr_quiz' ? 'Quiz' : 'Lesson') )
                }, 'text')
            }
            $input.on('change keyup blur keypress', function(e){
                if( (e.type == 'keyup' && e.keyCode == 13 ) || e.type == 'change' || e.type == 'blur') {
                    _update();
                }else if(e.keyCode == 27){
                    _update( false );
                }
            })
            $doc.on('click.hide_editable_quiz_lesson', function(e){
                if( $(e.target).is($input) ) return;
                _update();
            })
            $input.insertAfter( $this ).focus();
        })
		.on('submit.lpr_prepare', 'form#post', function(){
			
			$('.lpr-curriculum-section', $section).each(function( i, j ){
				var $sec = $(this),
					$inputs = $('input[name*="__SECTION__"]', $sec);
			
				$inputs.each( function(){
					var $input = $(this),
						name = $input.attr('name');
			
					name = name.replace(/__SECTION__/, i);
					$input.attr('name', name);
					
				})
				
			})
			//return false;	
		})
        .on('click', function(e){

            var $li = $(e.target).closest('.lpr-curriculum-section');
            if( !$li.get(0) && $('#lpr-block').is(':hidden') ) {
                $('.lpr-curriculum-section.lpr-selected').removeClass('lpr-selected');

            }else if( $(e.target).is('.lpr-curriculum-section-content') ){
                if( $('.lpr-section-name', $li).is(':focus') ) {
                    $('#wpbody-content').focus();
                }else{
                    $('.lpr-section-name', $li).focus()
                }

            }

            //$li.trigger('click')
        });
	$section.sortable({
		//handle: '.lpr-sort',
		items: '>li:not(.lpr-empty)',
		axis: 'y',
		revert: true,
		start: function(e, ui){
			$(this).addClass('sorting');
			$('.ui-sortable-placeholder', this).height(56);
			
		},
		sort: function(){
			$(this).children().filter(function(){
				if( $(this).hasClass('ui-sortable-helper') ) return false;
				if( $(this).hasClass('ui-sortable-placeholder') ) return false;
				
				return true;
			}).css({opacity: 0.2});	
		},
        stop: function(){
            $(this).removeClass('sorting');
            $(this).children().css("opacity", "");
        },
		update: function(e, ui){
			ajaxUpdate({
                success: function( res ){
                    toastr["success"](thim_course_localize.section_ordered)
                }
            });
		}
	});
		
	$('.lpr-section-quiz-less', $section).sortable(lesson_quiz_sort);
	$('select', $section).select2(select2Options);
	
	$quizForm = $( wp.template('lpr-quiz-form')({}) ).hide().appendTo( $body );
	$('select', $quizForm).select2(select2Options)
		.on('select2-close', hideForms)
		.on('select2-selecting', selectQuiz);
		
	$lessonForm = $( wp.template('lpr-lesson-form')({}) ).hide().appendTo( $body );
	$('select', $lessonForm).select2(select2Options)
		.on('select2-close', hideForms)
		.on('select2-selecting', selectLesson);

    $doc.on('keypress', function(event){
        if( event.shiftKey ){
            //toastr["success"](event.keyCode)
        }

    })

    updateLessonButtonState();
    updateQuizButtonState();
}

$doc.ready( _ready );

})(jQuery);