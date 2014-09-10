// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

(function(Icinga, $) {

    "use strict";

    var activeMenuId;

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Navigation = function (icinga) {
        this.icinga = icinga;
    };

    Navigation.prototype.apply = function(el) {
        // restore old menu state
        if (activeMenuId) {
            $('[role="navigation"] li.active', el).removeClass('active');
            var $selectedMenu = $('#' + activeMenuId, el).addClass('active');
            var $outerMenu = $selectedMenu.parent().closest('li');
            if ($outerMenu.size()) {
                $outerMenu.addClass('active');
            }
        } else {
            // store menu state
            var $menus = $('[role="navigation"] li.active', el);
            if ($menus.size()) {
                activeMenuId = $menus[0].id;
            }
        }
    };

    Navigation.prototype.bind = function() {
        $(document).on('click', '#menu a', { self: this }, this.linkClicked);
        $(document).on('click', '#menu tr[href]', { self: this }, this.linkClicked);
        $(document).on('mouseenter', 'li.dropdown', this.dropdownHover);
        $(document).on('mouseleave', 'li.dropdown', {self: this}, this.dropdownLeave);
        $(document).on('mouseenter', '#menu > ul > li', { self: this }, this.menuTitleHovered);
        $(document).on('mouseleave', '#sidebar', { self: this }, this.leaveSidebar);
    };

    Navigation.prototype.unbind = function() {
        $(document).off('click', '#menu a', this.linkClicked);
        $(document).off('click', '#menu tr[href]', this.linkClicked);
        $(document).off('mouseenter', 'li.dropdown', this.dropdownHover);
        $(document).off('mouseleave', 'li.dropdown', this.dropdownLeave);
        $(document).off('mouseenter', '#menu > ul > li', this.menuTitleHovered);
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
