/**
 * Created by Tu on 30/03/2015.
 * Modified 03 Apr 2015
 */

;(function($){
    $.fn.fitSizeWithText = function(opts){

        return $.each(this, function(){
            if( $(this).data('fitSizeWithText') ) return this
            var options = $.extend({
                    x: 30,
                    auto: false
                }, opts || {}),
                $this = $(this),
                text = null,
                css = {
                    visibility: 'hidden',
                    padding: $this.css('padding'),
                    fontSize: $this.css('font-size'),
                    fontWeight: $this.css('font-weight'),
                    fontStyle: $this.css('font-style'),
                    fontFamily: $this.css('font-family')
                },
                width = 0;
            function calculate() {
                text = $this.val ? $this.val() : $this.text();
                var checker = $('<span />').css(css).html(text).appendTo($(document.body));
                width = checker.outerWidth() + options.x;
                checker.remove();
                $this.width(width);
            }
            calculate();
            if( options.auto ){
                $this.on('keyup', function(){
                    calculate()
                })
            }
            return $this.data('fitSizeWithText', 1);
        });

    }
    $.fn.outerHTML = function(){

        // IE, Chrome & Safari will comply with the non-standard outerHTML, all others (FF) will have a fall-back for cloning
        return (!this.length) ? this : (this[0].outerHTML || (
            function(el){
                var div = document.createElement('div');
                div.appendChild(el.cloneNode(true));
                var contents = div.innerHTML;
                div = null;
                return contents;
            })(this[0]));

    }
    function onRemoveQuestion(question){
        var $select = $('#lpr-quiz-question-select-existing'),
            $option = $('<option value="'+question.id+'">'+question.text+'</option>', $select);
        $select.append($option);
    }
    function updateAnswers($element){
        $rows = $('.lpr-question-option tbody', $element).children();
        if( $rows.length == 1 ){
            $rows.addClass('lpr-disabled');
        }else{
            $rows.filter(function(){
                if( $('.lpr-answer-text', this).val().length == 0 ) {
                    $(this).addClass('lpr-disabled');
                }else{
                    $(this).removeClass('lpr-disabled');
                }
            })
        }
        $element.trigger('lpr_update_item_index')
    }
    function inputKeyEvent(evt){
        var $input = $(this),
            $wrap = ( evt.data || {} ).wrap;

        if (evt.type == 'focusin') {
            $input.data('value', $input.val());
        } else if (evt.type == 'focusout') {
            if ($input.val().length == 0) {
                var $row = $input.closest('tr');
                if (!$row.is(':last-child')) $row.remove();
            }
            return false;
        } else {
            var $row = $input.closest('tr'),
                $rows = $row.parent().children(),
                index = $rows.index($row);


            switch (evt.keyCode) {
                default:
                    if ($row.is(':last-child') && $input.val().length) {
                        $('.lpr-button-add-answer', $wrap).trigger('click');
                        $('.lpr-question-answer tr:last input:last', $wrap).val('')
                        $input.focus();
                    }
                    break;
                case 13: // enter
                    if ($input.val().length) {
                        var $nextrow = $row.next();
                        if (!$nextrow.get(0)) {
                            $('.lpr-button-add-answer', $wrap).trigger('click')
                            $nextrow = $row.next();
                            $('input.lpr-answer-text', $nextrow).val('')
                        }
                        $('input.lpr-answer-text', $nextrow).focus();
                    }
                    evt.preventDefault();
                    return false;
                    break;
                case 27: // esc
                    $input.val($input.data('value'));
                    $input.closest('.lpr-question').focus();
                    evt.preventDefault();
                    break;
                case 9: // tab

                    $('input.lpr-answer-text', $row.next()).focus();
                    evt.preventDefault();
                    return false;
                    break;
                case 8: // back space
                case 46: // delete

                    if ($input.val().length == 0) {
                        $newrow = $row.prev();

                        if ($rows.length > 1) {
                            try{ $row.remove(); }catch(ex){}
                        }
                        if (!$newrow.get(0)) {
                            $newrow = $rows.last();
                        }
                        $newrow.find('input.lpr-answer-text').focus();
                        updateAnswers($wrap)
                        evt.preventDefault();
                        return false;
                    }
            }
        }
    }
    $.lprMultiChoiceQuestion = function(elem){
        var $element = $(elem);

        $('.lpr-sortable tbody', $element).sortable({
            handle: '.lpr-sortable-handle',
            placeholder: 'placeholder',
            axis: 'y',
            helper: function(e, helper) {
                helper.children().each(function(i) {
                    var td = $(this),
                        w = td.innerWidth() - parseInt( td.css("padding-left") ) - parseInt( td.css("padding-right") ) ;
                    td.width( w + 1 );
                });

                return helper;
            },
            start: function(e, ui) {
                ui.placeholder.height(ui.item.height() - 2);
            },
            update: function(e, ui) {
                $(this).trigger('lpr_update_item_index');
            },
            stop: function(e, ui) {
                ui.item.children().removeAttr('style');
            }
        }).on('lpr_update_item_index', function(){
            $(this).children().each(function(i){
                var $row = $(this),
                    $inputs = $('input[name^="lpr_question"]', this);
                $inputs.each(function(){
                    var name = $(this).attr('name');
                    name = name.replace(/\[__INDEX__([0-9]?)\]/, '[__INDEX__' + i + ']');
                    $(this).attr('name', name);
                })
            })
        });
        $element.on('click', '.lpr-button-add-answer', function(){
            if( $('.lpr-sortable tbody tr:last input:last', $element).val().length == 0 ){
                $('.lpr-sortable tbody tr:last input:last', $element).focus();
                return;
            }
            var $parent = $(this).parent(),
                tpl = wp.template("multi-choice-question-answer");
            var $item = $(tpl({
                question_id: $element.data('id') || 0
            }));
            $('.lpr-sortable tbody', $element).append( $item ).trigger('lpr_update_item_index');
            $('input[name*="text"]', $item).focus().val('Question answer');
            updateAnswers($element);
        }).on('click', '.lpr-remove-answer', function(){
            var $row = $(this).closest('tr');

            $row.remove();
        }).on('keydown keypress focus blur', '.lpr-answer-text', {wrap: $element}, inputKeyEvent)
            .on('focus', function(){$(this).addClass('selected')})
            .on('blur', function(){$(this).removeClass('selected')})
            .attr('tabindex', 0);

        updateAnswers($element);
    }
    $.fn.lprMultiChoiceQuestion = function(){
        return $.each( this, function(){
            var q = $(this).data('lprMultiChoiceQuestion');
            if( !q ){
                q = new $.lprMultiChoiceQuestion(this);
                $(this).data('lprMultiChoiceQuestion', q)
            }
            return this;
        })
    }


    // True or False question
    $.lprTrueOrFalseQuestion = function(elem){
        var $element = $(elem);

        $('.lpr-sortable tbody', $element).sortable({
            handle: '.lpr-sortable-handle',
            placeholder: 'placeholder',
            axis: 'y',
            helper: function(e, helper) {
                helper.children().each(function(i) {
                    var td = $(this),
                        w = td.innerWidth() - parseInt( td.css("padding-left") ) - parseInt( td.css("padding-right") ) ;
                    td.width( w + 1 );
                });

                return helper;
            },
            start: function(e, ui) {
                ui.placeholder.height(ui.item.height() - 2);

            },
            update: function(e, ui) {
                $(this).trigger('lpr_update_item_index');
            },
            stop: function(e, ui) {
                ui.item.children().removeAttr('style');
            }
        }).on('lpr_update_item_index', function(){
            $(this).children().each(function(i){
                var $row = $(this),
                    $inputs = $('input[name^="lpr_question"]', this);
                $inputs.each(function(){
                    var name = $(this).attr('name');
                    name = name.replace(/\[__INDEX__([0-9]?)\]/, '[__INDEX__' + i + ']');
                    $(this).attr('name', name);
                })
            })
        });
        $element.on('click', 'input[data-group^="lpr-question-answer-"]', function(){
            var group = $(this).attr('data-group');

            $(this).siblings('input').val(1);
            $('input[data-group="' + group+ '"]', $element).not(this).each(function(){
                $(this).removeAttr('checked').siblings('input').val(0);
            });
        }).on('keydown keypress focus blur', '.lpr-answer-text', {wrap: $element}, inputKeyEvent)
            .on('focus', function(){$(this).addClass('selected')})
            .on('blur', function(){$(this).removeClass('selected')})
            .attr('tabindex', 0);

        updateAnswers($element);
    }
    $.fn.lprTrueOrFalseQuestion = function(){
        return $.each( this, function(){
            var q = $(this).data('lprTrueOrFalseQuestion');
            if( !q ){
                q = new $.lprTrueOrFalseQuestion(this);
                $(this).data('lprTrueOrFalseQuestion', q)
            }
            return this;
        })
    }

    // Select dropdown question
    $.lprSingleChoiceQuestion = function(elem){
        var $element = $(elem);

        $('.lpr-sortable tbody', $element).sortable({
            handle: '.lpr-sortable-handle',
            placeholder: 'placeholder',
            items: 'tr:not(.lpr-disabled)',
            axis: 'y',
            helper: function(e, helper) {
                helper.children().each(function(i) {
                    var td = $(this),
                        w = td.innerWidth() - parseInt( td.css("padding-left") ) - parseInt( td.css("padding-right") ) ;
                    td.width( w + 1 );
                });

                return helper;
            },
            start: function(e, ui) {
                ui.placeholder.height(ui.item.height() - 2);//.find('td:eq(0)').css({display: 'none'});

            },
            update: function(e, ui) {
                $(this).trigger('lpr_update_item_index');
            },
            stop: function(e, ui) {
                ui.item.children().removeAttr('style');
            }
        }).on('lpr_update_item_index', function(){
            $(this).children().each(function(i){
                var $row = $(this),
                    $inputs = $('input[name^="lpr_question"]', this);
                $inputs.each(function(){
                    var name = $(this).attr('name');
                    name = name.replace(/\[__INDEX__([0-9]?)\]/, '[__INDEX__' + i + ']');
                    $(this).attr('name', name);
                })
            })
        });
        $element.on('click', 'input[data-group^="lpr-question-answer-"]', function(){
            var group = $(this).attr('data-group');

            $(this).siblings('input').val(1);
            $('input[data-group="' + group+ '"]', $element).not(this).each(function(){
                $(this).removeAttr('checked').siblings('input').val(0);
            });
        });
        $element.on('click', '.lpr-button-add-answer', function(){
            var $parent = $(this).parent(),
                tpl = wp.template("single-choice-question-answer");
            var $item = $(tpl({
                question_id: $element.data('id') || 0
            }));
            $('.lpr-sortable tbody', $element).append( $item ).trigger('lpr_update_item_index');
            $('input[name*="text"]', $item).focus();

            updateAnswers($element);

        }).on('click', '.lpr-remove-answer', function(){
            var $row = $(this).closest('tr');

            $row.remove();
        }).on('keydown keypress focus blur', '.lpr-answer-text', {wrap: $element}, inputKeyEvent)
            .on('focus', function(){$(this).addClass('selected')})
            .on('blur', function(){$(this).removeClass('selected')})
            .attr('tabindex', 0);
        updateAnswers($element);
    }
    $.fn.lprSingleChoiceQuestion = function(){
        return $.each( this, function(){
            var q = $(this).data('lprSingleChoiceQuestion');
            if( !q ){
                q = new $.lprSingleChoiceQuestion(this);
                $(this).data('lprSingleChoiceQuestion', q)
            }
            return this;
        })
    }




var $doc    = $(document),
    $body   = $(document.body);

function questionActions() {
    $doc.on('click', '.lpr-question-head a', function (evt) {
        var $link = $(this);
        if ($link.attr('href')) return true;
        evt.preventDefault();
        var action = $link.data('action'),
            $wrap = $link.closest('.lpr-question');
        switch (action) {
            case 'remove':
                if (!confirm('Remove?')) return;
                onRemoveQuestion({
                    id: $wrap.data('id'),
                    text: $('.lpr-question-name-input', $wrap).val()
                });
                var data = {
                    action: 'lpr_quiz_question_remove',
                    question_id: $wrap.data('id'),
                    quiz_id: lpr_quiz_id
                }
                $.post(ajaxurl, data, function(){

                })
                $wrap.remove();
                break;
            case 'collapse':
            case 'expand':
                var $input = $('input.lpr-question-toggle', $wrap);
                if (!$input.get(0)) {
                    $input = $('<input class="lpr-question-toggle" type="hidden" name="lpr_question[' + $wrap.data('id') + '][toggle]" value="" />')
                    $input.appendTo($wrap);
                }
                $input.val(action == 'collapse' ? 0 : 1);
                $link.hide();
                if (action == 'collapse') {
                    $link.siblings('a[data-action="expand"]').show();
                    $('.lpr-question-content', $wrap).slideUp();//addClass('hide-if-js');
                } else {
                    $link.siblings('a[data-action="collapse"]').show();
                    $('.lpr-question-content', $wrap).slideDown();//removeClass('hide-if-js');
                }
                break;
        }
    });


    $doc.on('add_new_question_type', function () {
        if( !$(this).val() ) return;


        var args = $(this).prev().offset(),
            type = $(this).val();
        args.type = $(this).val();


        $(this).val('').prev().find('.select2-chosen').html($('option:selected', this).html());
        var $form = showFormQuickAddQuestion(args);
        $form.css("top", args.top - $form.outerHeight() - 5);
        $('input', $form).val('').focus();
    });

    $('#lpr-quiz-question-select-existing').change(function(){
        addExistingQuestion(this.value)
    })
    if($.fn.select2) {
        $('.lpr-select2').select2();
    }
}
function loadQuestionSettings(){
    var $select = $(this),
        type = $select.val(),
        old_type = $select.attr('data-type');
    if( !type ){
        $('.lpr-question-settings').html('');
        return;
    }
    var data = {
        action: 'lpr_load_question_settings',
        question_id: lpr_question_id ,
        type: type
    };

    var $old_question = $('.lpr-question');
    $old_question.block_ui();
    data = lprHook.applyFilters( 'lpr_admin_load_question_settings_args', data, type, $old_question, old_type );
    $select.attr('disabled', true)
    $.post(ajaxurl, data, function(res){
        var $question = $(res);
        $('.lpr-question-settings').html($question);
        lprHook.doAction('lpr_admin_question_html', $question, type, old_type);
        $select.removeAttr('disabled').attr('data-type', type);
        $old_question.unblock_ui();
    }, 'text');
}
function addExistingQuestion(id, args){
    var data = {
        action: 'lpr_quiz_question_add',
        quiz_id: lpr_quiz_id,
        question_id: id
    };
    $.post(ajaxurl, data, function(res){
        if( res.success ) {
            var $question = $(res.html),
                $select = $('#lpr-quiz-question-select-existing');
            $('#lpr-quiz-questions').append($question);
            lprHook.doAction('lpr_admin_quiz_question_html', $question, res.type);
            $('option[value="'+id+'"]', $select).remove();
            $( '.lpr-question-title input', $question).fitSizeWithText({auto: true})
            $select
                .val('').prev().find('.select2-chosen').html($('option:selected', $select).html());
            args && args.success && args.success();
        }else{
            alert( res.msg )
        }
    }, 'json');
}
function addNewQuestion(args){
    //var type = $('#lpr-quiz-question-type').val();
    args = $.extend({
        type: null,
        text: null,
        success: false
    }, args || {});
    if( !args.type ){
        // warning
        return;
    }
    var data = {
        action: 'lpr_quiz_question_add',
        quiz_id: lpr_quiz_id,
        type: args.type,
        text: args.text
    };
    $.post(ajaxurl, data, function(res){
        if(res && res.success) {
            var $question = $(res.html)
            $('#lpr-quiz-questions').append($question);
            lprHook.doAction('lpr_admin_quiz_question_html', $question, args.type);
            $( '.lpr-question-title input', $question).fitSizeWithText({auto: true})
            args.success && args.success();
        }
    }, 'json');
}

function showFormQuickAddQuestion(args){
    args = $.extend({
        top: undefined,
        left: undefined,
        type: null
    }, args || {})


    var $form = $('#lpr-form-quick-add-question');

    if( !$form.get(0) ){
        $form = $( wp.template('form-quick-add-question')() ).appendTo($body).hide();
        $('button', $form).click(function(){
            var action = $(this).data('action'),
                $input = $('input', $form),
                args = $form.data('data');
            switch (action){
                case 'add':
                    if( !$input.val() ){
                        $input.css("border-color", "#FF0000");
                        return;
                    }
                    $form.addClass('working');

                    addNewQuestion({
                        type: $('select', $form).val(),
                        text: $input.val(),
                        success: function(){
                            $.lprHideBlock();
                            $form.removeClass('working');
                            $input.css("border-color", "");
                        }
                    });
                    break;
                case 'cancel':
                    $.lprHideBlock();
                    $input.css("border-color", "");
                    break;
            }
        });
        $('input', $form).on('keyup', function(evt){
            if( evt.keyCode == 13 ){
                $(this).siblings('button[data-action="add"]').trigger('click');
            }else if(evt.keyCode == 27 ){
                $(this).siblings('button[data-action="cancel"]').trigger('click');
            }
        });
    }



    $form.data('data', args);

    $.lprShowBlock( $form );
    return $form.css(args).show();
}
function _ready(){
    $body = $(document.body)
    $('.lpr-question-multi-choice').lprMultiChoiceQuestion();
    $('.lpr-question-true-or-false').lprTrueOrFalseQuestion();
    $('.lpr-question-single-choice').lprSingleChoiceQuestion();


    var select = $('#lpr-question-options-wrap select.lpr-question-types')

    select
        .click(function(){ return false;})
        .change( loadQuestionSettings )
        .appendTo($('.hndle', select.closest('.postbox')))
        .show()
    questionActions();
    $('#lpr-quiz-questions').sortable({
        handle: '.lpr-question-head',
        axis: 'y',
        start: function(evt, ui){
            $('.lpr-question-content', ui.item).css("display", "none");
            $(ui.item).css("height", "")
            $('.ui-sortable-placeholder', this).height($(ui.item).height());
        },
        stop: function(evt, ui){
            $('.lpr-question-content', ui.item).css("display", "");
        }
    });
    var $button_add_new_question_type = $('#lpr-add-new-question-type');
    $('button:first', $button_add_new_question_type).click(function(){
        var type = $(this).attr('data-type'),
            args = $.extend( { type: type }, $(this).offset() )
            $form = showFormQuickAddQuestion(args);

        $form.css("top", args.top - $form.outerHeight() - 5);
        $('select', $form).val(type);
        $('input', $form).val('').focus();

        $button_add_new_question_type.data('bg') && $button_add_new_question_type.data('bg').trigger('click')
    }).hover(function(){
        $button_add_new_question_type.data('bg') && $button_add_new_question_type.data('bg').trigger('click')
    });
    $('#lpr-add-new-question-type .dropdown-toggle').hover(function(){
        var $this = $(this);
        if( $this.hasClass('hovering') ) return
        $this.addClass('hovering')
        var bg = $('<div />').css({
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            zIndex: 1000
        }).appendTo( $(document.body) );
        bg.on('mouseenter click', function(){
            $(this).remove();
            $button_add_new_question_type.css("z-index", "");
            dropdown.hide();
            $this.removeClass('hovering')
        });
        $button_add_new_question_type.data('bg', bg)
        var dropdown = $this.siblings('.dropdown-menu').show();
        $button_add_new_question_type.css({
            zIndex: 1010
        })
    });
    $('.dropdown-menu a', $button_add_new_question_type).click(function(e){
        e.preventDefault();
        $('button:first', $button_add_new_question_type).attr('data-type', $(this).attr('rel')).trigger('click')
    })

    $(document).on('change', '.lpr-question-head > select', function(){
        var $select = $(this),
            $container = $select.closest('.lpr-question');
            old_type = $select.attr('data-type'),
            new_type = $select.val(),
            $qq = $('.lpr-question', $container);
        $container.block_ui();
        $select.attr('data-type', new_type);
        $container.removeClass('lpr-question-' + old_type.replace(/_/g, '-')).addClass('lpr-question-' + new_type.replace(/_/g, '-'));
        var data = {
            action: 'lpr_load_question_settings',
            question_id: $container.data('id') ,
            type: new_type
        };
        data = lprHook.applyFilters( 'lpr_admin_load_question_settings_args', data, new_type, $container, old_type );
        $.post(ajaxurl, data,
            function(res){

                $new = $(res);
                $container.replaceWith( $new );
                lprHook.doAction('lpr_admin_question_html', $new, new_type, old_type);
                $container.unblock_ui();
            }
        , 'text');
    }).on('focusin', '.lpr-question-title input', function(){
        $(this).removeClass('inactive');
    }).on('focusout', '.lpr-question-title input', function(){
        $(this).addClass('inactive');
    });

    $('.lpr-question-title input').fitSizeWithText({auto: true});
    $doc.on('click', '.lpr-questions-toggle a', function(evt){
        evt.preventDefault();
        var $button = $(this),
            action = $button.data('action');
        $('.lpr-question .lpr-question-head a[data-action="'+action+'"]').trigger('click');
        $button.hide().siblings().show();
    });

    if( $('.lpr-question .lpr-question-content:visible').length ){
        $('.lpr-questions-toggle a[data-action="collapse"]').show().siblings().hide();
    }else{
        $('.lpr-questions-toggle a[data-action="expand"]').show().siblings().hide();
    }


    lprHook.addFilter( 'lpr_admin_load_question_settings_args', function ( data, type, $old_question, old_type){
        data.options = {
            answer: []
        };

        $('tbody tr', $old_question).each(function(){
            var opt = {};
            if( old_type == 'multi_choice' ) {
                opt.is_true = $('td:eq(1) input[type="checkbox"]', this).is(":checked") ? 1 : 0;
            }else if( old_type == 'single_choice' || old_type == 'true_or_false' ){
                opt.is_true = $('.lpr-is-true-answer input[type="radio"]:checked', this).length ? 1 : 0;
            }
            opt.text = $('input[type="text"].lpr-answer-text', this).val();
            if( opt.text ) {
                data.options.answer.push(opt);
            }
        })
        return data
    })

    lprHook.addFilter( 'lpr_admin_question_html', function( $question, type, $old_question, old_type ){
        alert($question+','+ type+','+ $old_question+','+ old_type)
        if( ! old_type || ( type == old_type ) ) return $question;
        var $return = null;
        switch ( old_type ){
            case 'true_or_false':
                if( type == 'multi_choice' ){
                    var html = $old_question.outerHTML();
                    html = html.replace(/type=\"radio\"/g, 'type="checkbox"');
                    $return =  $(html).lprMultiChoiceQuestion();
                }else if( type == 'single_choice' ){
                    $return =  $old_question;
                }
                break;
            case 'multi_choice':
                if( type == 'true_or_false' ){
                    var html = $old_question.outerHTML();
                    html = html.replace(/type=\"checkbox\"/g, 'type="radio"');
                    html = $(html);
                    $( 'tbody tr:gt(1)', html).remove();
                    $return =  html.lprTrueOrFalseQuestion();
                }else if( type == 'single_choice' ){
                    console.log('xxx');
                    $('tbody input[type="checkbox"]', $old_question).each(function(){
                        var $c = $(this),
                            $r = $( $c.clone().outerHTML().replace(/type="checkbox"/, 'type="radio"') );
                        if( $c.is(":checked") ) $r.attr("checked", true);
                        $c.replaceWith($r);
                    });
                    $return = $old_question;
                }
                break;
            case 'single_choice':
                if( type == 'true_or_false' ){
                    $( 'tbody tr:gt(1)', $old_question).remove();
                    $return =  $old_question;
                }else if( type == 'multi_choice' ){
                    var html = $old_question.outerHTML();
                    html = html.replace(/type=\"radio\"/g, 'type="checkbox"');
                    $return =  $(html).lprMultiChoiceQuestion();
                    alert()
                }
                break;
        }
        if( old_type ) {
            $return.removeClass('lpr-question-' + old_type.replace(/_/g, '-')).addClass('lpr-question-' + type.replace(/_/g, '-') )
        }
        return $return;
    } )

    lprHook.addAction('lpr_admin_quiz_question_html', function($new, type){
        $('.lpr-question').filter(function(){ return !$new.is(this)}).find('.lpr-question-head a[data-action="collapse"]').trigger('click');
        $new.scrollTo({offset: 50});
    })
}

$doc.ready(_ready);

})(jQuery);