/*! Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

;(function(Icinga, $) {

    'use strict';

    /**
     * In case of unsufficient space hide tabitems behind a dropdown
     *
     * @param {object} e Event
     */
    function onRendered(e) {
        var $tabs = $(this).find(".tabs");

        if ($tabs.length) {
            var breakIndex = determineBreakIndex($tabs)

            if (breakIndex) {
                createDropdown($tabs, breakIndex);
            } else {
                console.log("tab items fit", breakIndex);
            }
        }
    }

    /**
     * Hide tab items behind a dropdown
     *
     * @param {jQuery} $tabs      The tab navigation element to modify
     * @param {int}    breakindex The index for the first tab to hide
     */
    function createDropdown($tabs, breakIndex) {
        var $tabItems = $tabs.children('li');
        var index = breakIndex - 1;

        if ($tabs.children('.additional-items').length < 1) {

            var $additionalTabsDropdown = $('<li class="dropdown-nav-item additional-items"><a href="#" class="dropdown-toggle"><i class="icon-attention-alt"></i></a><ul class="nav"></ul></li>');

            for (var i = index; i < $tabItems.length; i++) {
                var $item = $($tabItems.get(i));
                if ($item.children('ul.nav').length > 0) {

                    $item.children('ul.nav').children('li').each(function(j) {

                        var $clonedItem = $(this).clone();

                        console.log("itemtext", $clonedItem.text());

                        $additionalTabsDropdown.children('ul').append($clonedItem);
                    });
                } else {
                    var $clonedItem = $item.clone();
                    $additionalTabsDropdown.children('ul').append( $clonedItem );
                    $clonedItem.children('a').append($clonedItem.children("a").attr("title"));
                }
                $item.hide();
            }

            $tabs.append($additionalTabsDropdown);
        }
    }

    /**
     *
     *
     * @param {jQuery}    $tabContainer   The element containing the tabs
     *
     * @returns {Bool} false if there is sufficiently wide, the index of the first tab
     */
    function determineBreakIndex($tabContainer) {
        var breakIndex = false;
        var $tabItems = $tabContainer.children('li');
        var itemsWidth = 0;

        $tabItems.each(function(i) {

            itemsWidth += $(this).width() + parseFloat($(this).css('margin-right'));

            if (itemsWidth > $tabContainer.width()) {
                breakIndex = i;
            }

            console.log("too wide?", itemsWidth, ":", $tabContainer.width());
        });

        return breakIndex;
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Behavior for managing tab bar width
     *
     * The ResponsiveTabBar will wrap tabs in a dropdown if the containing
     * tab bar becomes too narrow
     *
     * @param {Icinga} icinga
     *
     * @constructor
     */
    var ResponsiveTabBar = function(icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('rendered', '#col1, #col2', onRendered, this);
        $(document).on("resize", onRendered, this);
    };

    ResponsiveTabBar.prototype = new Icinga.EventListener();

    Icinga.Behaviors.ResponsiveTabBar = ResponsiveTabBar;
})(Icinga, jQuery);
