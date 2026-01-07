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

            _this.update();
            _this.icinga.timer.register(_this.update, _this, 1000);
        }

        update() {
            const now = Date.now();
            const elements = document.querySelectorAll('time[data-relative-time]');

            document.querySelectorAll('time[data-relative-time="ago"], time[data-relative-time="since"]')
                .forEach(el => {
                    let partialTime = /(\d{1,2})m (\d{1,2})s/.exec(el.innerHTML);
                    let mode = el.dataset.relativeTime;

                    if (partialTime !== null) {
                        var minute = parseInt(partialTime[1], 10),
                            second = parseInt(partialTime[2], 10);
                        if (second < 59) {
                            ++second;
                        } else {
                            ++minute;
                            second = 0;
                        }

                        el.innerHTML = this.render(minute, second, mode);
                    }
                });

            document.querySelectorAll('time[data-relative-time="until"]')
                .forEach(el => {
                    var partialTime = /(-?)(\d{1,2})m (\d{1,2})s/.exec(el.innerHTML);
                    if (partialTime !== null) {
                        var minute = parseInt(partialTime[2], 10),
                            second = parseInt(partialTime[3], 10),
                            invert = partialTime[1];
                        if (invert.length) {
                            // Count up because partial time is negative
                            if (second < 59) {
                                ++second;
                            } else {
                                ++minute;
                                second = 0;
                            }
                        } else {
                            // Count down because partial time is positive
                            if (second === 0) {
                                if (minute === 0) {
                                    // Invert counter
                                    minute = 0;
                                    second = 1;
                                    invert = '-';
                                } else {
                                    --minute;
                                    second = 59;
                                }
                            } else {
                                --second;
                            }

                            if (minute === 0 && second === 0 && el.dataset.relativeTime === 'until' && el.dataset.agoLabel) {
                                el.innerText = el.dataset.agoLabel;
                                el.removeAttribute('data-relative-time');
                                el.dataset.relativeTime = 'ago';

                                return;
                            }
                        }

                        el.innerHTML = this.render(minute, second, 'until', invert);
                    }
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
