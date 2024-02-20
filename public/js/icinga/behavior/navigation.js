/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

(function(Icinga, $) {

    "use strict";

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Navigation = function (icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('click', '#menu a', this.linkClicked, this);
        this.on('click', '#menu tr[href]', this.linkClicked, this);
        this.on('rendered', '#menu', this.onRendered, this);
        this.on('mouseenter', '#menu .primary-nav .nav-level-1 > .nav-item', this.showFlyoutMenu, this);
        this.on('mouseleave', '#menu .primary-nav', this.hideFlyoutMenu, this);
        this.on('click', '#toggle-sidebar', this.toggleSidebar, this);

        this.on('click', '#menu .config-nav-item button', this.toggleConfigFlyout, this);
        this.on('mouseenter', '#menu .config-menu .config-nav-item', this.showConfigFlyout, this);
        this.on('mouseleave', '#menu .config-menu .config-nav-item', this.hideConfigFlyout, this);

        this.on('keydown', '#menu .config-menu .config-nav-item', this.onKeyDown, this);

        /**
         * The DOM-Path of the active item
         *
         * @see getDomPath
         *
         * @type {null|Array}
         */
        this.active = null;

        /**
         * The menu
         *
         * @type {jQuery}
         */
        this.$menu = null;

        /**
         * Local storage
         *
         * @type {Icinga.Storage}
         */
        this.storage = Icinga.Storage.BehaviorStorage('navigation');

        this.storage.setBackend(window.sessionStorage);

        // Restore collapsed sidebar if necessary
        if (this.storage.get('sidebar-collapsed')) {
            $('#layout').addClass('sidebar-collapsed');
        }
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
                    _this.setActiveAndSelected($(this));
                });
            } else {
                // if no item is marked as active, try to select the menu from the current URL
                _this.setActiveAndSelectedByUrl($('#col1').data('icingaUrl'));
            }
        }

        _this.refresh();
    };

    /**
     * Re-render the menu selection according to the current state
     */
    Navigation.prototype.refresh = function() {
        // restore selection to current active element
        if (this.active) {
            var $el = $(this.icinga.utils.getElementByDomPath(this.active));
            this.setActiveAndSelected($el);

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
    };

    /**
     * Handle a link click in the menu
     *
     * @param event
     */
    Navigation.prototype.linkClicked = function(event) {
        var $a = $(this);
        var href = $a.attr('href');
        var _this = event.data.self;
        var icinga = _this.icinga;

        // Check for ctrl or cmd click to open new tab and don't unfold other menus
        if (event.ctrlKey || event.metaKey) {
            return false;
        }

        if (href.match(/#/)) {
            // ...it may be a menu section without a dedicated link.
            // Switch the active menu item:
            _this.setActiveAndSelected($a);
        } else {
            _this.setActiveAndSelected($(event.target));
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
    Navigation.prototype.setActiveAndSelectedByUrl = function(url) {
        var $menu = $('#menu');

        if (! $menu.length) {
            return;
        }

        // try to active the first item that has an exact URL match
        this.setActiveAndSelected($menu.find('[href="' + url + '"]'));

        // the url may point to the search field, which must be activated too
        if (! this.active) {
            this.setActiveAndSelected($menu.find('form[action="' + this.icinga.utils.parseUrl(url).path + '"]'));
        }

        // some urls may have custom filters which won't match any menu item, in that case search
        // for a menu item that points to the base action without any filters
        if (! this.active) {
            this.setActiveAndSelected($menu.find('[href="' + this.icinga.utils.parseUrl(url).path + '"]').first());
        }
    };

    /**
     * Try to select a new URL by
     *
     * @param url
     */
    Navigation.prototype.trySetActiveAndSelectedByUrl = function(url) {
        var active = this.active;
        this.setActiveAndSelectedByUrl(url);

        if (! this.active && active) {
            this.setActiveAndSelected($(this.icinga.utils.getElementByDomPath(active)));
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
     * Remove all selected elements
     */
    Navigation.prototype.clearSelected = function() {
        if (this.$menu) {
            this.$menu.find('.selected').removeClass('selected');
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

    Navigation.prototype.setActiveAndSelected = function ($el) {
        if ($el.length > 1) {
            $el.each((key, el) => {
                if (! this.active) {
                    this.setActiveAndSelected($(el));
                }
            });
        } else if ($el.length) {
            let parent = $el[0].closest('.nav-level-1 > .nav-item, .config-menu');

            if ($el[0].offsetHeight || $el[0].offsetWidth || parent.offsetHeight || parent.offsetWidth) {
                // It's either a visible menu item or a config menu item
                this.setActive($el);
                this.setSelected($el);
            }
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

    Navigation.prototype.setSelected = function($el) {
        this.clearSelected();
        $el = $el.closest('li');

        if ($el.length) {
            $el.addClass('selected');
        }
    };

    /**
     * Reset the active element to nothing
     */
    Navigation.prototype.resetActive = function() {
        this.clear();
        this.active = null;
    };

    /**
     * Reset the selected element to nothing
     */
    Navigation.prototype.resetSelected = function() {
        this.clearSelected();
        this.selected = null;
    };

    /**
     * Show the fly-out menu
     *
     * @param e
     */
    Navigation.prototype.showFlyoutMenu = function(e) {
        var $layout = $('#layout');

        if ($layout.hasClass('minimal-layout')) {
            return;
        }

        var $target = $(this);
        var $flyout = $target.find('.nav-level-2');

        if (! $flyout.length) {
            $layout.removeClass('menu-hovered');
            $target.siblings().not($target).removeClass('hover');
            return;
        }

        var delay = 300;

        if ($layout.hasClass('menu-hovered')) {
            delay = 0;
        }

        setTimeout(function() {
            try {
                if (! $target.is(':hover')) {
                    return;
                }
            } catch(e) { /* Bypass because if IE8 */ }

            $layout.addClass('menu-hovered');
            $target.siblings().not($target).removeClass('hover');
            $target.addClass('hover');

            $flyout.css({
                bottom: 'auto',
                top: $target.offset().top + $target.outerHeight()
            });

            var rect = $flyout[0].getBoundingClientRect();

            if (rect.y + rect.height > window.innerHeight) {
                $flyout.addClass('bottom-up');
                $flyout.css({
                    bottom: window.innerHeight - ($target.offset().top + $target.find('> a')[0].offsetHeight),
                    top: 'auto'
                });
            } else {
                $flyout.removeClass('bottom-up');
            }
        }, delay);
    };

    /**
     * Hide the fly-out menu
     *
     * @param e
     */
    Navigation.prototype.hideFlyoutMenu = function(e) {
        var $layout = $('#layout');
        var $nav = $(e.currentTarget);
        var $hovered = $nav.find('.nav-level-1 > .nav-item.hover');

        if (! $hovered.length) {
            $layout.removeClass('menu-hovered');

            return;
        }

        setTimeout(function() {
            try {
                if ($hovered.is(':hover') || $nav.is(':hover')) {
                    return;
                }
            } catch(e) { /* Bypass because if IE8 */ };
            $hovered.removeClass('hover');
            $layout.removeClass('menu-hovered');
        }, 600);
    };

    /**
     * Collapse or expand sidebar
     *
     * @param {Object} e Event
     */
    Navigation.prototype.toggleSidebar = function(e) {
        var _this = e.data.self;
        var $layout = $('#layout');
        $layout.toggleClass('sidebar-collapsed');
        _this.storage.set('sidebar-collapsed', $layout.is('.sidebar-collapsed'));
        $(window).trigger('resize');
    };

    /**
     * Toggle config flyout visibility
     *
     * @param {Object} e Event
     */
    Navigation.prototype.toggleConfigFlyout = function(e) {
        var _this = e.data.self;
        if ($('#layout').is('.config-flyout-open')) {
            _this.hideConfigFlyout(e);
        } else {
            _this.showConfigFlyout(e);
        }
    }

    /**
     * Hide config flyout
     *
     * @param {Object} e Event
     */
    Navigation.prototype.hideConfigFlyout = function(e) {
        $('#layout').removeClass('config-flyout-open');
        if (e.target) {
            delete $(e.target).closest('.container')[0].dataset.suspendAutorefresh;
        }
    }

    /**
     * Show config flyout
     *
     * @param {Object} e Event
     */
    Navigation.prototype.showConfigFlyout = function(e) {
        $('#layout').addClass('config-flyout-open');
        $(e.target).closest('.container')[0].dataset.suspendAutorefresh = '';
    }

    /**
     * Hide, config flyout when "Enter" key is pressed to follow `.flyout` nav item link
     *
     * @param {Object} e Event
     */
    Navigation.prototype.onKeyDown = function(e) {
        var _this = e.data.self;

        if (e.key == 'Enter' && $(document.activeElement).is('.flyout a')) {
            _this.hideConfigFlyout(e);
        }
    }

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
            this.setActiveAndSelected($(active))
        } else {
            this.resetActive();
            this.resetSelected();
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

    Icinga.Behaviors.Navigation = Navigation;

})(Icinga, jQuery);
