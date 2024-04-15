/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

/**
 * Controls behavior of form elements, depending reload and
 */
(function(Icinga, $) {

    "use strict";

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Form = function (icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('rendered', '.container', this.onRendered, this);

        this.priority = 1;

        // store the modification state of all input fields
        this.inputs = new WeakMap();
    };
    Form.prototype = new Icinga.EventListener();

    /**
     * @param event
     */
    Form.prototype.onRendered = function (event) {
        var _this = event.data.self;
        var container = event.target;

        container.querySelectorAll('form input').forEach(function (input) {
            if (! _this.inputs.has(input) && input.type !== 'hidden') {
                _this.inputs.set(input, input.value);
                _this.icinga.logger.debug('registering "' + input.value + '" as original input value');
            }
        });
    };

    /**
     * Mutates the HTML before it is placed in the DOM after a reload
     *
     * @param content       {String}    The content to be rendered
     * @param $container    {jQuery}    The target container where the html will be rendered in
     * @param action        {String}    The action-url that caused the reload
     * @param autorefresh   {Boolean}   Whether the rendering is due to an autoRefresh
     * @param autoSubmit    {Boolean}   Whether the rendering is due to an autoSubmit
     *
     * @returns {string|NULL}           The content to be rendered, or NULL, when nothing should be changed
     */
    Form.prototype.renderHook = function(content, $container, action, autorefresh, autoSubmit) {
        if ($container.attr('id') === 'menu') {
            var $search = $container.find('#search');
            if ($search[0] === document.activeElement) {
                return null;
            }
            if ($search.length) {
                var $content = $('<div></div>').append(content);
                $content.find('#search').attr('value', $search.val()).addClass('active');
                return $content.html();
            }
            return content;
        }

        if (! autorefresh || autoSubmit) {
            return content;
        }

        var _this = this;
        var changed = false;
        $container[0].querySelectorAll('form input').forEach(function (input) {
            if (_this.inputs.has(input) && _this.inputs.get(input) !== input.value) {
                changed = true;
                _this.icinga.logger.debug(
                    '"' + _this.inputs.get(input) + '" was changed ("' + input.value + '") and aborts reload...'
                );
            }
        });
        if (changed) {
            return null;
        }

        const origFocus = document.activeElement;
        const containerId = $container.attr('id');
        if ($container[0].contains(origFocus)
            && origFocus.form
            && ! origFocus.matches(
                'input[type=submit], input[type=reset], input[type=button], .autofocus, .autosubmit:not(:hover)'
            )
        ) {
            this.icinga.logger.debug('Not changing content for ' + containerId + ' form has focus');
            return null;
        }

        return content;
    };

    Icinga.Behaviors.Form = Form;

}) (Icinga, jQuery);
