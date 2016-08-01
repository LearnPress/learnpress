/**
 * Custom functions to improves popup that display content of course's item
 * Combine this functions to main file when release theme
 */
;(function ($) {
	"use strict";
	var $doc = $(document),
		$body = null,
		$popup = null,
		$popupOverlay = null,
		resizeTimer = null,
		navItems = null,
		thim_scroll = false;

	function _init() {
		navItems = LP.Views.Course.model.items;
		$().extend(navItems, {
			getNext: function () {
				var current = this.getCurrent(),
					next = false,
					_at = false;
				for (var i = 0; i < this.length; i++) {
					_at = this.at(i);
					if (_at && (_at.get('current') == true)) {
						for (var j = i + 1; j < this.length; j++) {
							_at = this.at(j);
							if (_at && _at.get('viewable')) {
								next = this.at(j);
								break;
							}
						}
					}
					if (next) break;
				}
				return next;
			},
			getPrev: function () {
				var current = this.getCurrent(),
					prev = false,
					_at = false;
				for (var n = this.length, i = n - 1; i >= 0; i--) {
					_at = this.at(i);
					if (_at && (_at.get('current') == true)) {
						for (var j = i - 1; j >= 0; j--) {
							_at = this.at(j);
							if (_at && _at.get('viewable')) {
								prev = this.at(j);
								break;
							}
						}
					}
					if (prev) break;
				}
				return prev;
			}
		});

		if (navItems.getCurrent()) {
			$('.course-content-item').css({
				visibility: 'visible'
			});
			showPopup();
			$('.mfp-content').prepend($('.popup-title'));
			$('.mfp-wrap').scroll(function () {
				$(this).find('.popup-title').css('top', 'inherit').attr('x', Math.random());
			});
			///$('.popup-title').css('top', 32)
		}

	}

	function showPopup($content) {
		$.magnificPopup.open({
			closeOnBgClick: false,
			preloader     : false,
			showCloseBtn  : false,
			items         : {
				src : $content || $('.popup-content'),
				type: 'inline'
			},
			mainClass     : 'mfp-with-fade',
			removalDelay  : 300,
			callbacks     : {
				open  : function () {
					thim_scroll = false;
					if ($('.thim-course-menu-landing').length > 0) {
						$('.thim-course-menu-landing').addClass('thim-hidden');
					}

					//Cancel event close when loading
					$.magnificPopup.instance.close = function () {
						if ($('.thim-loading-container').length > 0) {
							return;
						}
						thim_scroll = true;
						$('.thim-course-menu-landing.thim-hidden').removeClass('thim-hidden');
						$.magnificPopup.proto.close.call(this);
						$('.course-content').removeClass('rendered');
					};
					this.container.css('padding-top', 0).parent().css({
						top   : $('html').css('margin-top'),
						height: 'auto',
						bottom: 0
					})

				},
				resize: function () {
					var $courseContent = $('.course-content'),
						$title = $courseContent.find('.popup-title'),
						position = $('.mfp-content').position() || {top: 0, left: 0},
						htmlPadding = parseInt($('html').css('margin-top'));
					/*$title.css({
					 'left' : position.left,
					 'top'  : position.top + htmlPadding,
					 'width': $courseContent.innerWidth()
					 });*/
				}
			}
		});
	}

	$doc.ready(_init);

	return;

	function _ready() {
		$body = $(document.body);
		$popup = $('.course-content-item.popup-content');
		$popupOverlay = $('.popup-overlay');


		$(window).bind('resize.thim_resize_popup', function () {
			resizeTimer && clearTimeout(resizeTimer);
			resizeTimer = setTimeout(_resize, 150);
		});

		LP.Views.Course.setElement($body);

		var navItems = LP.Views.Course.model.items;
		$().extend(navItems, {
			getNext: function () {
				var current = this.getCurrent(),
					next = false,
					_at = false;
				for (var i = 0; i < this.length; i++) {
					_at = this.at(i);
					if (_at && (_at.get('current') == true)) {
						for (var j = i + 1; j < this.length; j++) {
							_at = this.at(j);
							if (_at && _at.get('viewable')) {
								next = this.at(j);
								break;
							}
						}
					}
					if (next) break;
				}
				return next;
			},
			getPrev: function () {
				var current = this.getCurrent(),
					prev = false,
					_at = false;
				for (var n = this.length, i = n - 1; i >= 0; i--) {
					_at = this.at(i);
					if (_at && (_at.get('current') == true)) {
						for (var j = i - 1; j >= 0; j--) {
							_at = this.at(j);
							if (_at && _at.get('viewable')) {
								prev = this.at(j);
								break;
							}
						}
					}
					if (prev) break;
				}
				return prev;
			}
		});

		$doc
		//.unbind('learn_press_item_loaded')
			.unbind('learn_press_item_current_changed')
			.on('learn_press_item_current_changed', function () {
				if (!$popup.is(':visible')) {
					$popupOverlay.fadeIn();
					$popup.show();
				}
				$popup.addClass('ajax-loading');

			}).on('learn_press_item_loaded', function (e, data) {
			var newHeading = $popup.find('#learn-press-content-item .popup-title'),
				oldHeading = $popup.find('.popup-title').not(newHeading),
				newNav = $popup.find('.yyyyy .course-item-nav'),
				oldNav = $popup.find('.course-item-nav').not(newNav);
			if (newHeading.length == 0) {
				newHeading = oldHeading;
			}
			if (data.item) {
				newHeading.find('.index').remove();
				newHeading.find('.title').html(data.item.get('title'));
				newHeading.prepend(data.item.get('el').find('.index').clone());
				console.log("xxx", data.item.get('title'));

			}
			if (oldHeading.length) {
				oldHeading.replaceWith(newHeading);
			} else {
				$popup.find('.xxxxx').prepend(newHeading);
			}
			var prev = navItems.getPrev(),
				next = navItems.getNext();
			if (next) {
				oldNav.find('.course-item-next').show().find('a').attr({
					'data-id': next.get('id'),
					'href'   : next.get('url')
				}).html(next.get('title'));
			} else {
				oldNav.find('.course-item-next').hide();
			}
			if (prev) {
				oldNav.find('.course-item-prev').show().find('a').attr({
					'data-id': prev.get('id'),
					'href'   : prev.get('url')
				}).html(prev.get('title'));
			} else {
				oldNav.find('.course-item-prev').hide();
			}
			$popup.removeClass('ajax-loading');
			$(window).trigger('resize.thim_resize_popup');
		}).on('learn_press_item_redirect_url', function (e, data) {
				var item = data.item,
					_callback = function (item) {
						var iframe = $('<iframe />', {id: 'thim-popup-quiz', src: item.get('url')});
						$('#learn-press-content-item').html(iframe)
						iframe.load(function () {
							$(this).css({
								width : '100%',
								height: $(this).contents().find('body').height()
							})
							$(window).trigger('resize.thim_resize_popup')
						});
						LP.setUrl(LP.Views.Course.model.get('url'));
					}
				if (item.get('type') == 'lp_quiz') {
					_callback(item);
					$doc.bind('learn_press_set_course_url.quiz_url', function (e, a) {
						$doc.unbind('learn_press_set_course_url.quiz_url');
						return false;
					})
					return false;
				}
				return data;
			}
		).on('click', '.mfp-close', function (e) {
			e.preventDefault();
			$('.popup-content,.popup-overlay').fadeOut();
			var cur = navItems.getCurrent();
			if (cur) {
				cur.set('current', false);
				LP.setUrl(LP.Views.Course.model.get('url'))
			}
		});

		$body.prepend($popup).prepend($popupOverlay);
		$popup.css({visibility: 'visible', display: 'none'});
		var cur = navItems.getCurrent();
		if (cur) {
			$doc.triggerHandler('learn_press_item_current_changed');
			$doc.triggerHandler('learn_press_item_loaded', {html: cur.get('content'), item: cur});
		}
	}

	function _resize() {
		var $el = $popup.find('.yyyyy'),
			$xx = $popup.find('.xxxxx'),
			popH = 0,
			winH = 0,
			dx = 0,
			$ifr = $popup.find('#thim-popup-quiz');
		winH = $(window).height();
		if ($ifr.length) {
			var ifrH = $ifr.contents().find('body').outerHeight(),
				contentH = winH - 240;
			if (ifrH > contentH) {
				//$ifr.height(contentH);
			} else {
				contentH = ifrH - 80;
			}
			popH = contentH + 240;// including header
			dx = (dx = (winH - popH) / 2) >= 10 ? dx : 10

		} else {
			popH = $el.find('#learn-press-content-item').outerHeight() + 240;// including header
			dx = (dx = (winH - popH) / 2) >= 10 ? dx : 10
		}
		$popup.stop().animate({
			top   : dx,
			bottom: dx
		}, 'fast', function () {
			//$xx.height($el.height()+180)
		});
		console.log('resize:' + dx)

	}

	$doc.ready(_ready);
})
(jQuery);