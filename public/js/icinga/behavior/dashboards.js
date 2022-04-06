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
            this.on('end', '.dashboard-settings, .dashboard-list-control, .dashlet-list-item', this.elementDropped, this);
            // This is for the normal dashboard/dashlets view
            this.on('end', '.dashboard.content', this.elementDropped, this);
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
                return this.widgetTypes.DashboardHome;
            } else if (target.matches('.dashboard-item-list')) {
                return this.widgetTypes.Dashboard;
            } else if (target.matches('.dashlet-item-list') || target.matches('.dashboard.content')) {
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
                        home = orgEvt.to.closest('.home-list-control').dataset.icingaHome;
                    if (orgEvt.to !== orgEvt.from) {
                        data.originals = {
                            originalHome : orgEvt.from.closest('.home-list-control').dataset.icingaHome,
                            originalPane : pane
                        };
                    }

                    data[home] = { [pane] : orgEvt.newIndex };
                    break;
                }
                case _this.widgetTypes.Dashlet: {
                    let dashlet = item.dataset.icingaDashlet,
                        pane,
                        home;

                    if (orgEvt.to.matches('.dashboard.content')) {
                        let parentData = orgEvt.to.dataset.icingaPane.split('|', 2);
                        home = parentData.shift();
                        pane = parentData.shift();

                        data.redirectPath = 'dashboards';
                    } else { // Dashboard manager view
                        let parent = orgEvt.to.closest('.dashboard-list-control');
                        pane = parent.dataset.icingaPane;
                        // If there is only default home in the dashboard manager view, there won't be rendered
                        // ".home-list-control", so we need to look for an alternative
                        home = parent.closest('.home-list-control, .dashboard-settings').dataset.icingaHome;

                        if (orgEvt.to !== orgEvt.from) {
                            let parent = orgEvt.from.closest('.dashboard-list-control');
                            data.originals = {
                                originalHome : parent.closest('.home-list-control').dataset.icingaHome,
                                originalPane : parent.dataset.icingaPane
                            }
                        }
                    }

                    dashlet = { [dashlet] : orgEvt.newIndex };
                    data[home] = { [pane] : dashlet };
                }
            }

            if (Object.keys(data).length) {
                data.Type = _this.getTypeFor(orgEvt.to);
                if (! data.originals) {
                    data.originals = null;
                }

                if (! data.redirectPath) {
                    data.redirectPath = 'dashboards/settings';
                }

                data = { dashboardData : JSON.stringify(data) };
                let url = _this.icinga.config.baseUrl + '/dashboards/reorder-widgets';
                _this.icinga.loader.loadUrl(url, $('#col1'), data, 'post');
            }
        }

        onRendered(e) {
            let _this = e.data.self;
            e.target.querySelectorAll('.dashboard-settings, .dashboard.content,'
                + ' .dashboard-item-list, .dashlet-item-list')
                .forEach(sortable => {
                    let groupName = _this.getTypeFor(sortable),
                        draggable,
                        handle;

                    switch (groupName) {
                        case _this.widgetTypes.DashboardHome:
                            groupName = _this.widgetTypes.DashboardHome;
                            draggable = '.home-list-control';
                            handle = '.home-list-control > h1';
                            break;
                        case _this.widgetTypes.Dashboard:
                            groupName = _this.widgetTypes.Dashboard;
                            draggable = '.dashboard-list-control';
                            handle = '.dashboard-list-control > h1'
                            break;
                        case _this.widgetTypes.Dashlet:
                            groupName = _this.widgetTypes.Dashlet;
                            if (sortable.matches('.dashboard.content')) {
                                draggable = '> .container';
                            } else {
                                draggable = '.dashlet-list-item';
                            }

                            handle = draggable;
                    }

                    let options = {
                        scroll     : true,
                        invertSwap : true,
                        delay      : 100,
                        dataIdAttr : 'id',
                        direction  : 'vertical',
                        draggable  : draggable,
                        handle     : handle,
                        group      : { name : groupName }
                    };

                    _this.Sortable.create(sortable, options);
                });
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.Dashboard = Dashboard;

})(Icinga, jQuery);
