;(function ($) {
	"use strict";
	var filterUrl = window.location.href;

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
				console.log(a);
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

	$(document).ready(function () {
		filterUrl = filterUrl.addQueryVar('course-filter', 'yes');
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
			LP.reload(filterUrl)
		})
	});
})(jQuery);