/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

(function(Icinga) {

    "use strict";

    class FlyoutMenu extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this.on('rendered', this.onRendered, this);

            this.on('click', '.flyout + button, [data-flyout-target]', this.onControlClick, this);

            this.on('click', '.flyout .flyout-menu a, .flyout .flyout-menu button[type=submit]', this.onMenuItemClick, this);

            // For Desktop
            this.on('mouseenter', '#menu .config-menu [data-flyout-target]', this.onMouseEnter, this);
            this.on('mouseleave', '#menu .config-menu [data-flyout-target]', this.onMouseLeave, this);

            this.on('keydown', '#menu .config-menu .config-nav-item', this.onKeyDown, this);
        }

        onMouseEnter(e) {
            let _this = e.data.self,
                flyout;
            var target = e.target.closest('[data-flyout-target]') || e.target.closest('button') || e.target;

            if (target.dataset !== undefined && target.dataset.flyoutTarget !== undefined && target.dataset.flyoutTarget !== '') {
                flyout = document.getElementById(target.dataset.flyoutTarget);
            } else {
                flyout = target.previousElementSibling;
            }

            _this.open(flyout);
        }

        onMouseLeave(e) {
            let _this = e.data.self,
                flyout;
            var target = e.target.closest('[data-flyout-target]') || e.target.closest('button') || e.target;

            if (target.dataset !== undefined && target.dataset.flyoutTarget !== undefined && target.dataset.flyoutTarget !== '') {
                flyout = document.getElementById(target.dataset.flyoutTarget);
            } else {
                flyout = target.previousElementSibling;
            }

            _this.close(flyout);
        }

        onRendered(e) {
            let layout = document.getElementById('layout');

            if (layout.dataset !== undefined && layout.dataset.flyoutsOpen !== undefined && layout.dataset.flyoutsOpen !== '') {
                let openFlyouts = document.querySelectorAll(layout.dataset.flyoutsOpen);

                openFlyouts.forEach(function(el) {
                    el.classList.add('flyout-open');
                });
            }
        }

        onControlClick(e) {
            let _this = e.data.self,
                flyout;
            var target = e.target.closest('[data-flyout-target]') || e.target.closest('button') || e.target;

            if (target.dataset !== undefined && target.dataset.flyoutTarget !== undefined && target.dataset.flyoutTarget !== '') {
                flyout = document.getElementById(target.dataset.flyoutTarget);
            } else {
                flyout = target.previousElementSibling;
            }

            if (flyout.classList.contains('flyout-open')) {
                _this.close(flyout);
            } else {
                _this.open(flyout);
            }
        }

        onMenuItemClick(e) {
            e.preventDefault();
            let _this = e.data.self,
                flyout = e.target.closest('.flyout');

            _this.close(flyout);
        }

        close(flyout) {
            let layout = document.getElementById('layout');

            flyout.classList.remove('flyout-open');

            if (layout.dataset !== undefined && layout.dataset.flyoutsOpen !== undefined) {
                let openFlyoutsSelectors = layout.dataset.flyoutsOpen.split(',');
                openFlyoutsSelectors.splice(openFlyoutsSelectors.indexOf(flyout.id));

                layout.dataset.flyoutsOpen = openFlyoutsSelectors.join(',');
            }
        }

        open = (flyout) => {
            let layout = document.getElementById('layout');
            var _this = this;

            document.querySelectorAll('.flyout-open').forEach((el) => {
                _this.close(el);
            });

            flyout.classList.add('flyout-open');

            if (layout.dataset.flyoutsOpen === undefined || layout.dataset.flyoutsOpen === '') {
                layout.dataset.flyoutsOpen = '#' + flyout.id;
            } else {
                if (layout.dataset.flyoutsOpen.split(',').indexOf(flyout.id) !== -1) {
                    layout.dataset.flyoutsOpen += ',#' + flyout.id;
                }
            }
        }

        /**
         * Hide, config flyout when "Enter" key is pressed to follow `.flyout` nav item link
         *
         * @param {Object} e Event
         */
        onKeyDown = function(e) {
            var _this = e.data.self;

            if (e.key == 'Enter' && $(document.activeElement).is('.flyout a')) {
                _this.hideConfigFlyout(e);
            }
        }
    }

    Icinga.Behaviors.FlyoutMenu = FlyoutMenu;

})(Icinga);
