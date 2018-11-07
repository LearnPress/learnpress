/**
 * LearnPress Notifications Module
 *
 * @author ThimPress
 * @package LearnPress/JS/Notifications
 * @version 3.2.0
 */
;(function ($) {
   // $(document).ready(load);

    function load() {
        var $notifications = $('<div id="learn-press-bubble-notifications" v-show="messages.length"><div v-for="(noti, index) in messages" :class="noti.type"><i class="icon"></i><div class="message" v-html="noti.message"></div></div></div>').appendTo(document.body);

        if (!LP.$vms) {
            LP.$vms = {};
        }

        // Push instance of notifications to LP global object
        LP.$vms['notifications'] = new Vue({
            el: $notifications[0],
            data: function () {
                return {
                    timer: null,
                    messages: []
                }
            },
            computed: {
                reversedMessage: function () {
                    return this.messages.reverse();
                }
            },
            created: function () {
            },
            mounted: function () {
                this.timer && clearInterval(this.timer);
                this.timer = setInterval(this.get, 10000);
            },
            methods: {
                get: function () {
                    var $vm = this;
                    $.ajax({
                        url: '',
                        data: {
                            'lp-ajax': 'get_notifications'
                        },
                        success: function (r) {
                            r = LP.parseJSON(r);
                            if (r) {
                                $vm.add(r);
                            }

                        }
                    });
                },
                scheduleItem: function () {
                    return function ($vm, it) {
                        setTimeout(function () {
                            var index = $vm.messages.findIndex(function (a) {
                                return a._uid == it._uid
                            });

                            if (index > -1) {
                                $vm.messages.splice(index, 1);
                            }
                        }, it.duration || 3000);
                    }
                },
                add: function (messages) {
                    if (!messages) {
                        return;
                    }

                    if (typeof messages === 'string') {
                        messages = [{message: messages}];
                    } else if ($.isPlainObject(messages)) {
                        messages = [messages];
                    }

                    for (var i = 0, n = messages.length; i < n; i++) {

                        if (!messages[i].type) {
                            messages[i].type = 'success'
                        }

                        if (!messages[i]._uid) {
                            messages[i]._uid = LP.uniqueId();
                        }

                        this.messages.push(messages[i]);

                        // close in 3 secs
                        new (this.scheduleItem())(this, messages[i]);
                    }
                },
                iconClass: function (m) {
                    var cls = ['fa', m.type || 'success'];

                    switch (m.type) {
                        case 'error':
                            cls.push('')
                    }

                    return cls;
                }
            }
        })
    }
})(jQuery);