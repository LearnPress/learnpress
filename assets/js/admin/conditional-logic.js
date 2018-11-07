/**
 * Conditional Logic for metabox fields
 *
 * @author ThimPress
 * @package LearnPress/JS
 * @version 3.0.0
 */
;(function ($) {
    window.conditional_logic_gray_state = function (state, field) {
        if (state) {
            $(this).removeClass('disabled')
        } else {
            $(this).addClass('disabled')
        }
    };
    var Conditional_Logic = window.Conditional_Logic = function (options) {
        this.options = $.extend({}, options || {});
        this.updateAll();
    };

    Conditional_Logic.prototype = $.extend(Conditional_Logic.prototype, {
        evaluate: function (changedId, conditionals) {
            if (!conditionals) {
                return undefined;
            }
            if (!conditionals || !$.isArray(conditionals.conditional)) {
                return undefined;
            }
            var show = undefined,
                controls = conditionals.conditional;
            for (var i in controls) {
                var _show = this.evaluateRequirement(controls[i]),
                    operator = (controls[i].combine || 'and').toLowerCase();
                if (_show !== undefined && show !== undefined) {
                    if (operator === 'and') {
                        show = show && _show;
                    } else {
                        show = show || _show;
                    }
                } else if (show === undefined) {
                    show = _show;
                }
            }
            return show;
        },
        evaluateRequirement: function (requirement) {
            if (!requirement) {
                return undefined;
            }
            if (!requirement['field']) {
                return undefined;
            }
            if (requirement['compare'] === undefined) {
                requirement['compare'] = '=';
            }
            var control = $('#field-' + requirement.field);
            switch (requirement['state']) {
                case 'show':
                    return control.is(':visible');
                    break;
                case 'hide':
                    return !control.is(':visible');
                    break;
                default:
                    var value = '';
                    switch (this.getFieldType(control)) {
                        case 'yes-no':
                            var $chk = control.find('input[type="checkbox"]');
                            value = $chk.is(':checked') ? $chk.val() : '';
                            break;
                        case 'radio':
                            value = control.find('input:checked').val();
                            break;
                        default:
                            value = control.find('input, select').val();

                    }

                    return this.compare(requirement['value'], value, requirement['compare']);
            }
        },

        compare: function (value2, value1, operator) {
            var show = undefined;
            switch (operator) {
                case '===':
                    show = (value1 === value2);
                    break;
                case '==':
                case '=':
                case 'equals':
                case 'equal':
                    show = (value1 === value2);
                    break;
                case '!==':
                    show = (value1 !== value2);
                    break;
                case '!=':
                case 'not equal':
                    show = (value1 !== value2);
                    break;
                case '>=':
                case 'greater or equal':
                case 'equal or greater':
                    show = (value1 >= value2);
                    break;
                case '<=':
                case 'smaller or equal':
                case 'equal or smaller':
                    show = (value1 <= value2);
                    break;
                case '>':
                case 'greater':
                    show = (value1 > value2);
                    break;
                case '<':
                case 'smaller':
                    show = (value1 < value2);
                    break;
                case 'contains':
                case 'in':
                    var _array, _string;
                    if ($.isArray(value1) && !$.isArray(value2)) {
                        _array = value1;
                        _string = value2;
                    } else if ($.isArray(value2) && !$.isArray(value1)) {
                        _array = value2;
                        _string = value1;
                    }
                    if (_array && _string) {
                        if (-1 === $.inArray(_string, _array)) {
                            show = false;
                        }
                    } else {
                        if (-1 === value1.indexOf(value2) && -1 === value2.indexOf(value1)) {
                            show = false;
                        }
                    }
                    break;
                default:
                    show = (value1 === value2);
            }
            if (show !== undefined) {
                return show;
            }
            return true;
        },
        hasConditional: function (source, target) {
            if (!this.options.conditionals) {
                return;
            }
            if (!this.options.conditionals[target]) {
                return false;
            }
            for (var i in this.options.conditionals[target]['conditional']) {
                if (this.options.conditionals[target]['conditional'][i].field === source) {
                    return this.options.conditionals[target];
                }
            }
            return false;
        },
        update: function (changedField, $fields) {
            var $changedField = $(changedField),
                id = this.getFieldName($changedField);
            $fields = $fields || $('.rwmb-field');
            _.forEach($fields, function (field) {
                var thisField = $(field),
                    thisId = this.getFieldName(thisField);

                if (thisId === id) {
                    return;
                }
                var conditional = this.hasConditional(id, thisId);

                if (!conditional) {
                    return;
                }
                var show = this.evaluate($changedField, conditional);

                if (show !== undefined) {
                    if (conditional.state === 'hide') {
                        show = !show;
                    }
                    if ($.isFunction(window[conditional.state_callback])) {
                        window[conditional.state_callback].call(thisField, show, thisField)
                    } else {
                        thisField.toggle(show);
                    }
                }
            }, this);
        },
        updateAll: function () {
            var $fields = $('.rwmb-field'),
                that = this;
            _.forEach($fields, function (field) {
                var $field = $(field),
                    type = this.getFieldType($field),
                    id = $field.find('.rwmb-field-name').val();
                if (!id) {
                    return;
                }
                $field.attr('id', 'field-' + id);

                if (-1 === _.indexOf(this.supportFields, type)) {
                    return;
                }
                $field.find('input, select, textarea').on('change', function () {
                    that.update($(this).closest('.rwmb-field'), $fields);
                }).trigger('change');
            }, this);
        },
        getFieldType: function (field) {
            var $field = $(field);
            if ($field.length === 0) {
                return false;
            }
            var className = $field.get(0).className,
                m = className.match(/rwmb-([^\s]*)-wrapper/);
            if (m) {
                return m[1];
            }
            return false;
        },
        getFieldName: function (field) {
            return $(field).find('.rwmb-field-name').val();
        },
        supportFields: ['yes-no', 'text', 'number', 'radio']
    });
    $(document).ready(function () {
        new Conditional_Logic({conditionals: lp_conditional_logic});
    })
})(jQuery);
