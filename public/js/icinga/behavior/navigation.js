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
        // restore menu state
        if (activeMenuId) {
            $('[role="navigation"] li.active', el).removeClass('active');

            var $selectedMenu = $('#' + activeMenuId, el);
            var $outerMenu = $selectedMenu.parent().closest('li');
            if ($outerMenu.size()) {
                $selectedMenu = $outerMenu;
            }
            $selectedMenu.addClass('active');
        } else {
            // store menu state
            var $menus = $('[role="navigation"] li.active', el);
            if ($menus.size()) {
                activeMenuId = $menus[0].id;
            }
        }
    };

    Navigation.prototype.bind = function() {
        $(document).on('click', 'a', { self: this }, this.linkClicked);
        $(document).on('click', 'tr[href]', { self: this }, this.linkClicked);
    };

    Navigation.prototype.unbind = function() {
        $(document).off('click', 'a', this.linkClicked);
        $(document).off('click', 'tr[href]', this.linkClicked);
    };

    Navigation.prototype.linkClicked = function(event) {
        var $a = $(this);
        var href = $a.attr('href');
        var isMenuLink = $a.closest('#menu').length > 0;
        var $li;
        var icinga = event.data.self.icinga;

        if (href.match(/#/)) {
            $li = $a.closest('li');
            if (isMenuLink) {
                activeMenuId = $($li).attr('id');
            }
        } else {
            if (isMenuLink) {
                activeMenuId = $(event.target).closest('li').attr('id');
            }
        }
        if (isMenuLink) {
            var $menu = $('#menu');
            // update target url of the menu container to the clicked link
            var menuDataUrl = icinga.utils.parseUrl($menu.data('icinga-url'));
            menuDataUrl = icinga.utils.addUrlParams(menuDataUrl.path, { url: href });
            $menu.data('icinga-url', menuDataUrl);
        }
    };

    Icinga.Behaviors.Navigation = Navigation;

}) (Icinga, jQuery);
