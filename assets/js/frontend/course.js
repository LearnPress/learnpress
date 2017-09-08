;(function ($, _, Vue) {

    'use strict';

    $(document).ready(function () {
        /*Vue.component('learn-press-modal-search-items', {
         template: '#learn-press-modal-search-items',
         data: function () {
         return {
         paged: 1,
         term: '',
         hasItems: false,
         selected: []
         }
         },
         watch: {
         show: function (value) {
         if (value) {
         $(this.$refs.search).focus();
         }
         }
         },
         props: ['postType', 'context', 'contextId', 'show', 'callbacks'],
         created: function () {
         },
         methods: {
         doSearch: function (e) {
         this.term = e.target.value;
         this.paged = 1;
         this.search();
         },
         search: _.debounce(function (term) {
         var that = this;
         Vue.http.post(
         window.location.href, {
         type: this.postType,
         context: this.context,
         context_id: this.contextId,
         term: term || this.term,
         paged: this.paged,
         'lp-ajax': 'modal-search-items'
         }, {
         emulateJSON: true,
         params: {}
         }
         ).then(function (response) {
         var result = LP.parseJSON(response.body);
         that.hasItems = !!_.size(result.items);

         $(that.$el).find('.search-results').html(result.html).find('input[type="checkbox"]').each(function () {
         var id = parseInt($(this).val());
         if (_.indexOf(that.selected, id) >= 0) {
         this.checked = true;
         }
         });
         _.debounce(function () {
         $(that.$el).find('.search-nav').html(result.nav).find('a, span').addClass('button').filter('span').addClass('disabled');
         }, 10)();
         });
         }, 500),
         loadPage: function (e) {
         e.preventDefault();
         var $button = $(e.target);
         if ($button.is('span')) {
         return;
         }
         if ($button.hasClass('next')) {
         this.paged++;
         } else if ($button.hasClass('prev')) {
         this.paged--;
         } else {
         var paged = $button.html();
         this.paged = parseInt(paged);
         }
         this.search();
         },
         selectItem: function (e) {
         var $select = $(e.target).closest('li'),
         $chk = $select.find('input[type="checkbox"]'),
         id = parseInt($chk.val()),
         pos = _.indexOf(this.selected, id);

         if ($chk.is(':checked')) {
         if (pos === -1) {
         this.selected.push(id);
         }
         } else {
         if (pos >= 0) {
         this.selected.splice(pos, 1);
         }
         }
         },
         addItems:function(){
         var close = true;
         if(this.callbacks && this.callbacks.addItems){
         this.callbacks.addItems.call(this);
         }
         $(document).triggerHandler('learn-press/add-order-items', this.selected);
         },
         close: function () {
         this.$emit('close');
         }
         }
         });
         */
        $(document).ready(function () {
            var $content = $('.content-item-scrollable');
            $content.addClass('scrollbar-light')
                .scrollbar({
                    scrollx: false
                });

            $content.parent().css({
                position: 'absolute',
                top: 0,
                bottom: 60,
                width: '100%'
            }).css('opacity', 1).end().css('opacity', 1);

            var $curriculum = $('.course-item-popup').find('.curriculum-scrollable');
            $curriculum.addClass('scrollbar-light')
                .scrollbar({
                    scrollx: false
                });

            $curriculum.parent().css({
                position: 'absolute',
                top: 0,
                bottom: 0,
                width: '100%'
            }).css('opacity', 1).end().css('opacity', 1);

            // $('.course-item-popup').find('#learn-press-course-curriculum').addClass('scrollbar-light').scrollbar({scrollx: false});

            $('body').css('opacity', 1);
        })
        window.LP.$courseXYZ = new Vue({
            el: '#learn-press-course',
            data: {
                show: false,
                term: '',
                postType: '',
                callbacks: {}
            },
            methods: {
                nextQuestion: function (event) {
                    event.preventDefault();
                    var data = $('.answer-options').serializeJSON(),
                        $form = $(event.target.form),
                        $hidden = $('<input type="hidden" name="question-data" />').val(JSON.stringify(data));
                    $form.append($hidden).submit();

                },
                completeItem: function (event) {
                    event.preventDefault();
                    $(event.target.form).submit();
                },
                toggle: function (event) {
                    var $el = $(event.target),
                        $chk = false;
                    if ($el.is('input.option-check')) {
                        return;
                    }
                    $chk = $el.closest('.answer-option').find('input.option-check');
                    if ($chk.is(':checkbox')) {
                        $chk[0].checked = !$chk[0].checked;
                    } else {
                        $chk[0].checked = true;
                    }
                }
            }
        });
    });
    $('htmlv').one('click.xxxx', '.course-item', function (e) {
        var $target = $(e.target),
            $a = $target.closest('.course-item').find('a');

        jQuery.ajax({
            url: $a.attr('href'),
            success: function (res) {
                var $dom = $(document.createElement("html"));
                $dom[0].innerHTML = res;

                var $head = $dom.find("head"),
                    $body = $dom.find("body");

                var $oldHead = $('head'),
                    $oldBody = $('body');

                jQuery('html').append($head).append($body).load(function () {
                    alert();
                });
                setTimeout(function ($a, $b) {
                    $a.remove();
                    $b.remove();
                }, 300, $oldHead, $oldBody)
                //$oldHead.remove();
                //$oldBody.remove();

                LP.setUrl($a.attr('href'))
            }
        });
        e.preventDefault();
    })


})(jQuery, _, Vue);