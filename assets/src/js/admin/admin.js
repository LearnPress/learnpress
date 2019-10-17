/**
 * JS code may run in all pages in admin.
 *
 * @version 3.2.6
 */
//import Utils from './utils';
//import Test from './test';

import Update from './pages/update';
var LP = LP || {};
;(function () {
    const $ = jQuery;

    const updateItemPreview = function updateItemPreview() {
        $.ajax({
            url: '',
            data: {
                'lp-ajax': 'toggle_item_preview',
                item_id: this.value,
                previewable: this.checked ? 'yes' : 'no',
                nonce: $(this).attr('data-nonce')
            },
            dataType: 'text',
            success: function (response) {
                response = LP.parseJSON(response);
            }
        });
    };

    /**
     * Callback event for button to creating pages inside error message.
     *
     * @param {Event} e
     */
    const createPages = function createPages(e) {
        var $button = $(this).addClass('disabled');
        e.preventDefault();
        $.post({
            url: $button.attr('href'),
            data: {
                'lp-ajax': 'create-pages'
            },
            dataType: 'text',
            success: function (res) {
                var $message = $button.closest('.lp-notice').html('<p>' + res + '</p>');
                setTimeout(function () {
                    $message.fadeOut()
                }, 2000);
            }
        });
    };

    const hideUpgradeMessage = function hideUpgradeMessage(e) {
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
    };

    const pluginActions = function pluginActions(e) {

        // Premium addon
        if ($(e.target).hasClass('buy-now')) {
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
    };

    const preventDefault = function preventDefault(e) {
        e.preventDefault();
        return false;
    };

    const ajaxCreateQuestionType = function ajaxCreateQuestionType(e) {
        const type = $(e.target).data('type') || $(this).find('li:first').data('type');
        const ajaxUrl = window['lpAdminSettings'].ajax;

        $.ajax({
            url: ajaxUrl,
            data: {
                'lp-ajax': 'create-question-type',
                type
            }
        });
    };

    LP.createButtonAddNewQuestion = function () {
        if (!$(document.body).hasClass('post-type-lp_question')) {
            return;
        }
        var $addNew = $(document).find('.page-title-action');

        if(!$addNew.length){
            return;
        }

        var types = window['lpAdminSettings'].questionTypes;
        var $newButton = $('<div id="button-new-question" class="page-title-action"><div></div></div>');
        var url = $addNew.attr('href');

        for (var type in types) {
            $newButton.find('div').append(`<a href="${url.addQueryVar('question-type', type)}">${types[type]}</a>`)
        }

        $newButton.find('span').html($addNew.text());

        //$addNew.append($newButton);
        $newButton.insertBefore($addNew);
        $newButton.prepend($addNew.removeClass('page-title-action'));
    };

    var onReady = function onReady() {

        $('.learn-press-dropdown-pages').LP('DropdownPages');
        $('.learn-press-advertisement-slider').LP('Advertisement', 'a', 's').appendTo($('#wpbody-content'));
        $('.learn-press-toggle-item-preview').on('change', updateItemPreview);
        $('.learn-press-tip').LP('QuickTip');
        //$('.learn-press-tabs').LP('AdminTab');

        LP.createButtonAddNewQuestion();

        $(document)
            .on('click', '#learn-press-create-pages', createPages)
            .on('click', '.lp-upgrade-notice .close-notice', hideUpgradeMessage)
            .on('click', '.plugin-action-buttons a', pluginActions)
            .on('click', '[data-remove-confirm]', preventDefault)
            .on('mousedown', '.lp-sortable-handle', function (e) {
                $('html, body').addClass('lp-item-moving');
                $(e.target).closest('.lp-sortable-handle').css('cursor', 'inherit');
            })
            .on('mouseup', function (e) {
                $('html, body').removeClass('lp-item-moving');
                $('.lp-sortable-handle').css('cursor', '');
            })
            //.on('click', '#button-new-question', ajaxCreateQuestionType);
    };

    $(document).ready(onReady)
})();