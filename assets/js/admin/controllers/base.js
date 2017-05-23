/**
 * Base controller
 *
 * @plugin LearnPress
 * @author ThimPress
 * @package LearnPress/AdminJS/Controller/Base
 * @version 3.0
 */
;(function ($) {
    /**
     * Base controller
     *
     * @param $scope
     */
    window['learn-press.base.controller'] = function ($scope, $compile, $element, $timeout) {
        $element = $($element);
        angular.extend($scope, {
            $element: $element,
            $doc: $(document),
            objectId: 0,
            noncePrefix: '',
            test: function () {
                console.log('Test function from Base Controller');
            },
            tooltip: function ($el) {
                var args = {title: 'data-tooltip'};
                $el.each(function () {
                    if ($el.hasClass('.learn-press-tooltip')) {
                        $el.tipsy(args);
                    }
                    $el.find('.learn-press-tooltip').tipsy(args);
                })
            },
            trustHtml: function (html) {
            },
            getNonce: function (action) {
                var selector = action ? 'input[name="' + this.noncePrefix + 'nonce-' + action + '"]' : 'input[name="' + this.noncePrefix + 'nonce"]';
                return this.getElement(selector).val();
            },
            getElement: function (selector) {
                return selector ? this.$element.find(selector) : this.$element;
            },
            applyFilters: function (filter) {
                var rawData = arguments[1],
                    filteredData = undefined,
                    args = [];
                _.forEach(arguments, function (arg) {
                    args.push(arg)
                })
                this.$doc.triggerHandler.apply(this.$doc, args);
                return filteredData !== undefined ? filteredData : rawData;
            },
            getScreenPostId: function (type) {
                return (type && type === $('#post_type').val()) ? parseInt($('#post_ID').val()) : false;
            },
            getAjaxUrl: function (param) {
                var url = window.location.href;
                if (param) {
                    var p = url.split('#');
                    url = p[0] + (p[0].indexOf('?') === -1 ? '?' : '&');
                } else {
                    param = '';
                }
                return url + param + (p[1] ? '#' + p[1] : '');
            },
            getId: function () {
                return this.objectId ? this.objectId : parseInt(this.getElement().data('id'));
            },
            setId: function (id) {
                this.objectId = id;
            }
        });
    }
})(jQuery);