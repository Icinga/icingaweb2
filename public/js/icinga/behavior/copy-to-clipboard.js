(function (Icinga) {

    "use strict";

    class CopyToClipboard extends Icinga.EventListener {
        constructor(icinga)
        {
            super(icinga);

            try {
                this.CopyToClipboard = require('icinga/icinga-php-library/widget/CopyToClipboard');
            } catch (e) {
                console.warn('Unable to provide copy to clipboard feature. Libraries not available:', e);
                return;
            }

            this.on('rendered', '#main > .container', this.onRendered, this);
        }

        onRendered(event)
        {
            let _this = event.data.self;

            event.currentTarget.querySelectorAll('[data-icinga-clipboard]').forEach(button => {
                new _this.CopyToClipboard(button);
            });
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.CopyToClipboard = CopyToClipboard;
})(Icinga);