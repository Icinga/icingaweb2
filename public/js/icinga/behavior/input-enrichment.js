/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

/**
 * InputEnrichment - Behavior for forms with enriched inputs
 */
(function(Icinga) {

    "use strict";

    try {
        var SearchBar = require('icinga/ipl/widget/SearchBar');
        var SearchEditor = require('icinga/ipl/widget/SearchEditor');
        var FilterInput = require('icinga/ipl/widget/FilterInput');
        var TermInput = require('icinga/ipl/widget/TermInput');
        var Completer = require('icinga/ipl/widget/Completer');
    } catch (e) {
        console.warn('Unable to provide input enrichments. Libraries not available:', e);
        return;
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * @param icinga
     * @constructor
     */
    var InputEnrichment = function (icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('beforerender', this.onBeforeRender, this);
        this.on('rendered', this.onRendered, this);

        /**
         * Enriched inputs
         *
         * @type {WeakMap<object, SearchEditor|SearchBar|FilterInput|TermInput|Completer>}
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
    InputEnrichment.prototype = new Icinga.EventListener();

    /**
     * @param data
     */
    InputEnrichment.prototype.update = function (data) {
        var input = document.querySelector(data[0]);
        if (input !== null && this._enrichments.has(input)) {
            this._enrichments.get(input).updateTerms(data[1]);
        }
    };

    /**
     * @param event
     * @param content
     * @param action
     * @param autorefresh
     * @param scripted
     */
    InputEnrichment.prototype.onBeforeRender = function (event, content, action, autorefresh, scripted) {
        if (! autorefresh) {
            return;
        }

        let _this = event.data.self;
        let inputs = event.target.querySelectorAll('[data-enrichment-type]');

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
    InputEnrichment.prototype.onRendered = function (event, autorefresh, scripted) {
        let _this = event.data.self;
        let container = event.target;

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
        var inputs = container.querySelectorAll('[data-enrichment-type]');
        inputs.forEach(function (input) {
            var enrichment = _this._enrichments.get(input);
            if (! enrichment) {
                switch (input.dataset.enrichmentType) {
                    case 'search-bar':
                        enrichment = (new SearchBar(input)).bind();
                        break;
                    case 'search-editor':
                        enrichment = (new SearchEditor(input)).bind();
                        break;
                    case 'filter':
                        enrichment = (new FilterInput(input)).bind();
                        enrichment.restoreTerms();

                        if (_this._enrichments.has(input.form)) {
                            _this._enrichments.get(input.form).setFilterInput(enrichment);
                        }

                        break;
                    case 'terms':
                        enrichment = (new TermInput(input)).bind();
                        enrichment.restoreTerms();
                        break;
                    case 'completion':
                        enrichment = (new Completer(input)).bind();
                }

                _this._enrichments.set(input, enrichment);
            }
        });
    };

    Icinga.Behaviors.InputEnrichment = InputEnrichment;

})(Icinga);
