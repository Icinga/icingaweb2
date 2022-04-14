/*! Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

;(function (Icinga, $) {

    'use strict';

    /**
     * Behavior for the enhanced Icinga Web 2 dashboards
     *
     * @param {Icinga} icinga The current Icinga Object
     */
    class Dashboard extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            try {
                this.Sortable = require('icinga/icinga-php-library/vendor/Sortable');
            } catch (e) {
                console.warn('Unable to provide Sortable. Library not available:', e);
                return;
            }

            /**
             * Possible type of widgets this behavior is being applied to
             *
             * @type {object}
             */
            this.widgetTypes = { Dashlet : 'Dashlets', Dashboard : 'Dashboards', DashboardHome : 'Homes' };

            this.on('rendered', '#main > .container', this.onRendered, this);
            // Registers the drop event for all the widget types
            this.on('end', '.dashboard-settings', this.elementDropped, this);
        }

        /**
         * Get the widget type of the given element
         *
         * @param {HTMLElement} target
         *
         * @returns {null|string}
         */
        getTypeFor(target) {
            if (target.matches('.dashboard-settings')) {
                if (! target.querySelector('.home-list-control:first-child')) {
                    return this.widgetTypes.Dashboard;
                }

                return this.widgetTypes.DashboardHome;
            } else if (target.matches('.dashboard-item-list')) {
                return this.widgetTypes.Dashboard;
            } else if (target.matches('.dashlet-item-list')) {
                return this.widgetTypes.Dashlet;
            }

            return null;
        }

        /**
         * Set up a request with the reordered widget and post the data to the controller
         *
         * @param event
         *
         * @returns {boolean}
         */
        elementDropped(event) {
            let _this = event.data.self,
                orgEvt = event.originalEvent,
                data = {};

            if (orgEvt.to === orgEvt.from && orgEvt.newIndex === orgEvt.oldIndex) {
                return false;
            }

            let item = orgEvt.item;
            switch (_this.getTypeFor(orgEvt.to)) {
                case _this.widgetTypes.DashboardHome: {
                    let home = item.dataset.icingaHome;
                    data[home] = orgEvt.newIndex;
                    break;
                }
                case _this.widgetTypes.Dashboard: {
                    let pane = item.dataset.icingaPane,
                        home = orgEvt.to.closest('.home-list-control, .dashboard-settings').dataset.icingaHome;
                    if (orgEvt.to !== orgEvt.from) {
                        let homeList = orgEvt.from.closest('.home-list-control, .dashboard-settings');
                        data.originals = {
                            originalHome : homeList.dataset.icingaHome,
                            originalPane : pane
                        };
                    }

                    data[home] = { [pane] : orgEvt.newIndex };
                    break;
                }
                case _this.widgetTypes.Dashlet: {
                    let dashlet = item.dataset.icingaDashlet;

                    let parent = orgEvt.to.closest('.dashboard-list-control');
                    let pane = parent.dataset.icingaPane;
                    // If there is only default home in the dashboard manager view, there won't be rendered a
                    // ".home-list-control", so we need to look for an alternative
                    let home = parent.closest('.home-list-control, .dashboard-settings').dataset.icingaHome;

                    if (orgEvt.to !== orgEvt.from) {
                        let parent = orgEvt.from.closest('.dashboard-list-control');
                        let orgHome = parent.closest('.home-list-control, .dashboard-settings').dataset.icingaHome;
                        data.originals = {
                            originalHome : orgHome,
                            originalPane : parent.dataset.icingaPane
                        }
                    }

                    dashlet = { [dashlet] : orgEvt.newIndex };
                    data[home] = { [pane] : dashlet };
                }
            }

            if (Object.keys(data).length) {
                if (! data.originals) {
                    data.originals = null;
                }

                data = { dashboardData : JSON.stringify(data) };
                let url = _this.icinga.config.baseUrl + '/dashboards/reorder-widgets';
                let req = _this.icinga.loader.loadUrl(url, $('#col1'), data, 'post');

                req.addToHistory = false;
                req.scripted = true;
            }
        }

        onRendered(e) {
            let _this = e.data.self;
            e.target.querySelectorAll('.dashboard-settings, .dashboard-item-list, .dashlet-item-list')
                .forEach(sortable => {
                    let groupName = _this.getTypeFor(sortable),
                        draggable;

                    switch (groupName) {
                        case _this.widgetTypes.DashboardHome:
                            groupName = _this.widgetTypes.DashboardHome;
                            draggable = '.home-list-control';
                            break;
                        case _this.widgetTypes.Dashboard:
                            groupName = _this.widgetTypes.Dashboard;
                            draggable = '.dashboard-list-control';
                            break;
                        case _this.widgetTypes.Dashlet:
                            groupName = _this.widgetTypes.Dashlet;
                            draggable = '.dashlet-list-item';
                    }

                    let options = {
                        scroll      : true,
                        invertSwap  : true,
                        dataIdAttr  : 'id',
                        direction   : 'vertical',
                        draggable   : draggable,
                        handle      : 'h1 > .widget-drag-initiator',
                        group       : { name : groupName },
                        chosenClass : 'draggable-widget-chosen'
                    };

                    _this.Sortable.create(sortable, options);
                });
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.Dashboard = Dashboard;

})(Icinga, jQuery);
