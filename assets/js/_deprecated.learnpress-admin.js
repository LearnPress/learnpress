/**
 * Created by foobla on 3/10/2015.
 */

if( typeof LearnPress == 'undefined' ) LearnPress = {};

jQuery(document).ready(function ($) {
	$('.meta_box_edit').click(function (event) {
		event.preventDefault();
		LearnPress.log("haha");
		var post_id = jQuery(this).closest('select').val();
		var edit_link = jQuery('.meta_box_course_lesson_quiz').attr('site_url');
		edit_link += "wp-admin/post.php?post=" + post_id + "&action=edit";
		var new_window = window.open(edit_link);
		LearnPress.log(post_id);
	})
});

jQuery(document).ready(function ($) {
	$("[name='lpr_settings[payment][method]']").click(function () {
		$(".payments").css("display", "none");
		var check = $(this).val();
		$("[id*=" + check + "]").css("display", "");
	});
	$(".payments").css("display", "none");
	var check = $("[name='lpr_settings[payment][method]']:checked").val();
	$("[id*=" + check + "]").css("display", "");
});

jQuery(document).ready(function ($) {
	var input = $('#_lpr_course_price');
	var input2 = $('#_lpr_course_suggestion_price');
	$('[name=_lpr_course_payment]').change(function () {
		if ($('[value=free]').is(':checked')) {
			input.prop('disabled', true);
			input2.prop('disabled', true);
		} else {
			input.prop('disabled', false);
			input2.prop('disabled', false);
		}
	});
});

jQuery(document).ready(function ($) {
	$('#wp-admin-bar-be_teacher').click(function () {
		$.ajax({
			url    : ajaxurl,
			data   : {
				action: 'learnpress_be_teacher'
			},
			success: function () {
				alert('You Are An Instructor Now');
				setTimeout(function () {
					location.reload();
				}, 500);
			}
		})
	});
});

jQuery(document).ready(function ($) {
	$('.lpr-set-up').click(function (evt) {
        evt.preventDefault();
        var $link = $(this);
		$.ajax({
			url    : ajaxurl,
			data   : {
				action: 'learnpress_ignore_setting_up'
			},
			success: function () {
                if( $link.attr('href') ){
                    window.location.href = $link.attr('href');
                }else {
                    $('#lpr-setting-up').remove();
                }
			}
		})
	});
});

jQuery(document).ready(function ($){
	var input = $('#_lpr_course_condition');
	$('[name=_lpr_course_final]').change(function (){
		if($('[value=yes]').is(':checked')){
			input.prop('disabled', false);
		} else {
			input.prop('disabled', true);
		}
	})
});

jQuery(document).ready(function($){
	$('#lpr-custom-time').submit(function(){
		$.ajax({
			url: ajaxurl,
			data: $(this).serialize(),
			success:function(response){
				drawStudentsChart(response, config)
			},
			type: 'POST',
			dataType: 'json'
		})
		return false;
	})

    // admin notice install sample data
    $(document).on('click', '#learn-press-install-sample-data-notice a', function(e){
        e.preventDefault();
        var yes = $(this).hasClass('yes') ? true : false;
        if( yes ){
            $(document.body).block_ui({
                position: 'fixed',
                zIndex: 999999,
                backgroundImage: 'url(' + LearnPress_Settings.plugin_url + '/assets/images/spinner.gif)',
                backgroundRepeat: 'no-repeat',
                backgroundPosition: 'center'
            });
        }
        $.ajax({
            url: ajaxurl,
            dataType: 'html',
            type: 'post',
            data:{
                action: 'learnpress_install_sample_data',
                yes: yes
            },
            success: function(response){
                response = LearnPress.parse_json(response);
                if( response.hide_notice ){
                    $('#learn-press-install-sample-data-notice').fadeOut();
                }else{
                    if( response.error ){
                        alert(response.error)
                    }else if(response.success){
                        alert( response.success );
                        if( response.redirect )
                            window.location.href = response.redirect;
                    }
                    $(document.body).unblock_ui()
                }
            }
        })
    });

        var $checked = null;
        $checked = $('input[name="_lpr_course_enrolled_require"]').bind('click change', function () {

            var payment_field = $('.lpr-course-payment-field').toggleClass('hide-if-js', !( $(this).val() != 'no' ));
            if (payment_field.is(':visible')) {
                $('input[name="_lpr_course_payment"]:checked', payment_field).trigger('change')
            } else {
                $('.lpr-course-price-field').addClass('hide-if-js');
            }

        });
        $checked.filter(':checked').trigger('change');
        if ($checked.length == 0) {
            $('input[name="_lpr_course_enrolled_require"][value="yes"]').trigger('click');
        }

        $('input[name="_lpr_course_payment"]').bind('click change', function () {
            $('.lpr-course-price-field').toggleClass('hide-if-js', !( $(this).val() != 'free' ) || ( $('input[name="_lpr_course_enrolled_require"]:checked').val() == 'no' ));
        }).filter(':checked').trigger('change');

        $checked.closest('.rwmb-field').removeClass('hide-if-js');

});

//javascript hook functions
var lprHook = {
	hooks       : {action: {}, filter: {}},
	addAction   : function (action, callable, priority, tag) {
		lprHook.addHook('action', action, callable, priority, tag);
	},
	addFilter   : function (action, callable, priority, tag) {
		lprHook.addHook('filter', action, callable, priority, tag);
	},
	doAction    : function (action) {
		lprHook.doHook('action', action, arguments);
	},
	applyFilters: function (action) {
		return lprHook.doHook('filter', action, arguments);
	},
	removeAction: function (action, tag) {
		lprHook.removeHook('action', action, tag);
	},
	removeFilter: function (action, priority, tag) {
		lprHook.removeHook('filter', action, priority, tag);
	},
	addHook     : function (hookType, action, callable, priority, tag) {
		if (undefined == lprHook.hooks[hookType][action]) {
			lprHook.hooks[hookType][action] = [];
		}
		var hooks = lprHook.hooks[hookType][action];
		if (undefined == tag) {
			tag = action + '_' + hooks.length;
		}
		lprHook.hooks[hookType][action].push({tag: tag, callable: callable, priority: priority});
	},
	doHook      : function (hookType, action, args) {

		// splice args from object into array and remove first index which is the hook name
		args = Array.prototype.slice.call(args, 1);

		if (undefined != lprHook.hooks[hookType][action]) {
			var hooks = lprHook.hooks[hookType][action], hook;
			//sort by priority
			hooks.sort(function (a, b) {
				return a["priority"] - b["priority"]
			});
			for (var i = 0; i < hooks.length; i++) {
				hook = hooks[i].callable;
				if (typeof hook != 'function')
					hook = window[hook];
				if ('action' == hookType) {
					hook.apply(null, args);
				} else {
					args[0] = hook.apply(null, args);
				}
			}
		}
		if ('filter' == hookType) {
			return args[0];
		}
	},
	removeHook  : function (hookType, action, priority, tag) {
		if (undefined != lprHook.hooks[hookType][action]) {
			var hooks = lprHook.hooks[hookType][action];
			for (var i = hooks.length - 1; i >= 0; i--) {
				if ((undefined == tag || tag == hooks[i].tag) && (undefined == priority || priority == hooks[i].priority)) {
					hooks.splice(i, 1);
				}
			}
		}
	}
};
//end of javascript hook functions

function _lprAdminQuestionHTML($dom, type) {
	switch (type) {
		case 'true_or_false':
			$dom.lprTrueOrFalseQuestion();
			break;
		case 'multi_choice':
			$dom.lprMultiChoiceQuestion();
			break;
		case 'single_choice':
			$dom.lprSingleChoiceQuestion();
			break;
	}
}
lprHook.addAction('lpr_admin_question_html', _lprAdminQuestionHTML);
lprHook.addAction('lpr_admin_quiz_question_html', _lprAdminQuestionHTML);

;
(function ($) {
	var $doc = $(document),
		$body = $(document.body);
    $.fn.scrollTo = function(options){
        return this.each(function(){
            options = $.extend({
                delay: 0,
                offset: 0,
                speed: 'slow'
            }, options || {});

            $('body')
                .fadeIn( 0 )
                .delay( options.delay )
                .animate({
                    scrollTop: $(this).offset().top - options.offset
                }, options.speed);

            return this;
        })
    }


	$.lprShowBlock = function ($form) {
		var $block = $("#lpr-block");

		if (!$block.get(0)) {
			$block = $('<div id="lpr-block" />').appendTo($body).hide();
			$block.click($.lprHideBlock);


		}
		$block.show().data('form', $form);

		return $block;
	}
	$.lprHideBlock = function () {
		var $block = $("#lpr-block");
		if (!$block.get(0)) return;
		$block.hide();
		if ($block.data('form')) $block.data('form').hide();
		$block.data('form', 0);
		return $block;
	}

	$.fn.lprFancyCheckbox = function (options) {
		var defaults = {
			newElementClass   : 'tog',
			activeElementClass: 'on'
		};
		var options = $.extend(defaults, options);
		this.each(function () {
			//Assign the current checkbox to obj
			var obj = $(this);
			//Create new span element to be styled
			var newObj = $('<div/>', {
				'id'   : obj.attr('id'),
				'class': 'lpr-fancy-checkbox ' + options.newElementClass
			}).insertAfter(this).data('input', this);
			//Make sure pre-checked boxes are rendered as checked
			if (obj.is(':checked')) {
				newObj.addClass(options.activeElementClass);
			}
			obj.hide(); //Hide original checkbox
			//Labels can be painful, let's fix that
			if ($('[for=' + obj.attr('id') + ']').length) {

				var label = $('[for=' + obj.attr('id') + ']');
				label.click(function () {
					newObj.trigger('click'); //Force the label to fire our element
					return false;
				});
			}
			//Attach a click handler
			newObj.click(function () {
				//Assign current clicked object
				var obj = $(this);
				//Check the current state of the checkbox
				if (obj.hasClass(options.activeElementClass)) {
					obj.removeClass(options.activeElementClass);
					$(obj.data('input')).attr('checked', false).trigger('change');
				} else {
					obj.addClass(options.activeElementClass);
					$(obj.data('input')).attr('checked', true).trigger('change');
				}
				//Kill the click function
				return false;
			});
		});
	}

	$doc.ready(function () {
		$body = $(document.body);

		$('input.lpr-fancy-checkbox')
			.on('change', function () {
                var $chk = $(this),
                    state = $(this).data('state'),
                    checked = $(this).is(':checked');
                $.ajax({
                    url: ajaxurl,
                    data: {
                        url: $chk.attr('data-url'),
                        plugin: $chk.attr('data-plugin'),
                        t: checked ? 'activate' : 'deactivate',
                        action: 'learnpress_update_add_on_status'
                    },
                    success: function(response){

                        $chk.attr('data-url', response.url)
                            .attr('state', response.status)

                    }
                })
			})
			.lprFancyCheckbox();
        $('#learn-press-add-ons-wrap').on('click', '.plugin-action-buttons a', function(evt){
            evt.preventDefault();
            var $link = $(this), action = $link.data('action');
            if( ! action ) return;
            $link.addClass('disabled spinner');
            $.ajax({
                url: $link.attr('href'),
                dataType: 'html',
                success: function(response){
                    if(action == 'install-now' || action == 'update-now' || action == 'active-now'){
                        if( $link.hasClass('thimpress') ){
                            response = LearnPress.parse_json( response );
                            $link.removeClass( 'spinner' );
                            if( response.status == 'activate' ){
                                $link.addClass('disabled').html(response.status_text).removeAttr('href').removeAttr('data-action');
                                $('.addon-status', $link.closest('.action-links')).html(response.status_text).addClass('enabled');
                            }else{
                                $link.removeClass('disabled');
                            }
                        }
                    }
                }
            })
        });

        $('#learn-press-bundle-activate-add-ons').click(function(){
            var $button = $(this);
            $button.addClass('spinner').attr('disabled', 'disabled');
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'learnpress_bundle_activate_add_ons'
                },
                dataType: 'html',
                success: function(response){
                    response = LearnPress.parse_json( response );
                    if( response.addons ){
                        for(var slug in response.addons ){
                            var plugin = response.addons[slug];
                            if( 'activate' == plugin.status ){
                                $('.plugin-card-'+slug).find('.install-now.thimpress, .active-now.thimpress').addClass('disabled').attr('href', '').html(plugin.status_text);
                            }else{

                            }
                        }
                    }
                    $button.removeClass('spinner').removeAttr('disabled');
                }
            })
        });
        (function() {
            var boxes = $('.post-type-lpr_quiz, .post-type-lpr_course, .post-type-lpr_lesson, .post-type-lpr_question').find('#postbox-container-1');
            if( !boxes.length ) return;
            var $win = $(window),
                $container = $('#poststuff'),
                currentOffset = 0;
            $(window).scroll(function () {
                var container_height = $container.height(),
                    dir = $win.scrollTop() > currentOffset ? 'down' : 'up';
                currentOffset = $win.scrollTop();

                boxes.each(function(){
                    var $box = $(this),
                        box_height = $box.height(),
                        container_height = $container.height(),
                        max_scroll = container_height - box_height - 10,
                        scroll_top = $win.scrollTop(),
                        offset = ( scroll_top - $container.offset().top - $box.height() ) + $win.height();
                    if( max_scroll <= 0 ) return;
                    if( box_height < $win.height() ) offset = scroll_top - $container.offset().top + 50;
                    else{
                        if( offset >= max_scroll ) offset = max_scroll;
                    }
                    $box.css("margin-top", Math.max( 0, offset ) );
                })
            })
        })();

        $('.lpr-dropdown-pages').each( function(){
            var $select = $(this),
                $form = $select.siblings('.lpr-quick-add-page-inline'),
                $actions = $select.siblings('.lpr-quick-actions-inline');
            function add_page_to_all_dropdowns( response ){
                var pos = $.inArray( response.page.ID.toString() + "", response.ordering );
                $('.lpr-dropdown-pages').each(function() {
                    var $select = $(this),
                        $new_option = $('<option value="'+response.page.ID+'">'+response.page.post_title+'</option>')
                    if (pos == 0) {
                        $('option', $select).each(function () {
                            if (parseInt($(this).val())) {
                                $new_option.insertBefore($(this));
                                return false;
                            }
                        })
                    } else if (pos == response.ordering.length - 1) {
                        $select.append($new_option);
                    } else {
                        $new_option.insertAfter($('option[value="' + response.ordering[pos - 1] + '"]', $select));
                    }
                });
            }
            $select.click(function(){
                $select.data('value', this.value)
            }).change(function(){
                var option = $(this).val();
                $actions.addClass('hide-if-js');
                if( option == 'add_new_page'){
                    $form.removeClass('hide-if-js').find('input').val('').focus();
                    $(this).attr('disabled', true);
                }else if( ! isNaN(option) ){
                    $.ajax({
                        url: ajaxurl,
                        data: {
                            action: 'learnpress_get_page_permalink',
                            page_id: option
                        },
                        success: function(response) {
                            if( response ) {
                                $actions.html(response).removeClass('hide-if-js')
                            }
                        },
                        dataType: 'html'
                    })
                }
            })//.trigger('change');
            $form.on('keypress', 'input', function(evt){
                if( evt.keyCode == 13 ){
                    evt.preventDefault();
                    $(this).siblings('button').trigger('click');
                }
            }).on('keydown', 'input', function(evt){
                if( evt.keyCode == 27 ){
                    $(this).siblings('a').trigger('click');
                }
            });
            $('button', $form).click(function(){
                var $input = $(this).siblings('input');
                if( ! $input.val().length ){
                    $input.focus();
                    return;
                }
                $form.block_ui();
                $.ajax(ajaxurl, {
                    data: {
                        action: 'learnpress_create_page',
                        title: $input.val()
                    },
                    success:function( response ){
                        if(response.page){
                            add_page_to_all_dropdowns( response );
                            $select.removeAttr('disabled').val(response.page.ID);
                            $form.addClass('hide-if-js');
                            $actions.html(response.html).removeClass('hide-if-js');
                        }else{
                            alert(response.error);
                            $select.removeAttr('disabled').val( $select.data('value'));
                            if( $select.data('value') ) $actions.removeClass('hide-if-js');
                        }
                        $form.unblock_ui();
                    },
                    dataType: 'json',
                    type: 'post'
                })
            });
            $('a', $form).click(function(evt){
                evt.preventDefault();
                $(this).parent().addClass('hide-if-js');
                $select.removeAttr('disabled').val( $select.data('value') );
                if( $select.data('value') ) $actions.removeClass('hide-if-js');
            })
        })

    })

    $.extend( LearnPress, {
        parse_json: function(response){
            if( typeof reposnse == 'object' ) return response;
            try {
                var m = response.match(/<!-- LP_AJAX_START -->(.*)<!-- LP_AJAX_END -->/)

                if (m && m[1]) {
                    response = JSON.parse(m[1])
                } else {
                    response = JSON.parse(response)
                }
            }catch(e){ response = false }
            return response;
        },
        block_page: function(args){
            var block_page = $( '#lpr-page-block' );
            if( block_page.length == 0 ){
                block_page = $( wp.template( 'page-block' )()).appendTo($body);
                block_page.click($.proxy( function(){ this.unblock_page() }, this ));
            }
            args = $.extend( {
                on_close: function () {

                },
                backgroundColor: undefined,
                opacity: undefined
            }, args || {} );
            $.each(['backgroundColor', 'opacity'], function(){
                block_page.css( this, args[this] );
            })
            block_page.data('args', args).show();
        },
        unblock_page: function(args){
            args = $.extend( {

            }, args || {} );
            var block_page = $( '#lpr-page-block'),
                stored_args = block_page.data('args');
            block_page.hide();

            if( stored_args ){
                $.each(['backgroundColor', 'opacity'], function(){
                    block_page.css( this, '' );
                });
                $.isFunction(stored_args.on_close) && stored_args.on_close.call(block_page);
            }
        },
        showLessonQuiz: function( pos, ed ){
            var textNode        = $(ed.selection.getNode()),
                iframe          = $('#content_ifr'),
                form            = $('#form-quick-add-lesson-link'),
                offset          = textNode.offset(),
                iframe_offset   = iframe.offset(),
                range           = ed.selection.getRng();
            ed.execCommand('mceInsertContent', false,'<span id="learn_press_book_mark"></span>');
            offset = $( '#learn_press_book_mark', textNode).position();
            $( '#learn_press_book_mark', textNode).remove();
            ed.selection.setRng(range);
            if( form.length == 0 ){
                form = $( wp.template('form-quick-add-lesson-link')()).css({zIndex: 99999}).appendTo($body);
                $('select', form).select2({
                    width: 300,
                    containerCssClass: 'lpr-container-dropdown',
                    dropdownCssClass: 'lpr-select-dropdown'
                })
                    .on('select2-close', function(){
                        $('#form-quick-add-lesson-link').hide();
                        tinyMCE.activeEditor.focus();
                    })
                    .on('select2-selecting', function(e){
                        var lesson_id = e.val;
                        if( !lesson_id ) return;
                        var ed          = tinymce.activeEditor,
                            shortcode   = '[quick_lesson_link id="'+lesson_id + '"]',
                            range       = ed.selection.getRng();
                        range.startContainer.nodeValue = range.startContainer.nodeValue.replace(/@l/, '');
                        ed.selection.setCursorLocation( range.startContainer, range.startContainer.nodeValue.length )
                        ed.selection.setContent(shortcode)
                        $('#form-quick-add-lesson-link').hide();
                    });
            }
            form.css({
                top: iframe_offset.top + offset.top,
                left: iframe_offset.left + offset.left + 40
            }).show();
            $('select', form).select2('open');
        }
    });

    $(document).ready(function(){
        var $add_new_h2 = $('body.post-type-lpr_course').find('.page-title-action, .add-new-h2'),
            $reset_h2 = $('<a href="" class="page-title-action add-new-h2">Reset</a>');

        $reset_h2
            .insertAfter($add_new_h2)
            .click(function(evt){
                evt.preventDefault();
                var link = window.location.href.replace(/reset-course-data=([0-9]+)/, '');
                link += '&reset-course-data=' + $('input#post_ID').val();
                window.location.href = link;
            });
    })
})(jQuery)

