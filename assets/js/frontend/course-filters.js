;(function ($) {
	"use strict";
	$.fn.courseFilters = function (options) {
		return $.each(this, function () {
			var defaults = {
					attribute_operator: 'and',
					value_operator    : 'and',
					ajax_filter       : 1,
					button_filter     : 1
				},
				$widget = $(this),
				widgetID = $widget.attr('id'),
				filterUrl = window.location.href.addQueryVar('course-filter', 'yes'),
				oldFilterUrl = filterUrl,
				$buttonFilter = $widget.find('.lp-button-filter'),
				$buttonReset = $widget.find('.lp-button-reset-filter');
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

			function hasFiltered() {
				return $('#' + widgetID + ' .lp-course-attribute-values li.active').length;
			}

			function validateUrl(url) {
				// remove paged in query
				url = url.replace(/\/page\/[0-9]+/, '').removeQueryVar('paged');
				return url;
			}

			function doFilter() {
				filterUrl = validateUrl(filterUrl);
				if (oldFilterUrl == filterUrl) {
					return;
				}
				var useAjax = $widget.find('.lp-course-attributes.course-filters').data('ajax') == 'yes';
				if (useAjax) {
					LP.setUrl(filterUrl);
					LP.blockContent();
					$.ajax({
						url    : filterUrl,
						success: function (res) {
							var $html = $(res).contents(),
								$newContent = $html.find('.entry-content'),
								$newWidget = $html.find('#' + widgetID);
							$('.entry-content').replaceWith($newContent);
							$('#' + widgetID).replaceWith($newWidget);
							LP.unblockContent();
						}
					});
				} else {
					LP.reload(filterUrl);
				}
				oldFilterUrl = filterUrl;
				$buttonFilter.prop('disabled', true);
			}

			function toggleControls() {
				filterUrl = validateUrl(filterUrl);
				var url = filterUrl;
				if (!hasFiltered()) {
					url = url
						.removeQueryVar('attribute_operator')
						.removeQueryVar('value_operator')
						.removeQueryVar('course-filter');
				}
				if ($buttonFilter.length == 0) {
					doFilter();
				} else {
					if (filterUrl == oldFilterUrl) {
						$buttonFilter.prop('disabled', true);
					} else {
						$buttonFilter.prop('disabled', false);
					}
					$buttonReset.prop('disabled', !hasFiltered());
				}
				LP.setUrl(url);
			}

			if ($buttonFilter.length) {
				$buttonFilter.off('click').on('click', function () {
					doFilter()
				})
			}
			$buttonReset.off('click').on('click', function () {
				$('#' + widgetID + ' .lp-course-attribute-values li.active a').each(function () {
					var $this = $(this),
						$li = $this.parent(),
						$attribute = $li.closest('.lp-course-attribute-values').parent(),
						attribute = $attribute.data('attribute'),
						value = $li.data('value');
					$li.removeClass('active');
					filterUrl = filterUrl.removeQueryVar(attribute);
				});
				$(this).prop('disabled', true);
				toggleControls();
			})
			$(document)
				.off('click', '#' + widgetID + ' .lp-course-attribute-values a')
				.on('click', '#' + widgetID + ' .lp-course-attribute-values a', function (e) {
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
					toggleControls();
				});
		});
	}
})(jQuery);