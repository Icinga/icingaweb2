/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

/**
 * Complete - Behavior for forms with auto-completion of terms
 */
(function(Icinga) {

    "use strict";

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * @param icinga
     * @constructor
     */
    var Complete = function (icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('beforerender', '.container', this.onBeforeRender, this);
        this.on('rendered', '.container', this.onRendered, this);

        /**
         * Enriched inputs
         *
         * @type {WeakMap<object, FilterInput>}
         * @private
         */
        this._enrichments = new WeakMap();

        /**
         * Cached enrichments
         *
         * Holds values only during the time between `beforerender` and `rendered`
         *
         * @type {{}}
         * @private
         */
        this._cachedEnrichments = {};
    };
    Complete.prototype = new Icinga.EventListener();

    /**
     * @param event
     * @param content
     * @param action
     * @param autorefresh
     * @param scripted
     */
    Complete.prototype.onBeforeRender = function (event, content, action, autorefresh, scripted) {
        if (! autorefresh) {
            return;
        }

        let _this = event.data.self;
        let inputs = event.currentTarget.querySelectorAll('input[data-term-completion]');

        // Remember current instances
        inputs.forEach(function (input) {
            var enrichment = _this._enrichments.get(input);
            if (enrichment) {
                _this._cachedEnrichments[_this.icinga.utils.getDomPath(input).join(' ')] = enrichment;
            }
        });
    };

    /**
     * @param event
     * @param autorefresh
     * @param scripted
     */
    Complete.prototype.onRendered = function (event, autorefresh, scripted) {
        let _this = event.data.self;
        let container = event.currentTarget;

        if (autorefresh) {
            // Apply remembered instances
            for (var inputPath in _this._cachedEnrichments) {
                var enrichment = _this._cachedEnrichments[inputPath];
                var input = container.querySelector(inputPath);
                if (input !== null) {
                    enrichment.refresh(input);
                    _this._enrichments.set(input, enrichment);
                } else {
                    enrichment.destroy();
                }

                delete _this._cachedEnrichments[inputPath];
            }
        }

        // Create new instances
        var inputs = container.querySelectorAll('input[data-term-completion]');
        inputs.forEach(function (input) {
            var enrichment = _this._enrichments.get(input);
            if (! enrichment) {
                enrichment = (new FilterInput(input)).bind();
                enrichment.restoreTerms();

                _this._enrichments.set(input, enrichment);
            }
        });
    };

    Icinga.Behaviors.Complete = Complete;

})(Icinga);
