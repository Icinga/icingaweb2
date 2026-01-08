/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

(function (Icinga) {

    'use strict';

    class RelativeTime extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this.formatter = new Intl.RelativeTimeFormat(
                [icinga.config.locale, 'en'],
                {style: 'narrow'}
            );

            this.on('rendered', '#main > .container, #modal-content', this.onRendered, this);
            this.on('close-column', this.stop, this);
            this.on('close-modal', this.stop, this);
        }

        onRendered(event) {
            let _this = event.data.self;
            const root = event.currentTarget || event.target;
            const hasRelativeTime =
                (root.matches && root.matches('time[data-relative-time]')) ||
                root.querySelector('time[data-relative-time]');

            if (!hasRelativeTime) {
                return;
            }

            if (_this._relativeTimeTimerRegistered) {
                _this.update(root);

                return;
            }
            _this._relativeTimeTimerRegistered = true;

            _this.update();
            _this.icinga.timer.register(_this.update, _this, 1000);
        }

        update(root = document) {
            const now = Date.now();
            const ONE_HOUR_SEC = 60 * 60;

            const getDatetimeMs = (el) => {
                const dt = el.dateTime || el.getAttribute('datetime');
                if (!dt) {
                    return NaN;
                }

                return Date.parse(dt);
            };

            root.querySelectorAll('time[data-relative-time="ago"], time[data-relative-time="since"]')
                .forEach((el) => {
                    const mode = el.dataset.relativeTime;

                    const ts = getDatetimeMs(el);
                    if (!Number.isFinite(ts)) {
                        return;
                    }

                    let diffSec = Math.floor((now - ts) / 1000);
                    if (diffSec < 0) {
                        diffSec = 0;
                    }

                    if (diffSec >= ONE_HOUR_SEC) {
                        return;
                    }

                    const minute = Math.floor(diffSec / 60);
                    const second = diffSec % 60;

                    el.innerHTML = this.render(minute, second, mode);
                });

            root.querySelectorAll('time[data-relative-time="until"]')
                .forEach((el) => {
                    const ts = getDatetimeMs(el);
                    if (!Number.isFinite(ts)) {
                        return;
                    }

                    const remainingSec = Math.ceil((ts - now) / 1000);

                    if (Math.abs(remainingSec) >= ONE_HOUR_SEC) {
                        return;
                    }

                    if (remainingSec === 0 && el.dataset.agoLabel) {
                        el.innerText = el.dataset.agoLabel;
                        el.dataset.relativeTime = 'ago';

                        return;
                    }

                    let invert = '';
                    let absSec = remainingSec;

                    if (remainingSec < 0) {
                        invert = '-';
                        absSec = -remainingSec;
                    }

                    const minute = Math.floor(absSec / 60);
                    const second = absSec % 60;

                    el.innerHTML = this.render(minute, second, 'until', invert);
                });
        }

        render(minute, second, mode, invert = '') {
            const sign = mode === 'ago' || mode === 'since' ? -1 : 1;

            let min = minute * sign;
            let sec = second * sign;


            const minutes = this.formatter.formatToParts(min, "minute");
            const seconds = this.formatter.formatToParts(sec, "second");
            let isPrefix = true;
            let prefix = '', suffix = '';
            for (let i = 0; i < seconds.length; i++) {
                if (seconds[i].type === "integer") {
                    if (i === 0) {
                        isPrefix = false;
                    }
                    continue;
                }

                if (seconds[i].value === minutes[i].value) {
                    if (isPrefix) {
                        prefix = seconds[i].value;
                    } else {
                        suffix = seconds[i].value;
                    }
                    break;
                }

                const a = String(seconds[i].value);
                const b = String(minutes[i].value);
                const maxLen = Math.min(a.length, b.length);

                // helper: longest common prefix
                const lcp = () => {
                    let common = "";
                    for (let k = 1; k <= maxLen; k++) {
                        const cand = a.slice(0, k);
                        if (b.startsWith(cand)) {
                            common = cand;
                        } else {
                            break;
                        }
                    }
                    return common;
                };

                // helper: longest common suffix
                const lcs = () => {
                    let common = "";
                    for (let k = 1; k <= maxLen; k++) {
                        const cand = a.slice(-k);
                        if (b.endsWith(cand)) {
                            common = cand;
                        } else {
                            break;
                        }
                    }
                    return common;
                };

                if (isPrefix) {
                    const common = lcp();
                    if (common && common.trim().length) {
                        prefix = common;
                    }
                } else {
                    const common = lcs();
                    if (common && common.trim().length) {
                        suffix = common;
                    }
                }
            }

            return  prefix + minute + 'm ' + second + 's ' + suffix;
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.RelativeTime = RelativeTime;

})(Icinga);
