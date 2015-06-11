if( typeof LearnPress == 'undefined' ) var LearnPress = {};
(function($){
    LearnPress.singleQuizInit = function( args ) {
        $.alerts.overlayOpacity = 0.3;
        $.alerts.overlayColor = '#000';
        var finish = false;
        var $question_answer = '';
        var defaults = {
            quiz_id: 0,
            question_id: 0,
            questions: [],
            time_remaining: 0,
            quiz_started: false,
            quiz_url: null
        };
        this.args           = $.extend({}, defaults, args);
        var current_question_id = this.args.question_id,
            quiz_finished       = this.args.quiz_completed,
            self = this,
            countdown = undefined;

        function get_next_question_id(){
            var pos = $.inArray( current_question_id, self.args.questions );
            if( pos != -1 ){
                pos++;
                if( pos < self.args.questions.length ) {
                    return self.args.questions[pos];
                }
            }
            return undefined;
        }
        function get_prev_question_id(){
            var pos = $.inArray( current_question_id, self.args.questions );
            if( pos != -1 ){
                pos--;
                if( pos >= 0 ) {
                    return self.args.questions[pos];
                }
            }
            return undefined;
        }
        function next_question(){
            var next_id = get_next_question_id();
            var data = {
                    action: 'learn_press_submit_answer',
                    quiz_id: self.args.quiz_id,
                    question_id: current_question_id,
                    question_answer: $('input, select, textarea', $('.quiz-question-nav .question-'+current_question_id)).toJSON(),
                    next_id: next_id
                },
                next_question = $('.quiz-question-nav .question-' + next_id);
            $.post( ajaxurl, data, function(res){

                if( next_id ){
                    current_question_id = next_id;
                    next_question.show().siblings().filter(function(){return this.className.match(/question-[0-9]+/)}).hide();
                    var new_question = $('.quiz-question-nav .question-' + next_id ),
                        prev_question = $('.quiz-question-nav .lp-question-wrap').hide();
                    if( new_question.length == 0 ){
                        new_question = $(res.html);
                        if (prev_question.length) {
                            new_question.insertAfter(prev_question.last());
                        } else {
                            $('.quiz-question-nav').prepend(new_question);
                        }
                    }else{
                        new_question.show();
                    }


                }else{
                    countdown.backward_timer("cancel");
                    if( res.quiz_completed ){
                        window.location.href = window.location.href;
                        return;
                    }
                    quiz_finished = true;
                    $('.quiz-questions .current').removeClass('current');
                    $('.quiz-question-nav .lp-question-wrap').hide();
                    $('.quiz-question-nav').append(res.html);
                    countdown.backward_timer("cancel");
                }
                update_nav_buttons();
            }, 'json')
        }
        function prev_question(){
            var prev_id = get_prev_question_id(),
                prev_question = $('.quiz-question-nav .question-'+prev_id);
            if( prev_question.get(0) ){
                prev_question.show();
                prev_question.siblings('.lp-question-wrap').hide();
                current_question_id = prev_id;
                update_nav_buttons();
            }else{
                var data = {
                    action: 'learn_press_load_question',
                    quiz_id: self.args.quiz_id,
                    question_id: prev_id
                };
                $.post( ajaxurl, data, function(res){
                    if( prev_id ) current_question_id = prev_id
                    var new_question = $(res),
                        next_question = $('.quiz-question-nav .lp-question-wrap').hide();
                    if (prev_question.length) {
                        new_question.beforeAfter(prev_question.first());
                    } else {
                        $('.quiz-question-nav').prepend(new_question);
                    }
                    update_nav_buttons();
                })
            }
        }

        function update_nav_buttons() {

            if( self.args.quiz_started && !quiz_finished ){
                $('.button-start-quiz').hide();
                if ($.inArray(current_question_id, self.args.questions) == 0) {
                    $('.quiz-question-nav-buttons button.prev-question').hide();
                } else {
                    $('.quiz-question-nav-buttons button.prev-question').show();
                }
                $('.quiz-questions .sibdebar-quiz-question-' + current_question_id).addClass('current').siblings().removeClass('current');
                $('.quiz-question-nav-buttons button.next-question').show();

            }else if( quiz_finished ){
                $('.quiz-question-nav-buttons button').hide();
                $('.quiz-questions .current').removeClass('current');
                $('.button-finish-quiz').hide();
            }else{
                $('.quiz-question-nav-buttons button').hide();
            }
        }

        function finish_quiz(){
            if( !quiz_finished ) {
                var data = {
                    action: 'learn_press_submit_answer',
                    quiz_id: self.args.quiz_id,
                    question_id: current_question_id,
                    question_answer: $('input, select, textarea', $('.quiz-question-nav .question-'+current_question_id)).toJSON(),
                    finish: true
                };
                $.post( ajaxurl, data, function(res){
                    window.location.href = window.location.href;
                }, 'json')
            }
        }

        function on_timeout(){
            if( !quiz_finished ){
                jAlert( "The time is over", "Time up!", function () {
                    finish_quiz();
                });
            }
        }
        function init_countdown_timer(){
            countdown = $("#quiz-countdown");
            countdown.backward_timer({
                seconds: self.args.time_remaining,
                format: 'm%:s%',
                on_exhausted: function (timer) {
                    on_timeout.call( this, timer );
                },
                on_tick: function (timer) {
                    var color = (timer.seconds_left <= 5) ? "#F00" : ""
                    timer.target.css('color', color);
                }
            });
            if( self.args.quiz_started ){
                countdown.backward_timer('start');
            }
        }
        function start_quiz(){
            var url = window.location.href;
            $.post( url, {action: 'learn_press_start_quiz', quiz_id: self.args.quiz_id}, function(res){

                var new_question = $('.quiz-question-nav .question-'+current_question_id, res),
                    last_question = $('.quiz-question-nav .lpr-question-wrap:last');
                if( last_question.get(0) ){
                    new_question.insertAfter( last_question );
                }else{
                    $('.quiz-question-nav').prepend( new_question );
                }
                $(this).hide().siblings().show();
                $('.button-start-quiz').hide();
                $('.button-finish-quiz').show();
                countdown.backward_timer('start');
                self.args.quiz_started = true;
                $('.single-quiz').addClass('quiz-started');
                update_nav_buttons();
            });
        }

        $(document).on('click', '.quiz-question-nav-buttons button', function(evt){
            var $button = $(this),
                nav     = $button.data('nav');
            switch(nav){
                case 'prev':
                    prev_question.call(this);
                    break;
                case 'next': //
                    next_question.call(this);
                    break;
            }

        }).on('click', '.button-finish-quiz', function(){
            if( ! confirm( learn_press_js_localize.confirm_finish_quiz ) ) return;
            finish_quiz();
        }).on('click', '.button-retake-quiz', function(){
            return;
            if( !confirm( "The result of current quiz will be deleted!\nAre you sure you want to do it again?" ) ) return;
            $.post( window.location.href, { retake_quiz: 1 }, function(){
                window.location.href = window.location.href;
            })
        });


        $(document).on('click', '.button-start-quiz', function(){
            start_quiz.call( this )
        });
        update_nav_buttons();
        init_countdown_timer();
    }
})(jQuery);
