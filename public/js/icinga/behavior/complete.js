/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

/**
 * Complete - Behavior for forms with auto-completion of terms
 */
(function(Icinga, $) {

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
         * Cached completions
         *
         * Holds values only during the time between `beforerender` and `rendered`
         *
         * @type {{}}
         */
        this.cachedCompletions = {};
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
        var _this = event.data.self;

        var $elements = $('input[data-term-completion]', event.currentTarget);

        // Remember current instances
        $elements.each(function () {
            var $input = $(this),
                completion = $input.data('completion');
            if (completion) {
                if (! completion.keepUsedTerms) {
                    completion.keepUsedTerms = autorefresh;
                }

                _this.cachedCompletions[_this.icinga.utils.getDomPath($input[0]).join(' ')] = completion;
            }
        });
    };

    /**
     * @param event
     * @param autorefresh
     * @param scripted
     */
    Complete.prototype.onRendered = function (event, autorefresh, scripted) {
        var _this = event.data.self;

        // Apply remembered instances
        $.each(_this.cachedCompletions, function (inputPath) {
            var $input = $(inputPath);
            if ($input.length) {
                this.refresh($input[0]);
            } else {
                this.destroy();
            }

            delete _this.cachedCompletions[inputPath];
        });

        var $elements = $('input[data-term-completion]', event.currentTarget);

        // Create new instances
        $elements.each(function() {
            var $input = $(this);
            if (! $input.data('completion')) {
                (new Completion(_this.icinga, this)).bind().restoreTerms();
            }
        });
    };

    Icinga.Behaviors.Complete = Complete;

})(Icinga, jQuery);
