/*! Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

;(function(Icinga, $) {

    "use strict";

    /**
     * Toggle the CSS class active of the dropdown navigation item
     *
     * Called when the dropdown toggle has been activated via mouse or keyobard. This will expand/collpase the dropdown
     * menu according to CSS.
     *
     * @param {object} e Event
     */
    function setActive(e) {
        $(this).parent().toggleClass('active');
    }

    /**
     * Clear active state of the dropdown navigation item when the mouse leaves the navigation item
     *
     * @param {object} e Event
     */
    function clearActive(e) {
        $(this).removeClass('active');
    }

    /**
     * Clear active state of the dropdown navigation item when the navigation items loses focus
     *
     * @param {object} e Event
     */
    function clearFocus(e) {
        var $dropdown = $(this);
        // Timeout is required to wait for the next element in the DOM to receive focus
        setTimeout(function() {
            if (! $.contains($dropdown[0], document.activeElement)) {
                $dropdown.removeClass('active');
            }
        }, 10);
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Behavior for dropdown navigation items
     *
     * The dropdown behavior listens for activity on dropdown navigation items for toggling the CSS class
     * active on them. CSS is responsible for the expanded and collapsed state.
     *
     * @param {Icinga} icinga
     *
     * @constructor
     */
    var Dropdown = function (icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('click', '.dropdown-nav-item > a', setActive, this);
        this.on('mouseleave', '.dropdown-nav-item', clearActive, this);
        this.on('focusout', '.dropdown-nav-item', clearFocus, this);
    };

    Dropdown.prototype = new Icinga.EventListener();

    Icinga.Behaviors.Dropdown = Dropdown;

})(Icinga, jQuery);
