(function (Icinga) {

    "use strict";

    try {
        var LoadMore = require('icinga/icinga-php-library/widget/LoadMore');
    } catch (e) {
        console.warn('Unable to provide LoadMore feature. Libraries not available:', e);
        return;
    }
    class LoadMoreBehavior extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this.on('rendered', '#main > .container', this.onRendered, this);
            this.on('load', this.onLoad, this);

            /**
             * Load More elements
             *
             * @type {WeakMap<object, LoadMore>}
             * @private
             */
            this._loadMoreElements = new WeakMap();
        }

        /**
         * @param event
         */
        onRendered(event)
        {
            let _this = event.data.self;

            event.currentTarget.querySelectorAll('.load-more').forEach(element => {
                _this._loadMoreElements.set(element, new LoadMore(element));
            });
        }

        onLoad(event) {
            let _this = event.data.self;
            let anchor = event.target;
            let showMore = anchor.parentElement;
            var progressTimer = _this.icinga.timer.register(function () {
                var label = anchor.innerText;

                var dots = label.substr(-3);
                if (dots.slice(0, 1) !== '.') {
                    dots = '.  ';
                } else {
                    label = label.slice(0, -3);
                    if (dots === '...') {
                        dots = '.  ';
                    } else if (dots === '.. ') {
                        dots = '...';
                    } else if (dots === '.  ') {
                        dots = '.. ';
                    }
                }

                anchor.innerText = label + dots;
            }, null, 250);

            let url = anchor.getAttribute('href');

            let req = _this.icinga.loader.loadUrl(
                // Add showCompact, we don't want controls in paged results
                _this.icinga.utils.addUrlFlag(url, 'showCompact'),
                $(showMore.parentElement),
                undefined,
                undefined,
                'append',
                false,
                progressTimer
            );
            req.addToHistory = false;
            req.done(function () {
                showMore.remove();

                // Set data-icinga-url to make it available for Icinga.History.getCurrentState()
                req.$target.closest('.container').data('icingaUrl', url);

                _this.icinga.history.replaceCurrentState();
            });

            return req;
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.LoadMoreBehavior = LoadMoreBehavior;
})(Icinga);
