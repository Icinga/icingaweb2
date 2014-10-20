// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

(function(Icinga, $) {

    "use strict";

    var activeMenuId;

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Navigation = function (icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('click', '#menu a', this.linkClicked, this);
        this.on('click', '#menu tr[href]', this.linkClicked, this);
        this.on('mouseenter', 'li.dropdown', this.dropdownHover, this);
        this.on('mouseleave', 'li.dropdown', this.dropdownLeave, this);
        this.on('mouseenter', '#menu > ul > li', this.menuTitleHovered, this);
        this.on('mouseleave', '#sidebar', this.leaveSidebar, this);
        this.on('rendered', this.onRendered);
    };
    Navigation.prototype = new Icinga.EventListener();

    Navigation.prototype.onRendered = function(evt) {
        // get original source element of the rendered-event
        var el = evt.target;
        if (activeMenuId) {
            // restore old menu state
            $('[role="navigation"] li.active', el).removeClass('active');
            var $selectedMenu = $('#' + activeMenuId).addClass('active');
            var $outerMenu = $selectedMenu.parent().closest('li');
            if ($outerMenu.size()) {
                $outerMenu.addClass('active');
            }
        } else {
            // store menu state
            var $menus = $('[role="navigation"] li.active', el);
            if ($menus.size()) {
                activeMenuId = $menus[0].id;
                $menus.find('li.active').first().each(function () {
                    activeMenuId = this.id;
                });
            }
        }
    };

    Navigation.prototype.linkClicked = function(event) {
        var $a = $(this);
        var href = $a.attr('href');
        var $li;
        var icinga = event.data.self.icinga;

        if (href.match(/#/)) {
            // ...it may be a menu section without a dedicated link.
            // Switch the active menu item:
            $li = $a.closest('li');
            $('#menu .active').removeClass('active');
            $li.addClass('active');
            activeMenuId = $($li).attr('id');
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
            activeMenuId = $(event.target).closest('li').attr('id');
        }
        // update target url of the menu container to the clicked link
        var $menu = $('#menu');
        var menuDataUrl = icinga.utils.parseUrl($menu.data('icinga-url'));
        menuDataUrl = icinga.utils.addUrlParams(menuDataUrl.path, { url: href });
        $menu.data('icinga-url', menuDataUrl);
    };

    Navigation.prototype.setActiveByUrl = function(url)
    {
        this.resetActive();
        this.setActive($('#menu [href="' + url + '"]'));
    }

    /**
     * Change the active menu element
     *
     * @param $el   {jQuery}    A selector pointing to the active element
     */
    Navigation.prototype.setActive = function($el) {

        $el.closest('li').addClass('active');
        $el.parents('li').addClass('active');
        activeMenuId = $el.closest('li').attr('id');
    };

    Navigation.prototype.resetActive = function() {
        $('#menu .active').removeClass('active');
        activeMenuId = null;
    };

    Navigation.prototype.menuTitleHovered = function(event) {
        var $li = $(this),
            delay = 800,
            self = event.data.self;

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
            if (! $li.is('li:hover')) {
                return;
            }
            if ($li.hasClass('active')) {
                return;
            }

            $li.siblings().each(function () {
                var $sibling = $(this);
                if ($sibling.is('li:hover')) {
                    return;
                }
                if ($sibling.hasClass('hover')) {
                    $sibling.removeClass('hover');
                }
            });

            self.hoverElement($li);
        }, delay);
    };

    Navigation.prototype.leaveSidebar = function (event) {
        var $sidebar = $(this),
            $li = $sidebar.find('li.hover'),
            self = event.data.self;
        if (! $li.length) {
            $('#layout').removeClass('hoveredmenu');
            return;
        }

        setTimeout(function () {
            if ($li.is('li:hover') || $sidebar.is('sidebar:hover') ) {
                return;
            }
            $li.removeClass('hover');
            $('#layout').removeClass('hoveredmenu');
        }, 500);
    };

    Navigation.prototype.hoverElement = function ($li)  {
        $('#layout').addClass('hoveredmenu');
        $li.addClass('hover');
    };

    Navigation.prototype.dropdownHover = function () {
        $(this).addClass('hover');
    };

    Navigation.prototype.dropdownLeave = function (event) {
        var $li = $(this),
            self = event.data.self;
        setTimeout(function () {
            // TODO: make this behave well together with keyboard navigation
            if (! $li.is('li:hover') /*&& ! $li.find('a:focus')*/) {
                $li.removeClass('hover');
            }
        }, 300);
    };
    Icinga.Behaviors.Navigation = Navigation;

}) (Icinga, jQuery);
