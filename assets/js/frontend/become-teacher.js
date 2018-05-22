/**
 * Become a Teacher form handler
 *
 * @author ThimPress
 * @package LearnPress/JS
 * @version 3.0.0
 */
if (typeof jQuery === 'undefined') {
    console.log('jQuery is not defined');
} else {
    (function ($) {
        $(document).ready(function () {
            $('form[name="become-teacher-form"]').each(function () {
                var $form = $(this),
                    $submit = $form.find('button[type="submit"]'),
                    hideMessages = function () {
                        $('.learn-press-error, .learn-press-message').fadeOut('fast', function () {
                            $(this).remove()
                        });
                    },
                    showMessages = function (messages) {
                        var m = [];
                        if ($.isPlainObject(messages)) {
                            for (var i in messages) {
                                m.push($(messages[i]));
                            }
                        } else if ($.isArray(messages)) {
                            m = messages.reverse();
                        } else {
                            m = [messages];
                        }
                        for (var i = 0; i < m.length; i++) {
                            $(m[i]).insertBefore($form);
                        }

                    },
                    blockForm = function (block) {
                        return $form.find('input, select, button, textarea')
                            .prop('disabled', !!block)
                    },
                    beforeSend = function () {
                        hideMessages();

                        blockForm(true)
                            .filter($submit)
                            .data('origin-text', $submit.text())
                            .html($submit.data('text'));

                    },
                    ajaxSuccess = function (response) {
                        response = LP.parseJSON(response);
                        if (response.message) {
                            showMessages(response.message)
                        }

                        blockForm().filter($submit).html($submit.data('origin-text'));

                        if (response.result === 'success') {
                            $form.remove();
                        } else {
                            $submit.prop('disabled', false);
                            $submit.html($submit.data('text'));
                        }

                    },
                    ajaxError = function (response) {
                        response = LP.parseJSON(response);

                        if (response.message) {
                            showMessages(response.message)
                        }

                        blockForm().filter($submit).html($submit.data('origin-text'));
                    };

                $form.submit(function () {
                    if ($form.triggerHandler('become_teacher_send') !== false) {
                        $.ajax({
                            url: window.location.href.addQueryVar('lp-ajax', 'request-become-a-teacher'),
                            data: $form.serialize(),
                            dataType: 'text',
                            type: 'post',
                            beforeSend: beforeSend,
                            success: ajaxSuccess,
                            error: ajaxError
                        });
                    }
                    return false;
                });
            })
        });
    })(jQuery);
}