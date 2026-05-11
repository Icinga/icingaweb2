// SPDX-FileCopyrightText: 2023 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

(function (Icinga) {

    "use strict";

    try {
        var CopyToClipboard = require('icinga/icinga-php-library/widget/CopyToClipboard');
    } catch (e) {
        console.warn('Unable to provide copy to clipboard feature. Libraries not available:', e);
        return;
    }

    class CopyToClipboardBehavior extends Icinga.EventListener {
        constructor(icinga)
        {
            super(icinga);

            this.on('rendered', '#main > .container, #layout', this.onRendered, this);

            /**
             * Clipboard buttons
             *
             * @type {WeakMap<object, CopyToClipboard>}
             * @private
             */
            this._clipboards = new WeakMap();
        }

        onRendered(event)
        {
            if (event.currentTarget !== event.target) {
                return;
            }

            let _this = event.data.self;

            event.currentTarget.querySelectorAll('[data-icinga-clipboard]').forEach(button => {
                _this._clipboards.set(button, new CopyToClipboard(button));
            });
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.CopyToClipboardBehavior = CopyToClipboardBehavior;
})(Icinga);
