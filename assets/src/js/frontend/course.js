/**
 * LearnPress frontend course app.
 *
 * @version 3.2.0
 * @author ThimPress
 * @package LearnPress/JS/Course
 */
( function( $ ) {
	'use strict';
	function LP_Storage( key ) {
		const storage = window.localStorage;
		this.key = key;
		this.get = function( id ) {
			const val = storage.getItem( this.key ) || '',
				sections = val.split( ',' );
			if ( id ) {
				id = id + '';
				const pos = sections.indexOf( id );
				if ( pos >= 0 ) {
					return sections[ pos ];
				}
			}
			return sections;
		};
		this.set = function( sections ) {
			if ( typeof sections !== 'string' ) {
				sections = sections.join( ',' );
			}
			storage.setItem( this.key, sections );
			return sections.split( ',' );
		};
		this.hasSection = function( id ) {
			id = id + '';
			const sections = this.get(),
				at = sections.indexOf( id );

			return at >= 0 ? at : false;
		};
		this.add = function( id ) {
			id = id + '';
			const sections = this.get();
			if ( this.hasSection( id ) ) {
				return;
			}
			sections.push( id );
			this.set( sections );
			return sections;
		};
		this.remove = function( id ) {
			id = id + '';
			const at = this.hasSection( id );
			if ( at !== false ) {
				const sections = this.get();
				sections.splice( at, 1 );
				this.set( sections );
				return sections;
			}
			return false;
		};
	}

	/**
	 * LP_Course
	 *
	 * @param settings
	 * @class
	 */
	function LP_Course( settings ) {
		const elLPOverlay = $( '.lp-overlay' );
		const elFormCompleteLesson = $(	'form[name=learn-press-form-complete-lesson]' );
		let sectionStorage = new LP_Storage( 'sections' ),
			$body = $( 'body' ),
			$content = $( '.content-item-scrollable' ),
			$curriculum = $( '#learn-press-course-curriculum' ),
			$contentItem = $( '#learn-press-content-item' ),
			$curriculumScrollable = $curriculum.find( '.curriculum-scrollable' ),
			$header = $( '#course-item-content-header' ),
			$footer = $( '#course-item-content-footer' ),
			$courseItems = $curriculum.find( '.course-item' ),
			isShowingHeader = true,
			fullScreen,
			contentTop = 0,
			headerTimer,
			inPopup = false;

		/**
		 * Toggle answer option check/uncheck
		 *
		 * @param event
		 */
		function toggleAnswerOptions( event ) {
			let $el = $( event.target ),
				$chk;
			if ( $el.is( 'input.option-check' ) ) {
				return;
			}

			$chk = $el.closest( '.answer-option' ).find( 'input.option-check' );

			if ( ! $chk.length ) {
				return;
			}

			if ( $chk.is( ':disabled' ) ) {
				return;
			}
			if ( $chk.is( ':checkbox' ) ) {
				$chk[ 0 ].checked = ! $chk[ 0 ].checked;
			} else {
				$chk[ 0 ].checked = true;
			}
		}

		/**
		 * Show/Hide section content
		 */
		function toggleSection() {
			const id = $( this ).closest( '.section' ).data( 'section-id' );
			$( this ).siblings( '.section-content' ).slideToggle( function() {
				if ( $( this ).is( ':visible' ) ) {
					sectionStorage.remove( id );
				} else {
					sectionStorage.add( id );
				}
			} );
		}

		/**
		 * Init sections
		 */
		function initSections() {
			let $activeSection = $( '.course-item.current' ).closest( '.section' ),
				sections = $( '.curriculum-sections' ).find( '.section' ),
				sectionId = $activeSection.data( 'section-id' ),
				hiddenSections = [];

			if ( $activeSection ) {
				hiddenSections = sectionStorage.remove( sectionId );
			} else {
				hiddenSections = sectionStorage.get();
			}

			for ( let i = 0; i < hiddenSections.length; i++ ) {
				sections.filter( '[data-section-id="' + hiddenSections[ i ] + '"]' ).
					find( '.section-content' ).
					hide();
			}
		}

		/**
		 * Prepare form before submitting
		 *
		 * @param form
		 */
		function prepareForm( form ) {
			const $answerOptions = $( '.answer-options' ),
				$form = $( form ),
				data = $answerOptions.serializeJSON(),
				$hidden = $( '<input type="hidden" name="question-data" />' ).
					val( JSON.stringify( data ) );

			if ( ( $form.attr( 'method' ) + '' ).toLowerCase() !== 'post' ) {
				return;
			}

			$form.find( 'input[name="question-data"]' ).remove();
			return $form.append( $hidden ).
				append( $( '<div />' ).append( $answerOptions.clone() ).hide() );
		}

		/**
		 * Tab course event
		 *
		 * @param e
		 * @param tab
		 */
		function onTabCourseClick( e, tab ) {
			if ( $( document.body ).hasClass( 'course-item-popup' ) ) {
				return;
			}

			const $tab = $( tab ),
				$parent = $tab.closest( '.course-nav' );

			if ( $parent.siblings().length === 0 ) {
				return;
			}
			LP.setUrl( $tab.attr( 'href' ) );
		}

		/**
		 * Event on press any key into search
		 *
		 * @param e
		 * @return {boolean}
		 */
		function onSearchInputKeypress( e ) {
			if ( e.type === 'keypress' && e.keyCode === 13 ) {
				return false;
			}

			const s = this.value,
				r = new RegExp( s, 'ig' );
			$courseItems.map( function() {
				const $item = $( this ),
					itemName = $item.find( '.item-name' ).text();
				if ( itemName.match( r ) || ! s.length ) {
					$item.show();
				} else {
					$item.hide();
				}
			} );

			$( '.section' ).show().each( function() {
				if ( s.length ) {
					if ( ! $( this ).
						find( '.section-content' ).
						children( ':visible' ).length ) {
						$( this ).hide();
					} else {
						$( this ).show();
					}
				} else {
					$( this ).show();
				}
			} );
			$( this ).
				closest( '.course-item-search' ).
				toggleClass( 'has-keyword', !! this.value.length );
		}

		function onClearSearchInputClick( e ) {
			const $form = $( this ).closest( '.course-item-search' );
			$form.find( 'input' ).val( '' ).trigger( 'keyup' );
		}

		function onClickQM() {
			$( '#qm' ).css( { 'z-index': 999999999, position: 'relative' } );
			$( 'html, body' ).css( 'overflow', 'auto' );
		}

		function getCurriculumWidth() {
			return $curriculum.outerWidth();
		}

		function maybeShowCurriculum( e ) {
			return;
			const offset = $( this ).offset(),
				offsetX = e.pageX - offset.left,
				curriculumWidth = getCurriculumWidth();

			if ( ! fullScreen || ( offsetX > 50 ) ) {
				return;
			}

			timeoutToClose();

			if ( ! isShowingHeader ) {
				$curriculum.stop().animate( {
					left: 0,
				} );

				$contentItem.stop().animate( {
					left: curriculumWidth,
				} );

				$footer.stop().animate( {
					left: curriculumWidth,
				}, function() {
					$( document, window ).trigger( 'learn-press/toggle-content-item' );
				} );

				$header.find( '.course-item-search' ).show();
				toggleEventShowCurriculum( true );
				isShowingHeader = true;
			}
		}

		function toggleEventShowCurriculum( b ) {
			$( document )[ b ? 'off' : 'on' ]( 'mousemove.maybe-show-curriculum',
				'body', maybeShowCurriculum );
		}

		function timeoutToClose() {
			headerTimer && clearTimeout( headerTimer );
			headerTimer = setTimeout( function() {
				const curriculumWidth = getCurriculumWidth();

				if ( ! fullScreen ) {
					return;
				}

				$curriculum.stop().animate( {
					left: -curriculumWidth,
				} );

				$contentItem.stop().animate( {
					left: 0,
				} );

				$footer.stop().animate( {
					left: 0,
				}, function() {
					$( document, window ).trigger( 'learn-press/toggle-content-item' );
				} );

				$header.find( '.course-item-search' ).hide();

				isShowingHeader = false;
				toggleEventShowCurriculum();
			}, 3000 );
		}

		function toggleContentItem( e ) {
			e.preventDefault();
			const curriculumWidth = getCurriculumWidth();
			let sidebarPosition = 'left';

			if ( 'rtl' === $( 'html' ).attr( 'dir' ) ) {
				sidebarPosition = 'right';
			}

			fullScreen = $body.toggleClass( 'full-screen-content-item' ).
				hasClass( 'full-screen-content-item' );

			if ( 'right' === sidebarPosition ) {
				curriculumRight();
			} else {
				curriculumLeft();
			}

			function curriculumRight() {
				$curriculum.stop().animate( {
					right: fullScreen ? -curriculumWidth : 0,
				} );

				$contentItem.stop().animate( {
					right: fullScreen ? 0 : curriculumWidth,
				} );

				$footer.stop().animate( {
					right: fullScreen ? 0 : curriculumWidth,
				}, function() {
					$( document, window ).trigger( 'learn-press/toggle-content-item' );
				} );
			}

			function curriculumLeft() {
				$curriculum.stop().animate( {
					left: fullScreen ? -curriculumWidth : 0,
				} );

				$contentItem.stop().animate( {
					left: fullScreen ? 0 : curriculumWidth,
				} );

				$footer.stop().animate( {
					left: fullScreen ? 0 : curriculumWidth,
				}, function() {
					$( document, window ).trigger( 'learn-press/toggle-content-item' );
				} );
			}

			isShowingHeader = ! fullScreen;
			window.localStorage && window.localStorage.setItem( 'lp-full-screen',
				fullScreen ? 'yes' : 'no' );

			fullScreen && toggleEventShowCurriculum();
			$header.find( '.course-title' ).
				stop().
				animate( { marginLeft: fullScreen ? -curriculumWidth : 0 } );
			$header.find( '.course-item-search' ).
				stop().
				animate( { opacity: fullScreen ? 0 : 1 } ).
				css( { visibility: fullScreen ? 'hidden' : 'visible' } );
		}

		function initEvents() {
			// Live events
			$( document ).
				on( 'learn-press/nav-tabs/clicked', onTabCourseClick ).
				on( 'keyup keypress', '.course-item-search input',
					onSearchInputKeypress ).
				on( 'click', '.course-item-search button', onClearSearchInputClick ).
				on( 'click', '#wp-admin-bar-query-monitor', onClickQM ).
				on( 'click', '.answer-options .answer-option', toggleAnswerOptions ).
				on( 'click', '.section-header', toggleSection ).
				on( 'submit', 'form.lp-form', function() {
					prepareForm( this );
				} ).
				on( 'click', '.toggle-content-item', toggleContentItem );

			$curriculum.hover( function() {
				headerTimer && clearTimeout( headerTimer );
			}, function() {
				if ( fullScreen ) {
					timeoutToClose();
				}
			} );
		}

		function fitVideo() {
			const $wrapContent = $( '.content-item-summary.content-item-video' );

			if ( ! $wrapContent.length ) {
				return;
			}

			let $entryVideo = $wrapContent.find( '.entry-video' ),
				$frame = $entryVideo.find( 'iframe' ),
				width = $frame.attr( 'width' ),
				height = $frame.attr( 'height' ),
				ratio = 1,
				contentHeight,
				timer;

			function resizeVideo() {
				const frameWidth = $frame.width();
				contentHeight = frameWidth * ratio;
				$frame.css( {
					height: contentHeight,
					marginLeft: ( $entryVideo.width() - frameWidth ) / 2,
				} );

				$wrapContent.css( {
					paddingTop: contentHeight,
				} );
			}

			if ( ! $entryVideo.length ) {
				return false;
			}

			if ( width && height ) {
				if ( width.indexOf( '%' ) === -1 && height.indexOf( '%' ) === -1 ) {
					ratio = height / width;
				}
			}

			$( window ).
				on( 'resize.fit-content-video learn-press/toggle-content-item',
					function() {
						timer && clearTimeout( timer );
						timer = setTimeout( resizeVideo, 250 );
					} ).
				trigger( 'resize.fit-content-video' );

			$( '.content-item-scrollable' ).scroll( function() {
				$( this ).find( '.entry-video' ).css( 'padding-top', this.scrollTop );
			} );
		}

		/**
		 * Init
		 */
		function init() {
			inPopup = $body.hasClass( 'course-item-popup' );
			initSections();
			initEvents();

			if ( ! inPopup ) {
				return;
			}

			// $contentItem.appendTo( $body );
			// $curriculum.appendTo( $body );

			if ( $( '#wpadminbar' ).length ) {
				$body.addClass( 'wpadminbar' );
				contentTop = 32;
			}

			fitVideo();

			fullScreen = window.localStorage && 'yes' ===
					window.localStorage.getItem( 'lp-full-screen' );
			if ( $( window ).width() <= 768 ) {
				fullScreen = true;
			}
			if ( fullScreen ) {
				const curriculumWidth = getCurriculumWidth();
				$body.addClass( 'full-screen-content-item' );
				$contentItem.css( 'left', 0 );
				$curriculum.css( 'left', -curriculumWidth );
				$footer.css( 'left', 0 );
				isShowingHeader = ! fullScreen;
				$header.find( '.course-title' ).
					css( { marginLeft: fullScreen ? -curriculumWidth : 0 } );
				$header.find( '.course-item-search' ).
					css( { opacity: fullScreen ? 0 : 1 } );
				toggleEventShowCurriculum();
			}

			setTimeout( function() {
				const $cs = $body.find( '.curriculum-sections' ).parent();
				$cs.scrollTo( $cs.find( '.course-item.current' ), 100 );

				if ( window.location.hash ) {
					$( '.content-item-scrollable:last' ).
						scrollTo( $( window.location.hash ) );
				}
			}, 300 );

			$body.css( 'opacity', 1 );
		}

		// new LP.Alerts();

		/**
		 * Event complete items of course
		 */
		this.completeItemsCourse = function() {
			const lp_course = this;
			$( document ).on( 'click', '.lp-btn-complete-item', function( e ) {
				e.preventDefault();
				const elFormSubmit = $( this ).closest( 'form' );

				if ( 'yes' === lpGlobalSettings.show_popup_confirm_finish ) {
					lp_course.lpModalOverlay.setElCalledModal( elFormSubmit );
					lp_course.lpModalOverlay.callBackYes = function() {
						elFormSubmit.submit();
					};
					elLPOverlay.show();
				} else {
					elFormSubmit.submit();
				}
			} );
		};

		this.lpModalOverlay = {
			elMessage: null,
			elTitle: null,
			callBackYes: null,
			elCalledModal: null,
			init() {
				const lpModalOverlay = this;
				this.elMessage = elLPOverlay.find( '.message' );
				this.elTitle = elLPOverlay.find( '.modal-title' );

				$( document ).on( 'click', '.close, .btn-no', function() {
					elLPOverlay.hide();
				} );

				$( document ).on( 'click', '.btn-yes', function() {
					lpModalOverlay.callBackYes();
				} );
			},
			setElCalledModal( elCalledModal ) {
				this.elCalledModal = elCalledModal;

				this.setContentModal();
			},
			setContentModal() {
				this.elTitle.text( this.elCalledModal.data( 'title' ) );
				this.elMessage.text( this.elCalledModal.data( 'confirm' ) );
			},
		};

		this.finishCourse = function() {
			const lp_course = this;
			const elFormFinishCourse = $( '.form-button-finish-course' );

			if ( ! elFormFinishCourse ) {
				return;
			}

			const elBtnFinishCourse = elFormFinishCourse.find( '.lp-btn-finish-course' );

			elBtnFinishCourse.on( 'click', function( e ) {
				e.preventDefault();

				if ( 'yes' === lpGlobalSettings.show_popup_confirm_finish ) {
					lp_course.lpModalOverlay.setElCalledModal( elFormFinishCourse );
					lp_course.lpModalOverlay.callBackYes = function() {
						elFormFinishCourse.submit();
					};
					elLPOverlay.show();
				} else {
					elFormFinishCourse.submit();
				}
			} );
		};

		this.scrollToItemViewing = function() {
			const elItemViewing = $( '.viewing-course-item' );

			if ( elItemViewing.length ) {
				const heightCourseItemContentHeader = $( '#course-item-content-header' ).outerHeight();
				const heightSectionTitle = $( '.section-title' ).outerHeight();
				const heightSectionHeader = $( '.section-header' ).outerHeight();
				const regex = new RegExp( '^viewing-course-item-([0-9].*)' );
				const classList = elItemViewing.attr( 'class' );
				const classArr = classList.split( /\s+/ );
				let idItem = 0;

				$.each( classArr, function( i, className ) {
					const compare = regex.exec( className );

					if ( compare ) {
						idItem = compare[ 1 ];
						return false;
					}
				} );

				if ( 0 === idItem ) {
					return;
				}

				const elItemCurrent = $( '.course-item-' + idItem );
				const offSetTop = elItemCurrent.offset().top;
				let numberOffSetTop = offSetTop - heightCourseItemContentHeader;

				if ( undefined === heightSectionTitle ) {
					numberOffSetTop = numberOffSetTop - heightSectionHeader + 20;
				} else {
					numberOffSetTop = numberOffSetTop - heightSectionTitle;
				}

				$( '#learn-press-course-curriculum' ).animate( {
					scrollTop: numberOffSetTop + 7,
				}, 800 );
			}
		};

		init();
	}

	$( function() {
		const lp_course = new LP_Course( {} );

		lp_course.lpModalOverlay.init();
		// lp_course.completeLesson();
		lp_course.completeItemsCourse();
		lp_course.finishCourse();
		lp_course.scrollToItemViewing();

		// $( '#learn-press-course-curriculum' ).animate( {
		//   scrollTop: 300
		// }, 800);

		$( this ).on( 'submit', 'form[name="course-external-link"]', function() {
			const redirect = $( this ).attr( 'action' );
			if ( redirect ) {
				window.location.href = redirect;
				return false;
			}
		} );

		// Reload course page when course duration expired
		const course_item_html = $( '.course-item-is-blocked' ),
			course_item_value = course_item_html.val();
		if ( course_item_html.length && course_item_value < 2147483647 ) {
			setTimeout( function() {
				window.location.reload( true );
			}, course_item_value );
		}
	} );
}( jQuery ) );
