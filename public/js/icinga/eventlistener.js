/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

/**
 * EventListener contains event handlers and can bind / and unbind them from
 * event emitting objects
 */
(function(Icinga, $) {

    "use strict";

    var EventListener = function (icinga) {
        this.icinga = icinga;
        this.handlers = [];
    };

    /**
     * Add an handler to this EventLister
     *
     * @param evt   {String}    The name of the triggering event
     * @param cond  {String}    The filter condition
     * @param fn    {Function}  The event handler to execute
     * @param scope {Object}    The optional 'this' of the called function
     */
    EventListener.prototype.on = function(evt, cond, fn, scope) {
        if (typeof cond === 'function') {
            scope = fn;
            fn = cond;
            cond = 'body';
        }
        this.icinga.logger.debug('on: ' + evt + '(' + cond + ')');
        this.handlers.push({ evt: evt, cond: cond, fn: fn, scope: scope });
    };

    /**
     * Bind all listeners to the given event emitter
     *
     * All event handlers will be executed when the associated event is
     * triggered on the given Emitter.
     *
     * @param emitter   {String}   An event emitter that supports the function
     *                             'on' to register listeners
     */
    EventListener.prototype.bind = function (emitter) {
        var _this = this;
        $.each(this.handlers, function(i, handler) {
            _this.icinga.logger.debug('bind: ' + handler.evt + '(' + handler.cond + ')');
            emitter.on(
                handler.evt, handler.cond,
                {
                    self: handler.scope || emitter,
                    icinga: _this.icinga
                }, handler.fn
            );
        });
    };

    /**
     * Unbind all listeners from the given event emitter
     *
     * @param emitter   {String}    An event emitter that supports the function
     *                              'off' to un-register listeners.
     */
    EventListener.prototype.unbind = function (emitter) {
        var _this = this;
        $.each(this.handlers, function(i, handler) {
            _this.icinga.logger.debug('unbind: ' + handler.evt + '(' + handler.cond + ')');
            emitter.off(handler.evt, handler.cond, handler.fn);
        });
    };

    Icinga.EventListener = EventListener;

}) (Icinga, jQuery);
