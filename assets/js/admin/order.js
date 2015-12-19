;
(function ($) {
	var LP_Order_View = window.LP_Order_View = Backbone.View.extend({
		el            : 'body',
		events        : {
			'click #learn-press-add-order-item'           : '_addItem',
			'click #learn-press-calculate-order-total'    : 'calculateTotal',
			'click #learn-press-courses-result a[data-id]': 'addItem',
			'click .remove-order-item'                    : 'removeItem',
			'keyup #learn-press-search-item-term'         : 'searchItems'
		},
		initialize: function () {
			_.bindAll( this, 'resetModal', 'updateModal' );
			LearnPress.Hook.addAction( 'learn_press_message_box_before_resize', this.resetModal)
			LearnPress.Hook.addAction( 'learn_press_message_box_resize', this.updateModal)
		},
		resetModal: function(height, $app){
			this.$('#learn-press-courses-result').css('height', height - 120).css('overflow', 'auto');
		},
		updateModal: function($app){
			this.$('#learn-press-courses-result').css('height', '').css('overflow', '')
		},
		_addItem      : function (e) {
			var $form = $('#learn-press-modal-add-order-courses');
			if ($form.length == 0) {
				$form = $(wp.template('learn-press-modal-add-order-courses')());
			}
			LearnPress.MessageBox.show($form);
		},
		addItem       : function (e) {
			var that = this,
				$item = $(e.target);
			if (e.ctrlKey) {
				return true;
			}
			$.ajax({
				url     : LearnPress_Settings.ajax,
				data    : {
					action  : 'learnpress_add_item_to_order',
					order_id: parseInt($('input#post_ID').val()),
					item_id : parseInt($item.attr('data-id')),
					nonce   : $('#learn-press-modal-add-order-courses').attr('data-nonce')
				},
				dataType: 'text',
				type    : 'post',
				success : function (response) {
					LearnPress.log(response);
					response = LearnPress.parseJSON(response);
					if (response.result == 'success') {
						var $order_table = that.$('.order-items'),
							$no_item = $order_table.find('.no-order-items');
						$(response.item_html).insertBefore($no_item);
						$order_table.find('.order-subtotal').html(response.order_data.subtotal_html);
						$order_table.find('.order-total').html(response.order_data.total_html);

						$item.remove();
						$no_item.addClass('hide-if-js');
					}
				}
			})

			return false;
		},
		removeItem: function(e){
			e.preventDefault();
			var that = this,
				$item = $(e.target).closest('tr'),
				item_id = parseInt( $item.attr('data-item_id') );
			if( !item_id ){
				return;
			}
			$.ajax({
				url: LearnPress_Settings.ajax,
				data: {
					action: 'learnpress_remove_order_item',
					order_id: $('#post_ID').val(),
					item_id: item_id,
					remove_nonce: $item.attr('data-remove_nonce')
				},
				type: 'post',
				dataType: 'text',
				success: function(response){
					response = LearnPress.parseJSON(response);
					if(response.result == 'success'){
						var $order_table = that.$('.order-items'),
							$no_item = $order_table.find('.no-order-items'),
							$other_items = $item.siblings().filter(function(){return !$(this).is($no_item)});
						$order_table.find('.order-subtotal').html(response.order_data.subtotal_html);
						$order_table.find('.order-total').html(response.order_data.total_html);
						$item.remove();
						$other_items.length == 0 && $no_item.removeClass('hide-if-js');
					}
				}
			});
		},
		calculateTotal: function (e) {
			LearnPress.log(e)
		},
		fetchResults  : function (results) {
			var $list = this.$('#learn-press-courses-result'),
				$t = $list.find('li').filter(function () {
					return $(this).hasClass('lp-search-no-results') ? true : $(this).remove() && false;
				});
			_.each(results, function (i, k) {
				$('<li><a href="' + i.permalink + '" data-id="' + k + '">' + i.title + '</a></li>').insertBefore($t);
			});
		},
		searchItems   : function (e) {
			var that = this,
				$input = $(e.target),
				timer = $input.data('timer'),
				term = $input.val(),
				_search = function () {
					$.ajax({
						url     : LearnPress_Settings.ajax,
						data    : {
							action: 'learnpress_search_courses',
							nonce : $input.attr('data-nonce'),
							term  : term
						},
						type    : 'get',
						dataType: 'text',
						success : function (response) {
							response = LearnPress.parseJSON(response);
							that.fetchResults(response);
						}
					});
				};
			timer && clearTimeout(timer);
			if ((term + '').length >= 3) {
				timer = setTimeout(_search, 250);
				$input.data('timer', timer);
			}
		}
	});

	$(document).ready(function () {
		new LP_Order_View();
	});
})(jQuery);