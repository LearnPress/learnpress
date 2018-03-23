;(function ($) {

    "use strict";

    function makePaymentsSortable() {
        // Make payments sortable
        $('.learn-press-payments.sortable tbody').sortable({
            handle: '.dashicons-menu',
            helper: function (e, ui) {
                ui.children().each(function () {
                    $(this).width($(this).width());
                });
                return ui;
            },
            axis: 'y',
            start: function (event, ui) {

            },
            stop: function (event, ui) {

            },
            update: function (event, ui) {

                var order = $(this).children().map(function () {
                    return $(this).find('input[name="payment-order"]').val()
                }).get();

                $.post({
                    url: '',
                    data: {
                        'lp-ajax': 'update-payment-order',
                        order: order
                    },
                    success: function (response) {
                        console.log(response)
                    }
                });
            }
        });
    }

    function _callbackFilterTemplates() {
        var $link = $(this);

        if ($link.hasClass('current')) {
            return false;
        }

        var $templatesList = $('#learn-press-template-files'),
            $templates = $templatesList.find('tr[data-template]'),
            template = $link.data('template'),
            filter = $link.data('filter');

        $link.addClass('current').siblings('a').removeClass('current');

        if (!template) {

            if (!filter) {
                $templates.removeClass('hide-if-js');
            } else {
                $templates.map(function () {
                    $(this).toggleClass('hide-if-js', $(this).data('filter-' + filter) !== 'yes');
                })
            }

        } else {
            $templates.map(function () {
                $(this).toggleClass('hide-if-js', $(this).data('template') !== template);
            })
        }

        $('#learn-press-no-templates').toggleClass('hide-if-js', !!$templatesList.find('tr.template-row:not(.hide-if-js):first').length);

        return false;
    }

    function toggleEmails(e) {
        e.preventDefault();
        var $button = $(this),
            status = $button.data('status');

        $.ajax({
            url: '',
            data: {
                'lp-ajax': 'update_email_status',
                status: status
            },
            success: function (response) {
                response = LP.parseJSON(response);
                for (var i in response) {
                    $('#email-' + i + ' .status').toggleClass('enabled', response[i]);
                }
            }
        });
    }

    function togglePaymentStatus(e) {
        e.preventDefault();
        var $row = $(this).closest('tr'),
            $button = $(this),
            status = $row.find('.status').hasClass('enabled') ? 'no' : 'yes';

        $.ajax({
            url: '',
            data: {
                'lp-ajax': 'update-payment-status',
                status: status,
                id: $row.data('payment')
            },
            success: function (response) {
                response = LP.parseJSON(response);
                for (var i in response) {
                    $('#payment-' + i + ' .status').toggleClass('enabled', response[i]);
                }
            }
        });
    }

    /**
     * Callback event for button to creating pages inside error message.
     *
     * @param {Event} e
     */
    function createPages(e) {
        var $button = $(this).addClass('disabled');
        e.preventDefault();
        $.post({
            url: $button.attr('href'),
            data: {
                'lp-ajax': 'create-pages'
            },
            dataType: 'text',
            success: function (res) {
                var $message = $button.closest('.error').html('<p>' + res + '</p>');
                setTimeout(function () {
                    $message.fadeOut()
                }, 2000);
            }
        });
    }

    function updateEmailStatus() {
        (function () {
            $.post({
                url: window.location.href,
                data: {
                    'lp-ajax': 'update_email_status',
                    status: $(this).parent().hasClass('enabled') ? 'no' : 'yes',
                    id: $(this).data('id')
                },
                dataType: 'text',
                success: $.proxy(function (res) {
                    res = LP.parseJSON(res);
                    for (var i in res) {
                        $('#email-' + i + ' .status').toggleClass('enabled', res[i]);
                    }
                }, this)
            });
        }).apply(this)
    }

    function updateLessonPreview() {
        $.ajax({
            url: '',
            data: {
                'lp-ajax': 'toggle_lesson_preview',
                lesson_id: this.value,
                previewable: this.checked ? 'yes' : 'no',
                nonce: $(this).attr('data-nonce')
            },
            dataType: 'text',
            success: function (response) {
                response = LP.parseJSON(response);
            }
        });
    }

    var LP_Admin = window.LP_Admin = {
        init: function () {
            var $doc = $(document);
            $doc.on('click', '#learn-press-install-sample-data-notice a', this._importCourses)
                .on('click', '.learn-press-admin-notice-dismiss', this._dismissNotice)
                .on('click', '[data-remove-confirm]', this._confirm);
            setTimeout(function () {
                $('[data-remove-confirm]').each(function () {
                })
            }, 1000);
        },
        _confirm: function (e) {
            e.preventDefault();
            return false;
        },
        _dismissNotice: function (e) {

            var $notice = $(e.target),
                context = $notice.attr('data-context'),
                transient = $notice.attr('data-transient');
            if (context) {
                $.ajax({
                    url: LP_Settings.ajax,
                    data: {
                        action: 'learnpress_dismiss_notice',
                        context: context,
                        transient: transient
                    },
                    success: function (response) {
                        $notice.closest('.updated').fadeOut();
                        $notice.closest('.error').fadeOut();
                    }
                });
                return false;
            }
        },
        _importCourses: function (e) {
            var $container = $('#learn-press-install-sample-data-notice'),
                action = $(this).attr('data-action');
            if (!action) {
                return;
            }
            e.preventDefault();

            if (action === 'yes') {
                $container
                    .find('.install-sample-data-notice').slideUp()
                    .siblings('.install-sample-data-loading').slideDown()
            } else {
                $container.fadeOut();
            }
            $.ajax({
                url: ajaxurl,
                dataType: 'html',
                type: 'post',
                data: {
                    action: 'learnpress_install_sample_data',
                    yes: action
                },
                success: function (response) {
                    response = LP.parseJSON(response);
                    if (response.url) {
                        $.ajax({
                            url: response.url,
                            success: function () {
                                $container
                                    .find('.install-sample-data-notice').html(response.message).slideDown()
                                    .siblings('.install-sample-data-loading').slideUp();
                            }
                        });
                    } else {
                        $container
                            .find('.install-sample-data-notice').html(response.message).slideDown()
                            .siblings('.install-sample-data-loading').slideUp();
                    }
                }
            })
        }
    };

    function initTooltips() {
        $('.learn-press-tooltip').each(function () {
            var $el = $(this),
                args = $.extend({title: 'data-tooltip', offset: 10, gravity: 's'}, $el.data());
            $el.tipsy(args);
        });
    }

    function initSelect2() {
        if ($.fn.select2) {
            $('.lp-select-2 select').select2();
        }
    }

    function toggleSalePriceSchedule() {
        var $el = $(this),
            id = $el.attr('id');

        if (id === '_lp_sale_price_schedule') {
            $(this).hide();
            $('#field-_lp_sale_start, #field-_lp_sale_end').removeClass('hide-if-js');
            $(window).trigger('resize.calculate-tab');
        } else {
            $('#_lp_sale_price_schedule').show();
            $('#field-_lp_sale_start, #field-_lp_sale_end').addClass('hide-if-js').find('#_lp_sale_start, #_lp_sale_end').val('');
            $(window).trigger('resize.calculate-tab');
        }

        return false;
    }

    function hideUpgradeMessage(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.closest('.lp-upgrade-notice').fadeOut();
        $.post({
            url: '',
            data: {
                'lp-hide-upgrade-message': 'yes'
            },
            success: function (res) {
            }
        });
    }

    function pluginActions(e) {

        if ($(e.target).closest('.learnpress-premium-plugin').length) {
            return;
        }

        e.preventDefault();
        var $plugin = $(this).closest('.plugin-card');
        if ($(this).hasClass('updating-message')) {
            return;
        }
        $(this).addClass('updating-message button-working disabled');
        $.ajax({
            url: $(this).attr('href'),
            data: {},
            success: function (r) {
                $.ajax({
                    url: window.location.href,
                    success: function (r) {
                        var $p = $(r).find('#' + $plugin.attr('id'));
                        if ($p.length) {
                            $plugin.replaceWith($p)
                        } else {
                            $plugin.find('.plugin-action-buttons a')
                                .removeClass('updating-message button-working')
                                .html(learn_press_admin_localize.plugin_installed);
                        }
                    }
                })
            }
        });
    }

    var $doc = $(document);

    function _ready() {

        $('.learn-press-dropdown-pages').dropdownPages();
        $('.learn-press-advertisement-slider').LP_Advertisement_Slider();
        $('.learn-press-toggle-lesson-preview').on('change', updateLessonPreview);
        $('.learn-press-tip').QuickTip();

        initTooltips();
        initSelect2();
        makePaymentsSortable();

        $doc.on('click', '.change-email-status', updateEmailStatus)
            .on('click', '#learn-press-enable-emails, #learn-press-disable-emails', toggleEmails)
            .on('click', '#learn-press-create-pages', createPages)
            .on('click', '.learn-press-payments .status .dashicons', togglePaymentStatus)
            .on('click', '#_lp_sale_price_schedule', toggleSalePriceSchedule)
            .on('click', '#_lp_sale_price_schedule_cancel', toggleSalePriceSchedule)
            .on('click', '.lp-upgrade-notice .close-notice', hideUpgradeMessage)
            .on('click', '.plugin-action-buttons a', pluginActions)
            .on('click', '.learn-press-filter-template', _callbackFilterTemplates);

        LP_Admin.init();
    }

    $doc.ready(_ready);
})(jQuery);

if (typeof LP === 'undefined') LP = {};