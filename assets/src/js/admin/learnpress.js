;(function () {
    const $ = jQuery;
    const $doc = $(document);
    const $win = $(window);

    const makePaymentsSortable = function makePaymentsSortable() {
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
                    }
                });
            }
        });
    };

    const initTooltips = function initTooltips() {
        $('.learn-press-tooltip').each(function () {
            var $el = $(this),
                args = $.extend({title: 'data-tooltip', offset: 10, gravity: 's'}, $el.data());
            $el.tipsy(args);
        });
    };

    const initSelect2 = function initSelect2() {
        if ($.fn.select2) {
            $('.lp-select-2 select').select2();
        }
    };

    const initSingleCoursePermalink = function initSingleCoursePermalink() {
        $doc
            .on('change', '.learn-press-single-course-permalink input[type="radio"]', function () {
                var $check = $(this),
                    $row = $check.closest('.learn-press-single-course-permalink');
                if ($row.hasClass('custom-base')) {
                    $row.find('input[type="text"]').prop('readonly', false);
                } else {
                    $row.siblings('.custom-base').find('input[type="text"]').prop('readonly', true);
                }
            })
            .on('change', 'input.learn-press-course-base', function () {
                $('#course_permalink_structure').val($(this).val());
            })
            .on('focus', '#course_permalink_structure', function () {
                $('#learn_press_custom_permalink').click();
            })
            .on('change', '#learn_press_courses_page_id', function () {
                $('tr.learn-press-courses-page-id').toggleClass('hide-if-js', !parseInt(this.value))
            });
    };

    const togglePaymentStatus = function togglePaymentStatus(e) {
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
    };

    const updateEmailStatus = function updateEmailStatus() {
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
    };

    const toggleSalePriceSchedule = function toggleSalePriceSchedule() {
        var $el = $(this),
            id = $el.attr('id');

        if (id === '_lp_sale_price_schedule') {
            $(this).hide();
            $('#field-_lp_sale_start, #field-_lp_sale_end').removeClass('hide-if-js');
            $win.trigger('resize.calculate-tab');
        } else {
            $('#_lp_sale_price_schedule').show();
            $('#field-_lp_sale_start, #field-_lp_sale_end').addClass('hide-if-js').find('#_lp_sale_start, #_lp_sale_end').val('');
            $win.trigger('resize.calculate-tab');
        }

        return false;
    };

    const callbackFilterTemplates = function callbackFilterTemplates() {
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
    };

    const toggleEmails = function toggleEmails(e) {
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
    };

    const duplicatePost = function duplicatePost(e) {
        e.preventDefault();

        var _self = $(this),
            _id = _self.data('post-id');

        $.ajax({
            url: '',
            data: {
                'lp-ajax': 'duplicator',
                id: _id
            },
            success: function (response) {
                response = LP.parseJSON(response);

                if (response.success) {
                    window.location.href = response.data;
                } else {
                    alert(response.data);
                }
            }
        });
    };

    const importCourses = function importCourses() {
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

    const onReady = function onReady() {
        makePaymentsSortable();
        initSelect2();
        initTooltips();
        initSingleCoursePermalink();

        $(document)
            .on('click', '.learn-press-payments .status .dashicons', togglePaymentStatus)
            .on('click', '.change-email-status', updateEmailStatus)
            .on('click', '#_lp_sale_price_schedule', toggleSalePriceSchedule)
            .on('click', '#_lp_sale_price_schedule_cancel', toggleSalePriceSchedule)
            .on('click', '.learn-press-filter-template', callbackFilterTemplates)
            .on('click', '#learn-press-enable-emails, #learn-press-disable-emails', toggleEmails)
            .on('click', '.lp-duplicate-row-action .lp-duplicate-post', duplicatePost)
            .on('click', '#learn-press-install-sample-data-notice a', importCourses)


    };

    $(document).ready(onReady)

})();