// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * Controls behavior of form elements, depending reload and
 */
(function(Icinga, $) {

    "use strict";

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Form = function (icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('keyup change', 'form input', this.onChange, this);

        // store the modification state of all input fields
        this.inputs = {};
    };
    Form.prototype = new Icinga.EventListener();

    /**
     * @param evt
     */
    Form.prototype.onChange = function (evt) {
        var el = evt.target;
        var form = evt.data.self.uniqueFormName($(el).closest('form')[0] || {});
        evt.data.self.inputs[form] = evt.data.self.inputs[form] || {};
        if (el.value !== '') {
            evt.data.self.inputs[form][el.name] = true;
        } else {
            evt.data.self.inputs[form][el.name] = false;
        }
    };

    /**
     * Try to generate an unique form name using the action
     * and the name of the given form element
     *
     * @param   form    {HTMLFormElement}   The
     * @returns         {String}            The unique name
     */
    Form.prototype.uniqueFormName = function(form)
    {
        return (form.name || 'undefined') + '.' + (form.action || 'undefined');
    };

    /**
     * Mutates the HTML before it is placed in the DOM after a reload
     *
     * @param content       {String}    The content to be rendered
     * @param $container    {jQuery}    The target container where the html will be rendered in
     * @param action        {String}    The action-url that caused the reload
     * @param autorefresh   {Boolean}   Whether the rendering is due to an autoRefresh
     *
     * @returns {string|NULL}           The content to be rendered, or NULL, when nothing should be changed
     */
    Form.prototype.renderHook = function(content, $container, action, autorefresh) {
        var origFocus = document.activeElement;
        var containerId = $container.attr('id');
        var icinga = this.icinga;
        var self = this.icinga.behaviors.form;
        var changed = false;
        $container.find('form').each(function () {
            var form = self.uniqueFormName(this);
            if (autorefresh) {
                // check if an element in this container was changed
                $(this).find('input').each(function () {
                    var name = this.name;
                    if (self.inputs[form] && self.inputs[form][name]) {
                        icinga.logger.debug(
                            'form input: ' + form + '.' + name + ' was changed and aborts reload...'
                        );
                        changed = true;
                    }
                });
            } else {
                // user-triggered reload, forget all changes to forms in this container
                self.inputs[form] = null;
            }
        });
        if (changed) {
            return null;
        }
        if (
            // is the focus among the elements to be replaced?
            $container.has(origFocus).length &&
                // is an autorefresh
                autorefresh &&

                // and has focus
                $(origFocus).length &&
                !$(origFocus).hasClass('autofocus') &&
                $(origFocus).closest('form').length
            ) {
            icinga.logger.debug('Not changing content for ' + containerId + ' form has focus');
            return null;
        }
        return content;
    };

    Icinga.Behaviors.Form = Form;

}) (Icinga, jQuery);
