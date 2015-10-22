;(function($){
	var $doc = $(document);
	function _ready(){

		var $sandbox_mode   = $('#learn_press_paypal_sandbox_mode'),
			$paypal_type    = $('#learn_press_paypal_type');
		$paypal_type.change(function(){
			$('.learn_press_paypal_type_security').toggleClass( 'hide-if-js', 'security' != this.value );
		});
		$sandbox_mode.change(function(){
			this.checked ? $('.sandbox input').removeAttr( 'readonly' ) : $('.sandbox input').attr( 'readonly', true );
		});

		$('#learn_press_paypal_enable').change(function(){
			var $rows = $(this).closest('tr').siblings('tr');
			if( this.checked ){
				$rows.css("display", "");
			}else{
				$rows.css("display", "none");
			}
		}).trigger('change');

		/*var $elem_conds = $('[data-cond]');
		$elem_conds.each(function(){
			var $elem = $(this),
				cond = $elem.attr('data-cond'),
				$conds = $('[data-'+cond+']');
			if(!$conds.length) return;
			$elem.on('change _update', function(){
				$conds.each(function(){
					var $cond = $(this);
					if($elem.is(':checked') && $cond.attr('data-'+cond+'') == 'yes'){
						$cond.show();
					}else{
						$cond.hide();
					}
				})
			}).trigger('_change');
		})*/
	}
	$doc.ready(_ready);
})(jQuery);