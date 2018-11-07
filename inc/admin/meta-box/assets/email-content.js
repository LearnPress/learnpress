;(function ($) {
    $(document).ready(function () {
        $('.rwmb-email-content-wrapper').each(function () {
            var $field = $(this),
                $select = $field.find('select.lp-email-format'),
                $templates = $field.find('.learn-press-email-template'),
                $variables = $field.find('.learn-press-email-variables');

            function _insertVariableToEditor(edId, variable) {
                var ed = null,
                    editorId = null,
                    activeEditor = tinyMCE.activeEditor;
                for (editorId in tinyMCE.editors) {
                    if (editorId == edId) {
                        break;
                    }
                    editorId = null;
                }
                if (!editorId) {
                    _insertVariableToTextarea(edId, variable);
                    return;
                }
                if (activeEditor && $(activeEditor.getElement()).attr('id') == editorId) {
                    activeEditor.execCommand('insertHTML', false, variable);
                    if ($(activeEditor.getElement()).is(':visible')) {
                        _insertVariableToTextarea(edId, variable);
                    }
                } else {

                }
            }

            function _insertVariableToTextarea(eId, varibale) {
                var $el = $('#' + eId).get(0);
                if (document.selection) {
                    $el.focus();
                    sel = document.selection.createRange();
                    sel.text = varibale;
                    $el.focus();
                }
                else if ($el.selectionStart || $el.selectionStart == '0') {
                    var startPos = $el.selectionStart;
                    var endPos = $el.selectionEnd;
                    var scrollTop = $el.scrollTop;
                    $el.value = $el.value.substring(0, startPos) + varibale + $el.value.substring(endPos, $el.value.length);
                    $el.focus();
                    $el.selectionStart = startPos + varibale.length;
                    $el.selectionEnd = startPos + varibale.length;
                    $el.scrollTop = scrollTop;
                } else {
                    $el.value += varibale;
                    $el.focus();
                }
            }

            $select.on('change', function () {
                $templates.filter('.' + this.value).removeClass('hide-if-js').siblings().addClass('hide-if-js');
            }).trigger('change');

            $variables.each(function () {
                var $list = $(this),
                    hasEditor = $list.hasClass('has-editor');
                $list.on('click', 'li', function () {
                    if (hasEditor) {
                        _insertVariableToEditor($list.attr('data-target'), $(this).data('variable'));
                    } else {
                        _insertVariableToTextarea($list.attr('data-target'), $(this).data('variable'));
                    }
                })
            });
        });
    })
})(jQuery);