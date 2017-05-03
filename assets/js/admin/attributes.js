;(function ($) {
	var select2Options = {
		formatNoMatches: function () {
			$(document).off('keyup.addNewAttributeValueEvent');
			$(document).on('keyup.addNewAttributeValueEvent', '.select2-input', addNewAttributeValueEvent);
			return 'No match found, <code>Ctrl + Enter</code> to add new attribute';
		},
		formatMatches  : function () {
			$(document).off('keyup.addNewAttributeValueEvent');
		}
	}, postId = 0;

	function getPostId() {
		if (!postId) {
			postId = $('input[name="post_ID"]').val();
		}
		return postId;
	}

	function addAttributeToCourse(button) {
		$(button).addClass('disabled');
		$.post({
			url    : window.location.href.addQueryVar('add-attribute-to-course', getPostId()),
			data   : $().extend({}, $(button).data()),
			success: function (response) {
				var $html = $('.course-attributes'),
					$newHtml = $(response);
				$newHtml.appendTo($html);
				$newHtml.find('.course-attribute-values').select2(select2Options)

                saveAttributesEvent();
                attr_sortable();
			}
		})
	}

	function addNewAttributeValue(name, taxonomy, el) {
		var $li = $(el).closest('.learn-press-attribute');
		$.post({
			url     : window.location.href.addQueryVar('add-attribute-value', getPostId()),
			data    : {
				name    : name,
				taxonomy: taxonomy
			},
			dataType: 'text',
			success : function (response) {
				response = LP.parseJSON(response);
				if (response.result == 'success') {
					$li.find('select.course-attribute-values').append('<option value="' + response.slug + '" selected="selected">' + response.name + '</option>').change();
				} else {
					if (response.message) {
						alert(response.message);
					}
				}
			}
		})
	}

	function addNewAttributeValueEvent(e) {
		if (e.ctrlKey && e.keyCode == 13) {
			var $sel = $('.select2-focused');
			if ($sel.length == 0) {
				return;
			}
			addNewAttributeValue($sel.val(), $sel.closest('.learn-press-attribute').data('taxonomy'), this)
		}
	}

	function saveAttributesEvent(e) {
		var attr_order = [];

        $.each($('.course-attributes').find('li.learn-press-attribute'), function(){
        	attr_order.push($(this).data('taxonomy'));
		})

		// console.log(attr_order)


		$.post({
			url    : window.location.href.addQueryVar('save-attributes', getPostId()),
			data   : {
				data_order: attr_order,
				data_attr: $('.course-attributes').find('input, select, textarea').serialize(),
			},
			success: function (response) {
				// console.log(response)
			}
		});
	}

	function removeAttributesEvent (e, this_btn) {
		e.preventDefault();

		var $button = $(e.target);

        LP.MessageBox.quickConfirm($button, {
            onOk: function (a) {
                var btn_add_attr = $('.course-attribute-taxonomy li[data-taxonomy="' + this_btn.closest('li.learn-press-attribute').data('taxonomy') + '"]');

                // Enable btn
                btn_add_attr.removeClass('disabled');
                // Remove attribute in client
                this_btn.closest('li.learn-press-attribute').remove();

                saveAttributesEvent();

                $.post({
                    url    : window.location.href.addQueryVar('remove-attributes', getPostId()),
                    data   : $().extend({}, $(btn_add_attr).data()),
                    success: function (response) {

                    }
                })
            }
        });

	}

	function attr_sortable() {
		$(".learn-press-attribute").each(function(i) {
		  var item = $(this);
		  var item_clone = item.clone();
		  item.data("clone", item_clone);
		  var position = item.position();
		  item_clone
		  .css({
		    left: position.left,
		    top: position.top,
		    visibility: "hidden"
		  })
		    .attr("data-pos", i+1);
		  
		  $("#cloned-slides").append(item_clone);
		});

		$('.course-attributes').sortable({
            axis: "y",
            cursor: "move",
            handle: "a.move.dashicons.dashicons-menu",
            connectWith: '.course-attributes',
            item: '.learn-press-attribute',
			revert: true,
			scroll: false,
			placeholder: "sortable-placeholder",

			start: function(e, ui) {
			    ui.helper.addClass("exclude-me");
			    $(".course-attributes .learn-press-attribute:not(.exclude-me)")
			      .css("visibility", "hidden");
			    ui.helper.data("clone").hide();
			    $(".course-attributes .learn-press-attribute").css("visibility", "visible");
			  },

			  stop: function(e, ui) {
			    $(".course-attributes .learn-press-attribute").each(function() {
			      var item = $(this);
			      var clone = item.data("clone");
			      var position = item.position();

			      clone.css("left", position.left);
			      clone.css("top", position.top);
			      clone.show();

			      item.removeClass("exclude-me");
			    });
			    
			    $(".course-attributes .learn-press-attribute").each(function() {
			      var item = $(this);
			      var clone = item.data("clone");
			      
			      clone.attr("data-pos", item.index());
			    });

			    $(".course-attributes .learn-press-attribute").css("visibility", "visible");
			    $(".cloned-slides .learn-press-attribute").css("visibility", "hidden");
			  },

			  change: function(e, ui) {
			    $(".course-attributes .learn-press-attribute:not(.exclude-me)").each(function() {
			      var item = $(this);
			      var clone = item.data("clone");
			      clone.stop(true, false);
			      var position = item.position();
			      clone.animate({
			        left: position.left,
			        top: position.top
			      }, 200);
			    });
			  }
		});
	}

	$(document).ready(function () {
		attr_sortable();

		$(document)
			.on('click', '.add-attribute:not(.disabled)', function () {
				addAttributeToCourse(this);
			})
			.on('click', '#save-attributes', saveAttributesEvent)
			.on('click', '.learn-press-remove-attribute', function(e){
				removeAttributesEvent(e, $(this))
			})
			.on('keyup.addNewAttributeValueEvent', '.select2-input', addNewAttributeValueEvent);

		var $courseAttributes = $('.course-attribute-values');

		if ($courseAttributes.length) {
			$courseAttributes.select2(select2Options);
		}

        $('.learn-press-toggle-box-tools').hide();
		$('li.learn-press-attribute').hover(function () {
			$(this).find('.learn-press-toggle-box-tools').show();
        }, function () {
            $(this).find('.learn-press-toggle-box-tools').hide();
        })


	});
})(jQuery);
