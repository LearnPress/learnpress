(function( $ ){
	$.fn.extend({
		iosCheckbox: function ( ) {
			$(this).each(function (){
				var $checkbox = $(this),
					$ui = $("<div>",{class: 'ios-ui-select'}).append($("<div>",{class: 'inner'}));
				if ($checkbox.is(":checked")){
					$ui.addClass("checked");
				}
				$checkbox.after($ui)
					.on('update', function(){
						$ui.trigger('update')
					})//.hide().appendTo($ui);
				$ui.on('click update', function (){
					$ui.toggleClass("checked");
					$checkbox.prop('checked', $ui.hasClass("checked")).trigger('change')
				});
			});
		}
	});

	$(document).ready(function(){
		$('.learn-press-checkbox').iosCheckbox();
	});
})(jQuery);