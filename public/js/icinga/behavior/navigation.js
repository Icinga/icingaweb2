/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

(function(Icinga, $) {

    "use strict";

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Navigation = function (icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('click', '#menu a', this.linkClicked, this);
        this.on('click', '#menu tr[href]', this.linkClicked, this);
        this.on('mouseenter', '#menu > nav > ul > li', this.menuTitleHovered, this);
        this.on('mouseleave', '#sidebar', this.leaveSidebar, this);
        this.on('rendered', '#menu', this.onRendered, this);

        /**
         * The DOM-Path of the active item
         *
         * @see getDomPath
         *
         * @type {null|Array}
         */
        this.active = null;

        /**
         * The DOM-Path of the hovered item
         *
         * @see getDomPath
         *
         * @type {null|Array}
         */
        this.hovered = null;

        /**
         * The menu
         *
         * @type {jQuery}
         */
        this.$menu = null;
    };
    Navigation.prototype = new Icinga.EventListener();

    /**
     * Activate menu items if their class is set to active or if the current URL matches their link
     *
     * @param {Object} e Event
     */
    Navigation.prototype.onRendered = function(e) {
        var _this = e.data.self;

        _this.$menu = $(e.target);

        if (! _this.active) {
            // There is no stored menu item, therefore it is assumed that this is the first rendering
            // of the navigation after the page has been opened.

            // initialise the menu selected by the backend as active.
            var $active = _this.$menu.find('li.active');
            if ($active.length) {
                $active.each(function() {
                    _this.setActive($(this));
                });
            } else {
                // if no item is marked as active, try to select the menu from the current URL
                _this.setActiveByUrl($('#col1').data('icingaUrl'));
            }
        }

        _this.refresh();
    };

    /**
     * Re-render the menu selection and menu hovering according to the current state
     */
    Navigation.prototype.refresh = function() {
        // restore selection to current active element
        if (this.active) {
            var $el = $(this.icinga.utils.getElementByDomPath(this.active));
            this.setActive($el);

            /*
             * Recreate the html content of the menu item to force the browser to update the layout, or else
             * the link would only be visible as active after another click or page reload in Gecko and WebKit.
             *
             * fixes #7897
             */
            if ($el.is('li')) {
                $el.html($el.html());
            }
        }

        // restore hovered menu to current hovered element
        if (this.hovered) {
            var hovered = this.icinga.utils.getElementByDomPath(this.hovered);
            if (hovered) {
                this.hoverElement($(hovered));
            }
        }
    };

    /**
     * Handle a link click in the menu
     *
     * @param event
     */
    Navigation.prototype.linkClicked = function(event) {
        var $a = $(this);
        var href = $a.attr('href');
        var $li;
        var _this = event.data.self;
        var icinga = _this.icinga;

        _this.hovered = null;
        if (href.match(/#/)) {
            // ...it may be a menu section without a dedicated link.
            // Switch the active menu item:
            _this.setActive($a);
            $li = $a.closest('li');
            if ($li.hasClass('hover')) {
                $li.removeClass('hover');
            }
            if (href === '#') {
                // Allow to access dropdown menu by keyboard
                if ($a.hasClass('dropdown-toggle')) {
                    $a.closest('li').toggleClass('hover');
                }
                return;
            }
        } else {
            _this.setActive($(event.target));
        }
        // update target url of the menu container to the clicked link
        var $menu = $('#menu');
        var menuDataUrl = icinga.utils.parseUrl($menu.data('icinga-url'));
        menuDataUrl = icinga.utils.addUrlParams(menuDataUrl.path, { url: href });
        $menu.data('icinga-url', menuDataUrl);
    };

    /**
     * Activate a menu item based on the current URL
     *
     * Activate a menu item that is an exact match or fall back to items that match the base URL
     *
     * @param url   {String}    The url to match
     */
    Navigation.prototype.setActiveByUrl = function(url) {

        // try to active the first item that has an exact URL match
        this.setActive(this.$menu.find('[href="' + url + '"]'));

        // the url may point to the search field, which must be activated too
        if (! this.active) {
            this.setActive(this.$menu.find('form[action="' + this.icinga.utils.parseUrl(url).path + '"]'));
        }

        // some urls may have custom filters which won't match any menu item, in that case search
        // for a menu item that points to the base action without any filters
        if (! this.active) {
            this.setActive(this.$menu.find('[href="' + this.icinga.utils.parseUrl(url).path + '"]').first());
        }
    };

    /**
     * Try to select a new URL by
     *
     * @param url
     */
    Navigation.prototype.trySetActiveByUrl = function(url) {
        var active = this.active;
        this.setActiveByUrl(url);
        if (! this.active && active) {
            this.setActive($(this.icinga.utils.getElementByDomPath(active)));
        }
    };

    /**
     * Remove all active elements
     */
    Navigation.prototype.clear = function() {
        if (this.$menu) {
            this.$menu.find('.active').removeClass('active');
        }
    };

    /**
     * Select all menu items in the selector as active and unfold surrounding menus when necessary
     *
     * @param   $item   {jQuery}    The jQuery selector
     */
    Navigation.prototype.select = function($item) {
        // support selecting the url of the menu entry
        var $input = $item.find('input');
        $item = $item.closest('li');

        if ($item.length) {
            // select the current item
            var $selectedMenu = $item.addClass('active');

            // unfold the containing menu
            var $outerMenu = $selectedMenu.parent().closest('li');
            if ($outerMenu.length) {
                $outerMenu.addClass('active');
            }
        } else if ($input.length) {
            $input.addClass('active');
        }
    };

    /**
     * Change the active menu element
     *
     * @param $el   {jQuery}    A selector pointing to the active element
     */
    Navigation.prototype.setActive = function($el) {
        this.clear();
        this.select($el);
        if ($el.closest('li')[0]) {
            this.active = this.icinga.utils.getDomPath($el.closest('li')[0]);
        } else if ($el.find('input')[0]) {
            this.active = this.icinga.utils.getDomPath($el[0]);
        } else {
            this.active = null;
        }
        // TODO: push to history
    };

    /**
     * Reset the active element to nothing
     */
    Navigation.prototype.resetActive = function() {
        this.clear();
        this.active = null;
    };

    /**
     * Called when the history changes
     *
     * @param url   The url of the new state
     * @param data  The active menu item of the new state
     */
    Navigation.prototype.onPopState = function (url, data) {
        // 1. get selection data and set active menu
        if (data) {
            var active = this.icinga.utils.getElementByDomPath(data);
            if (!active) {
                this.logger.fail(
                    'Could not restore active menu from history, path in DOM not found.',
                    data,
                    url
                );
                return;
            }
            this.setActive($(active));
        } else {
            this.resetActive();
        }
    };

    /**
     * Called when the current state gets pushed onto the history, can return a value
     * to be preserved as the current state
     *
     * @returns     {null|Array}    The currently active menu item
     */
    Navigation.prototype.onPushState = function () {
        return this.active;
    };

    Navigation.prototype.menuTitleHovered = function(event) {
        if ($('#layout').hasClass('minimal-layout')) {
            return;
        }

        var $li = $(this),
            delay = 800,
            _this = event.data.self;

        _this.hovered = null;
        if ($li.hasClass('active')) {
            $li.siblings().removeClass('hover');
            return;
        }
        if ($li.children('ul').children('li').length === 0) {
            return;
        }
        if ($('#menu').scrollTop() > 0) {
            return;
        }

        if ($('#layout').hasClass('hoveredmenu')) {
            delay = 0;
        }

        setTimeout(function () {
            try {
                if (!$li.is('li:hover')) {
                    return;
                }
                if ($li.hasClass('active')) {
                    return;
                }
            } catch(e) { /* Bypass because if IE8 */ }

            $li.siblings().each(function () {
                var $sibling = $(this);
                try {
                    if ($sibling.is('li:hover')) {
                        return;
                    }
                } catch(e) { /* Bypass because if IE8 */ };
                if ($sibling.hasClass('hover')) {
                    $sibling.removeClass('hover');
                }
            });
            _this.hoverElement($li);
        }, delay);
    };

    Navigation.prototype.leaveSidebar = function (event) {
        var $sidebar = $(this),
            $li = $sidebar.find('li.hover'),
            _this = event.data.self;
        if (! $li.length) {
            $('#layout').removeClass('hoveredmenu');
            return;
        }

        setTimeout(function () {
            try {
                if ($li.is('li:hover') || $sidebar.is('sidebar:hover')) {
                    return;
                }
            } catch(e) { /* Bypass because if IE8 */ };
            $li.removeClass('hover');
            $('#layout').removeClass('hoveredmenu');
        }, 500);
        _this.hovered = null;
    };

    Navigation.prototype.hoverElement = function ($li)  {
        $('#layout').addClass('hoveredmenu');
        $li.addClass('hover');
        if ($li[0]) {
            this.hovered = this.icinga.utils.getDomPath($li[0]);
        } else {
            this.hovered = null;
        }
    };

    Icinga.Behaviors.Navigation = Navigation;

}) (Icinga, jQuery);
