;(function ($) {
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
                    'lp-ajax': 'ajax-update-payment-order',
                    order: order
                },
                success: function (response) {
                    console.log(response)
                }
            });
        }
    });
    // Document is already ready?
    $(document).ready(function () {
        $('.learn-press-dropdown-pages').dropdownPages();
        $('.learn-press-advertisement-slider').LP_Advertisement_Slider();

        $(document).on('click', '#field-_lp_course_result input[name="_lp_course_result"]', function () {
            var $input = $(this),
                checked = $input.is(':checked');

            if ($input.val() === 'evaluate_final_quiz' && checked) {
                var $passingConditionInput = $('#_lp_passing_condition'),
                    passingCondition = $passingConditionInput.val();


            }
        });

        $(document).on('click', '.change-email-status', function () {
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
                        console.log(res, this)
                        $(this).parent().toggleClass('enabled', res.status)
                    }, this)
                });
            }).apply(this)
        })

        $('.learn-press-tooltip').each(function () {
            var $el = $(this),
                args = $.extend({title: 'data-tooltip', offset: 10, gravity: 's'}, $el.data());
            $el.tipsy(args);
        });

        $(document).on('click', '#_lp_sale_price_schedule', function (e) {
            e.preventDefault();
            $(this).hide();
            $('#field-_lp_sale_start, #field-_lp_sale_end').removeClass('hide-if-js');
            $(window).trigger('resize.calculate-tab');

        }).on('click', '#_lp_sale_price_schedule_cancel', function (e) {
            e.preventDefault();
            $('#_lp_sale_price_schedule').show();
            $('#field-_lp_sale_start, #field-_lp_sale_end').addClass('hide-if-js').find('#_lp_sale_start, #_lp_sale_end').val('');
            $(window).trigger('resize.calculate-tab');
        });
    });

    $(document).on('click', '.wp-list-table .lp-duplicate-row-action', function (e) {
        e.preventDefault();

        var _this = $(this),
            _tr = _this.closest('tr'),
            _id = _tr.find('.check-column input[type="checkbox"]').val(),
            data = {
                id: _id,
                'lp-ajax': 'duplicator'
            };

        $.ajax({
            url: '',
            type: 'POST',
            data: data
        }).done(function (res) {
            if (typeof res.redirect !== 'undefined') {
                window.location.href = res.redirect;
            }
        });

        return false;
    });

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

            if (action == 'yes') {
                $container
                    .find('.install-sample-data-notice').slideUp()
                    .siblings('.install-sample-data-loading').slideDown()
            } else {
                $('#learn-press-install-sample-data-notice').fadeOut();
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
    var $doc = $(document);

    function _ready() {
        LP_Admin.init();
        $(document).on('click', '.plugin-action-buttons a', function (e) {

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
        });
        var $sandbox_mode = $('#learn_press_paypal_sandbox_mode'),
            $paypal_type = $('#learn_press_paypal_type');
        $paypal_type.change(function () {
            $('.learn_press_paypal_type_security').toggleClass('hide-if-js', 'security' != this.value);
        });
        $sandbox_mode.change(function () {
            this.checked ? $('.sandbox input').removeAttr('readonly') : $('.sandbox input').attr('readonly', true);
        });

        $('#learn_press_paypal_enable').change(function () {
            var $rows = $(this).closest('tr').siblings('tr');
            if (this.checked) {
                $rows.css("display", "");
            } else {
                $rows.css("display", "none");
            }
        }).trigger('change');

        $('.learn-press-toggle-lesson-preview').on('change', function () {
            $.ajax({
                url: LP_Settings.ajax,
                data: {
                    action: 'learnpress_toggle_lesson_preview',
                    lesson_id: this.value,
                    previewable: this.checked ? 'yes' : 'no',
                    nonce: $(this).attr('data-nonce')
                },
                dataType: 'text',
                success: function (response) {
                    response = LP.parseJSON(response);
                }
            });
        });
    }

    $doc.ready(_ready);
})(jQuery);

/**
 * Created by foobla on 3/10/2015.
 */

if (typeof LP == 'undefined') LP = {};