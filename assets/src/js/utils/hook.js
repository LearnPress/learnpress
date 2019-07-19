const Hook = {
    hooks: {action: {}, filter: {}},
    addAction: function (action, callable, priority, tag) {
        this.addHook('action', action, callable, priority, tag);
        return this;
    },
    addFilter: function (action, callable, priority, tag) {
        this.addHook('filter', action, callable, priority, tag);
        return this;
    },
    doAction: function (action) {
        this.doHook('action', action, arguments);
        return this;
    },
    applyFilters: function (action) {
        return this.doHook('filter', action, arguments);
    },
    removeAction: function (action, tag) {
        this.removeHook('action', action, tag);
        return this;
    },
    removeFilter: function (action, priority, tag) {
        this.removeHook('filter', action, priority, tag);
        return this;
    },
    addHook: function (hookType, action, callable, priority, tag) {
        if (undefined === this.hooks[hookType][action]) {
            this.hooks[hookType][action] = [];
        }
        var hooks = this.hooks[hookType][action];
        if (undefined === tag) {
            tag = action + '_' + hooks.length;
        }
        this.hooks[hookType][action].push({tag: tag, callable: callable, priority: priority});
        return this;
    },
    doHook: function (hookType, action, args) {

        // splice args from object into array and remove first index which is the hook name
        args = Array.prototype.slice.call(args, 1);

        if (undefined !== this.hooks[hookType][action]) {
            var hooks = this.hooks[hookType][action], hook;
            //sort by priority
            hooks.sort(function (a, b) {
                return a["priority"] - b["priority"];
            });
            for (var i = 0; i < hooks.length; i++) {
                hook = hooks[i].callable;
                if (typeof hook !== 'function')
                    hook = window[hook];
                if ('action' === hookType) {
                    hook.apply(null, args);
                } else {
                    args[0] = hook.apply(null, args);
                }
            }
        }
        if ('filter' === hookType) {
            return args[0];
        }
        return this;
    },
    removeHook: function (hookType, action, priority, tag) {
        if (undefined !== this.hooks[hookType][action]) {
            var hooks = this.hooks[hookType][action];
            for (var i = hooks.length - 1; i >= 0; i--) {
                if ((undefined === tag || tag === hooks[i].tag) && (undefined === priority || priority === hooks[i].priority)) {
                    hooks.splice(i, 1);
                }
            }
        }
        return this;
    }
};

export default Hook;