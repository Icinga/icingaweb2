// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

(function(Icinga) {

    /**
     * Used to define a set of functionality that can be applied
     * on a subtree of the site's DOM
     *
     * Behaviors
     *
     * @constructor
     */
    Icinga.Behavior = function () {
        this.handler = {
            apply: [],
            bind: [],
            unbind: []
        };
    };

    Icinga.Behavior.prototype.on = function(evt, fn) {
        this.handler[evt].push(fn);
    };

    Icinga.Behavior.prototype.off = function(evt, fn) {
        this.handler[evt].remove(fn);
    };

    Icinga.Behavior.prototype.trigger = function(evt, el) {
        var handler = this.handler[evt];
        for (var i = 0; i < handler.length; i++) {
            if (typeof handler[i] === 'function') {
                handler[i](el);
            }
        }
    };

    Icinga.Behavior.prototype.onApply = function(fn) {
        this.on('apply', fn);
    };

    Icinga.Behavior.prototype.onBind = function(fn) {
        this.on('bind', fn);
    };

    Icinga.Behavior.prototype.onUnbind = function(fn) {
        this.on('unbind', fn);
    };

    Icinga.Behavior.prototype.apply = function(el) {
        this.trigger ('apply', el);
    };

    Icinga.Behavior.prototype.bind = function(el) {
        this.trigger ('bind', el);
    };

    Icinga.Behavior.prototype.unbind = function(el) {
        this.trigger ('apply', el);
    };

    Icinga.Behavior.prototype.off = function() {
        this.handler = {
            apply: [],
            bind: [],
            unbind: []
        };
    };
}) (Icinga);