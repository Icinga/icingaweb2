/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Behavior.Detach
 *
 * Detaches DOM elements before an auto-refresh and attaches them back afterwards
 */
(function(Icinga, $) {

    'use strict';

    function Detach(icinga) {
        Icinga.EventListener.call(this, icinga);
    }

    Detach.prototype = new Icinga.EventListener();

    /**
     * Mutates the HTML before it is placed in the DOM after a reload
     *
     * @param   content     {string}    The content to be rendered
     * @param   $container  {jQuery}    The target container
     * @param   action      {string}    The URL that caused the reload
     * @param   autorefresh {bool}      Whether the rendering is due to an auto-refresh
     *
     * @return  {string|null}           The content to be rendered or null, when nothing should be changed
     */
    Detach.prototype.renderHook = function(content, $container, action, autorefresh) {
        // Exit early
        if (! autorefresh) {
            return content;
        } else {
            var containerId = $container.attr('id');

            if (containerId === 'menu' || containerId === 'application-state') {
                return content;
            }
        }

        if (! $container.find('.detach:first').length) {
            return content;
        }

        var $content = $('<div></div>').append(content);
        var icinga = this.icinga;

        $content.find('.detach').each(function() {
            // Selector only works w/ IDs because it was initially built to work w/ absolute paths only
            var $detachTarget = $(this);
            var detachTargetId = $detachTarget.attr('id');
            if (detachTargetId === undefined) {
                return;
            }

            var selector = '#' + detachTargetId + ':first';
            var $detachSource = $container.find(selector);

            if ($detachSource.length) {
                icinga.logger.debug('Detaching ' + selector);
                $detachSource.detach();
                $detachTarget.replaceWith($detachSource);
                $detachTarget.remove();
            }
        });

        return $content.html();
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.Detach = Detach;

}) (Icinga, jQuery);
