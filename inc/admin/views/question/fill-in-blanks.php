<?php
/**
 * Admin question editor: fib question answer template.
 *
 * @since 3.0.0
 */

$content_id = uniqid( 'fib-content-' );
?>

<script type="text/x-template" id="tmpl-lp-fib-question-answer">
	<div id="lp-admin-question-editor" class="lp-admin-fib-question-editor">
		<?php learn_press_admin_view( 'question/fib-answer-editor' ); ?>
	</div>
</script>

<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {
		const $Vue = window.$Vue || Vue;
		const $store = window.LP_Question_Store;

		Vue.component( 'lp-fib-question-answer', {
			template: '#tmpl-lp-fib-question-answer',
			props: [ 'type', 'answers', 'rawBlanks' ],

			data() {
				return {
					valid: true,
					canInsertNewBlank: false,
					blanks: [],
				};
			},
			mounted() {
				const that = this,
					content = this.getContent();
				this.blanks = this.answers[ 0 ].blanks;
				this.$editor = $( this.$el ).find( '.content-editable' );
				this.$editor.html( content );
				this.parseBlanks( content );

				this.$editor[ 0 ].addEventListener( 'DOMCharacterDataModified', function( e ) {
					const $target = $( e.target ).parent(),
						id = $target.data( 'id' );

					for ( const i in that.blanks ) {
						if ( that.blanks[ i ].id == id ) {
							that.blanks[ i ].fill = $target.html().trim();
							that.$activeBlank = $target;
							break;
						}
					}
				}, false );

				this.interval = setInterval( function( a ) {
					a.parseBlanks( a.getContent() );
				}, 1000, this );
			},
			computed: {
				answer() {
					return {
						order: 1,
						is_true: '',
						question_answer_id: this.answers[ 0 ].question_answer_id,
						title: this.answers[ 0 ].title,
						value: '',
						blanks: this.answers[ 0 ].blanks,
					};
				},
			},
			methods: {
				updateAnswer( e ) {
					let answer = JSON.parse( JSON.stringify( this.answer ) );
					answer.title = this.getShortcode();
					answer.blanks = this.getBlanksForDB();

					$store.dispatch( 'updateAnswerTitle', answer );
				},
				updateAnswerBlank( e, blank ) {
					blank['comparison'] = e.target.value || '';
					this.updateAnswer();
				},
				updateAnswerMatchCase( e, blank ) {
					blank['match_case'] = e.target.checked ? true : false;
					this.updateAnswer();
				},
				getContent() {
					let content = this.answers[ 0 ].title,
						shortcodes = content.match( /\[fib.*?\]/g ),
						uids = {};

					if ( shortcodes ) {
						for ( let i = 0; i < shortcodes.length; i++ ) {
							var uid,
								fill,
								replaceText,
								props = shortcodes[ i ].match( /([a-z_]+)="(.*?)"/g ),
								data = [];

							for ( const j in props ) {
								const prop = props[ j ].match( /([a-z_]+)="(.*?)"/ );

								if ( ! prop ) {
									continue;
								}

								switch ( prop[ 1 ] ) {
								case 'uid':
								case 'id':
									uid = prop[ 2 ];
									break;
								case 'fill':
									fill = prop[ 2 ];
									break;
								default:
									data.push( 'data-' + prop[ 1 ] + '="' + prop[ 2 ] + '"' );
								}
							}

							uid = uid ? uid : LP.uniqueId();

							if ( uids[ uid ] ) {
								uid = LP.uniqueId();
							}

							replaceText = FIB.outerHTML( this.createBlank( fill, uid ).attr( 'data-index', i + 1 ) );// '<span class="fib-blank" id="fib-blank-' + uid + '" data-id="' + uid + '" data-index="' + (i + 1) + '">' + fill + '</span>';
							uids[ uid ] = true;

							content = content.replace( shortcodes[ i ], replaceText );
						}
					}
					return content;
				},
				activeBlank( e ) {
					this.$activeBlank = $( e.target ).closest( '.fib-blank' );
				},

				findBlank( id ) {
					for ( const i in this.blanks ) {
						if ( this.blanks[ i ].id == id ) {
							return this.blanks[ i ];
						}
					}

					return false;
				},
				parseBlanks( content ) {
					let $container = this.$editor,
						$inputs = $container.find( '.fib-blank' ),
						$input,
						data,
						blanks = [],
						uids = [],
						i = 0,
						n = 0;

					for ( i = 0; i < $inputs.length; i++ ) {
						$input = $inputs.eq( i ).attr( 'data-index', i + 1 );
						data = $input.data();

						if ( -1 !== $.inArray( data.id, uids ) ) {
							data.id = LP.uniqueId();
						}

						const oldBlank = this.findBlank( data.id ) || {};

						blanks.push( {
							fill: $input.html().trim(),
							id: data.id,
							comparison: data.comparison || oldBlank.comparison || '',
							match_case: data.match_case || oldBlank.match_case || 0,
							index: i + 1,
							open: !! oldBlank.open,
						} );
						uids.push( data.id );
					}
					this.blanks = blanks;
				},
				updateBlanks( content ) {
					this.parseBlanks( content !== undefined ? content : this.$editor.html() );
					return this.getShortcode();
				},
				getShortcode() {
					const that = this,
						$container = this.$editor.clone(),
						$blanks = $container.find( '.fib-blank' );

					$blanks.each( function() {
						let $blank = $( this ),
							id = $blank.attr( 'id' ),
							uid = id.replace( 'fib-blank-', '' ),
							blank = that.getBlankById( uid ),
							code = 'fib';

						if ( blank ) {
							if ( ! blank.id ) {
								return;
							}

							for ( const i in blank ) {
								if ( [ 'index' ].indexOf( i ) !== -1 ) {
									continue;
								}

								if ( ! blank[ i ] ) {
									continue;
								}

								code += ' ' + i + '="' + blank[ i ] + '"';
							}

							$blank.replaceWith( '[' + code + ']' );
						} else {
							console.log( 'Not found: ' + uid );
							$blank.replaceWith( '' );
						}
					} );
					return $container.html();
				},
				getBlankById( id ) {
					let blank = false;

					$.each( this.blanks, function() {
						if ( id == this.id ) {
							blank = this;
							return true;
						}
					} );

					return blank;
				},
				updateBlank( e ) {
					const $el = $( e.target ),
						id = $el.attr( 'id' ),
						$blank = this.$editor.find( '#' + id );
					$blank.html( e.target.value );
					this.updateAnswer();
				},
				removeBlank( e, id ) {
					e.preventDefault();
					this.removeBlankById( id );
					this.updateAll();
				},
				removeBlankById( id ) {
					const $blank = this.$editor.find( '.fib-blank#fib-blank-' + id );
					$blank.replaceWith( $blank.html() );
				},
				updateAll() {
					this.answer.title = this.updateBlanks();
					this.updateAnswer();
				},
				insertBlank() {
					if ( ! this.canInsertNewBlank ) {
						return;
					}

					const $content = $( this.$el ).find( '.content-editable' ),
						content = $content.html(),
						selectedText = FIB.getSelectedText(),
						selectionRange = FIB.getSelectionRange(),
						$blank = this.createBlank( selectedText ),
						nodeValue = selectionRange.anchorNode.nodeValue,
						x = selectionRange.anchorOffset,
						y = selectionRange.focusOffset;
					startRange = x < y ? x : y;
					endRange = startRange == x ? y : x;

					selectionRange.anchorNode.nodeValue = nodeValue.substr( 0, startRange );
					$( $blank ).insertAfter( selectionRange.anchorNode );
					$( FIB.createTextNode( nodeValue.substr( endRange ) ) ).insertAfter( $blank );

					const $blanks = $content.find( '.fib-blank' );
					$blanks.each( function( i, el ) {
						const $blank = $( this );
						if ( $blank.parent().is( 'b' ) ) {
							const $p = $blank.parent();
							$p.replaceWith( $p.html() );
						}
						$blank.attr( 'data-index', i + 1 );
					} );

					this.parseBlanks( $content.html() );
					this.updateAnswer();
				},
				clearContent() {
					this.$editor.html( '' );
					this.updateAnswer();
					this.canInsertNewBlank = false;
				},
				createBlank( content, id ) {
					if ( ! id ) {
						id = LP.uniqueId();
					}
					return $( '<b class="fib-blank" id="fib-blank-' + id + '" data-id="' + id + '"> ' + content + '</b>' );
				},
				clearBlanks() {
					if ( ! confirm( $store.getters.i18n.confirm_remove_blanks ) ) {
						return;
					}

					for ( const i in this.blanks ) {
						this.removeBlankById( this.blanks[ i ].id );
					}
					this.updateAll();
				},
				canInsertBlank() {
					const $content = $( this.$el ).find( '.content-editable' ),
						content = $content.html(),
						selectedText = FIB.getSelectedText(),
						selectionRange = FIB.getSelectionRange();
					this.canInsertNewBlank = selectedText.length && ! FIB.isContainHtml( selectionRange.anchorNode );
				},
				getBlanksForDB() {
					const blanks = {};
					for ( let i = 0, n = this.blanks.length; i < n; i++ ) {
						const id = this.blanks[ i ].id.replace( 'fib-blank-', '' );
						blanks[ id ] = JSON.parse( JSON.stringify( this.blanks[ i ] ) );
						blanks[ id ].id = id;
					}
					return blanks;
				},
				toggleOptions( e, id ) {
					e.preventDefault();
					const that = this;
					$( e.target ).closest( '.fib-blank' ).find( '.blank-options ul' ).slideToggle( function() {
						that.setBlankProp( id, 'open', ! $( this ).is( ':hidden' ) );
					} );
				},
				setBlankProp( id, prop, value ) {
					for ( const i in this.blanks ) {
						if ( this.blanks[ i ].id == id ) {
							if ( $.isPlainObject( prop ) ) {
								for ( const p in prop ) {
									this.$set( this.blanks[ i ], p, prop[ p ] );
								}
							} else {
								this.$set( this.blanks[ i ], prop, value );
							}

							break;
						}
					}
					this.updateAnswer();
				},
			},
		} );
	} );
</script>
