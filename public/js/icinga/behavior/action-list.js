(function (Icinga) {

    "use strict";

    try {
        var ActionList = require('icinga/icinga-php-library/widget/ActionList');
    } catch (e) {
        console.warn('Unable to provide ActionList feature. Libraries not available:', e);
        return;
    }
    class ActionListBehavior extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this.on('beforerender', '#main > .container', this.onBeforeRender, this);
            this.on('rendered', '#main > .container', this.onRendered, this);
            this.on('close-column', '#main > #col2', this.onColumnClose, this);
            this.on('column-moved', this.onColumnMoved, this);
            this.on('selection-start', this.onSelectionStart, this);
            this.on('selection-end', this.onSelectionEnd, this);
            this.on('all-deselected', this.allDeselected, this);

            /**
             * Action lists
             *
             * @type {WeakMap<object, ActionList>}
             * @private
             */
            this._actionLists = new WeakMap();

            /**
             * Cached action lists
             *
             * Holds values only during the time between `beforerender` and `rendered`
             *
             * @type {{}}
             * @private
             */
            this._cachedActionLists = {};
        }

        /**
         * @param event
         * @param content
         * @param action
         * @param autorefresh
         * @param scripted
         */
        onBeforeRender(event, content, action, autorefresh, scripted) {
            if (! autorefresh) {
                return;
            }

            let _this = event.data.self;
            let lists = _this.getActionLists(event.target)

            // Remember current instances
            lists.forEach((list) => {
                let actionList = _this._actionLists.get(list);
                if (actionList) {
                    _this._cachedActionLists[_this.icinga.utils.getDomPath(list).join(' > ')] = actionList;
                }
            });
        }

        /**
         * @param event
         * @param autorefresh
         * @param scripted
         */
        onRendered(event, autorefresh, scripted) {
            let _this = event.data.self;
            let container = event.target;
            let detailUrl = _this.getDetailUrl();

            if (autorefresh) {
                // Apply remembered instances
                for (let listPath in _this._cachedActionLists) {
                    let actionList = _this._cachedActionLists[listPath];
                    let list = container.querySelector(listPath);
                    if (list !== null) {
                        actionList.refresh(list, detailUrl);
                        _this._actionLists.set(list, actionList);
                    } else {
                        actionList.destroy();
                    }

                    delete _this._cachedActionLists[listPath];
                }
            }

            let lists = _this.getActionLists(event.currentTarget);
            lists.forEach(list => {
                let actionList = _this._actionLists.get(list);
                if (! actionList) {
                    let isPrimary = list.parentElement.matches('#main > #col1 > .content');
                    actionList = (new ActionList(list, isPrimary)).bind();
                    actionList.load(detailUrl);

                    _this._actionLists.set(list, actionList);
                } else {
                    actionList.load(detailUrl); // navigated back to the same page
                }
            });

            if (event.target.id === 'col2') { // navigated back/forward and the detail url is changed
                let lists = _this.getActionLists();
                lists.forEach(list => {
                    let actionList = _this._actionLists.get(list);

                    if (actionList) {
                        actionList.load(detailUrl);
                    }
                });
            }
        }

        onColumnClose(event)
        {
            let _this = event.data.self;
            let lists = _this.getActionLists();
            lists.forEach((list) => {
                let actionList = _this._actionLists.get(list);
                if (actionList) {
                    actionList.load();
                }
            });
        }

        /**
         * Triggers when column is moved to left or right
         *
         * @param event
         * @param sourceId The content is moved from
         */
        onColumnMoved(event, sourceId) {
            if (event.target.id === 'col2' && sourceId === 'col1') { // only for browser-back (col1 shifted to col2)
                let _this = event.data.self;
                let lists = _this.getActionLists(event.target);
                lists.forEach((list) => {
                    let actionList = _this._actionLists.get(list);
                    if (actionList) {
                        actionList.load();
                    }
                });
            }
        }

        /**
         * Selection started and in process
         *
         * @param event
         */
        onSelectionStart(event) {
            const container = event.target.closest('.container');
            container.dataset.suspendAutorefresh = '';
        }

        /**
         * Triggers when selection ends, the url can be loaded now
         * @param event
         */
        onSelectionEnd(event) {
            let _this = event.data.self;

            let req = _this.icinga.loader.loadUrl(
                event.detail.url,
                _this.icinga.loader.getLinkTargetFor($(event.target.firstChild))
            );

            req.always((_, __, errorThrown) => {

                if (errorThrown !== 'abort') {
                    delete event.target.closest('.container').dataset.suspendAutorefresh;
                    event.detail.actionList.setProcessing(false);
                }
            });
        }

        allDeselected(event) {
            let _this = event.data.self;
            if (_this.icinga.loader.getLinkTargetFor($(event.target), false).attr('id') === 'col2') {
                _this.icinga.ui.layout1col();
                _this.icinga.history.pushCurrentState();
                delete event.target.closest('.container').dataset.suspendAutorefresh;
            }
        }

        getDetailUrl() {
            return this.icinga.utils.parseUrl(
                this.icinga.history.getCol2State().replace(/^#!/, '')
            );
        }

        /**
         * Get action lists from the given element
         *
         * If element is not provided, all action lists from col1 will be returned
         *
         * @param element
         *
         * @return NodeList
         */
        getActionLists(element = null) {
            if (element === null) {
                return document.querySelectorAll('#col1 [data-interactable-action-list]');
            }

            return element.querySelectorAll('[data-interactable-action-list]');
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.ActionListBehavior = ActionListBehavior;
})(Icinga);