/**
 * Single Quiz functions
 *
 * @author ThimPress
 * @package LearnPress/JS
 * @version 1.1
 */
;(function ($) {

    !Number.prototype.toTime && (Number.prototype.toTime = function () {

        var MINUTE_IN_SECONDS = 60,
            HOUR_IN_SECONDS = 3600,
            DAY_IN_SECONDS = 24 * 3600,
            seconds = this + 0,
            str = '';

        if (seconds > DAY_IN_SECONDS) {
            var days = Math.ceil(seconds / DAY_IN_SECONDS);
            str = days + ( days > 1 ? ' days left' : ' day left' );
        } else {
            var hours = Math.floor(seconds / HOUR_IN_SECONDS),
                minutes = 0;
            seconds = hours ? seconds % (hours * HOUR_IN_SECONDS) : seconds;
            minutes = Math.floor(seconds / MINUTE_IN_SECONDS);
            seconds = minutes ? seconds % (minutes * MINUTE_IN_SECONDS) : seconds;


            if (hours && hours < 10) {
                hours = '0' + hours;
            }

            if (minutes < 10) {
                minutes = '0' + minutes;
            }

            if (seconds < 10) {
                seconds = '0' + seconds;
            }

            str = hours + ':' + minutes + ':' + seconds;
        }

        return str;
    });

    function LP_Quiz(settings) {

        var self = this,
            thisSettings = $.extend({}, settings),
            remainingTime = thisSettings.remainingTime,
            timerCountdown = null,
            $timeElement = $('.quiz-countdown .progress-number'),
            callbackEvents = new LP.Event_Callback(this);

        function timeCountdown() {
            stopCountdown();
            var overtime = thisSettings.remainingTime <= 0,
                isCompleted = -1 !== $.inArray(settings.status, ['completed', 'finished']);

            if (isCompleted) {
                return;
            }

            if (overtime) {
                $('form.complete-quiz').off('submit.learn-press-confirm');
                callbackEvents.callEvent('finish');
                return;
            }
            thisSettings.remainingTime--;
            timerCountdown = setTimeout(timeCountdown, 1000);
        }

        function stopCountdown() {
            timerCountdown && clearTimeout(timerCountdown);
        }

        function initCountdown() {
            thisSettings.watchChange('remainingTime', function (prop, oldVal, newVal) {
                remainingTime = newVal;
                onTick.apply(self, [oldVal, newVal]);
                return newVal;
            });
        }

        function onTick(oldVal, newVal) {
            callbackEvents.callEvent('tick', [newVal]);
            if (newVal <= 0) {
                stopCountdown();

                // Disable confirm message
                $('form.complete-quiz').off('submit.learn-press-confirm');
                callbackEvents.callEvent('finish');
            }
        }

        function showTime() {
            if (remainingTime < 0) {
                remainingTime = 0;
            }
            $timeElement.html(remainingTime.toTime());
        }

        function submit() {
            $('form.complete-quiz').submit();
        }

        function beforeSubmit() {
            var $form = $(this),
                $input = $form.find('input[name="nav-type"]'),
                navType = $form[0].className.match(/(prev|next|skip)-question/);

            if (!$input.length) {
                $input = $('<input type="hidden" name="nav-type" />').val(navType[0]).appendTo($form);
            }
        }

        function init() {
            if (thisSettings.onTick) {
                self.on('tick', thisSettings.onTick);
            }

            if (thisSettings.onFinish) {
                self.on('finish', thisSettings.onFinish);
            }

            $(document).on('submit', '.next-question, .prev-question, .skip-question', beforeSubmit);
            initCountdown();
            timeCountdown();
        }

        // Events
        this.on = callbackEvents.on;
        this.off = callbackEvents.off;

        if (thisSettings.totalTime > 0) {
            this.on('tick.showTime', showTime);
            this.on('finish.submit', submit);
        }

        this.getRemainingTime = function () {
            return remainingTime;
        }

        if(thisSettings.remainingTime <= 0){
            // Disable confirm message
            $('form.complete-quiz').off('submit.learn-press-confirm');
            callbackEvents.callEvent('finish');
        }

        init();
    }

    $(document).ready(function () {
        if (typeof lpQuizSettings !== 'undefined') {
            window.lpQuiz = new LP_Quiz(lpQuizSettings);
        }
    })


})(jQuery);