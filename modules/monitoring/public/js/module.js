/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

(function(Icinga) {

    var Monitoring = function(module) {
        /**
         * The Icinga.Module instance
         */
        this.module = module;

        /**
         * The observer used to handle the timeline's infinite loading
         */
        this.scrollCheckTimer = null;

        /**
         * Whether to skip the timeline's scroll-check
         */
        this.skipScrollCheck = false;

        this.initialize();
    };

    Monitoring.prototype = {

        initialize: function()
        {
            this.module.on('rendered', this.enableScrollCheck);
            this.module.icinga.logger.debug('Monitoring module loaded');
        },

        /**
         * Enable the timeline's scroll-check
         */
        enableScrollCheck: function()
        {
            /**
             * Re-enable the scroll-check in case the timeline has just been extended
             */
            if (this.skipScrollCheck) {
                this.skipScrollCheck = false;
            }

            /**
             * Prepare the timer to handle the timeline's infinite loading
             */
            var $timeline = $('div.timeline');
            if ($timeline.length && !$timeline.closest('.dashboard').length) {
                if (this.scrollCheckTimer === null) {
                    this.scrollCheckTimer = this.module.icinga.timer.register(
                        this.checkTimelinePosition,
                        this,
                        800
                    );
                    this.module.icinga.logger.debug('Enabled timeline scroll-check');
                }
            }
        },

        /**
         * Check whether the user scrolled to the end of the timeline
         */
        checkTimelinePosition: function()
        {
            if (!$('div.timeline').length) {
                this.module.icinga.timer.unregister(this.scrollCheckTimer);
                this.scrollCheckTimer = null;
                this.module.icinga.logger.debug('Disabled timeline scroll-check');
            } else if (!this.skipScrollCheck && this.module.icinga.utils.isVisible('#end')) {
                this.skipScrollCheck = true;
                this.module.icinga.loader.loadUrl(
                    $('#end').remove().attr('href'),
                    $('div.timeline'),
                    undefined,
                    undefined,
                    'append'
                ).addToHistory = false;
            }
        }
    };

    Icinga.availableModules.monitoring = Monitoring;

}(Icinga));
