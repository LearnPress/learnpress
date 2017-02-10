;(function ($) {
	"use strict";
	$.fn.courseFilters = function (options) {
		return $.each(this, function () {
			var defaults = {
					attribute_operator: 'and',
					value_operator    : 'and',
					ajax_filter        : 1,
					button_filter      : 1
				},
				$widget = $(this),
				filterUrl = window.location.href.addQueryVar('course-filter', 'yes'),
				$buttonFilter = $widget.find('.lp-button-filter');
			options = $.extend({}, defaults, options || {});

			filterUrl = filterUrl
				.addQueryVar('attribute_operator', options.attribute_operator)
				.addQueryVar('value_operator', options.value_operator);
			function addAttribute(attribute, value) {
				var attributes = filterUrl.getQueryVar(attribute);
				if (!attributes) {
					attributes = value;
				} else {
					if (attributes.indexOf(value) == -1) {
						attributes += ',' + value;
					}
				}
				filterUrl = filterUrl.addQueryVar(attribute, attributes);
				return filterUrl;
			}

			function removeAttribute(attribute, value) {
				var attributes = filterUrl.getQueryVar(attribute);
				if (attributes) {
					attributes = attributes.replace(value, '').split(',').filter(function (a, b) {
						return a + '' !== '';
					}).join(',');
					if (attributes) {
						filterUrl = filterUrl.addQueryVar(attribute, attributes);
					} else {
						filterUrl = filterUrl.removeQueryVar(attribute);
					}
				}
				return filterUrl;
			}

			function doFilter() {
				var useAjax = $widget.find('.lp-course-attributes.course-filters').data('ajax') == 'yes';
				if (useAjax) {
					LP.setUrl(filterUrl);
					$.ajax({
						url    : filterUrl,
						success: function (res) {
							var $newContent = $(res).contents().find('.entry-content');
							$('.entry-content').replaceWith($newContent);
						}
					});
				} else {
					LP.reload(filterUrl);
				}
			}

			$(document).on('click', '.lp-course-attribute-values a', function (e) {
				e.preventDefault();
				var $this = $(this),
					$li = $this.parent(),
					$attribute = $li.closest('.lp-course-attribute-values').parent(),
					attribute = $attribute.data('attribute'),
					value = $li.data('value');
				$li.toggleClass('active');
				if ($li.hasClass('active')) {
					filterUrl = addAttribute(attribute, value);
				} else {
					filterUrl = removeAttribute(attribute, value);
				}
				if ($buttonFilter.length == 0) {
					doFilter();
				} else {
					$buttonFilter.on('click', function () {
						doFilter()
					})
				}
			});

		});
	}
})(jQuery);