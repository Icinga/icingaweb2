// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

(function(Icinga, $) {

    "use strict";

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Sparkline = function (icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('rendered', this.onRendered, this);
    };
    Sparkline.prototype = new Icinga.EventListener();

    Sparkline.prototype.onRendered = function(evt) {
        var el = evt.target;

        $('span.sparkline', el).each(function(i, element) {
            // read custom options
            var $spark            = $(element);
            var labels            = $spark.attr('labels').split('|');
            var formatted         = $spark.attr('formatted').split('|');
            var title             = $spark.attr('title');
            var format            = $spark.attr('tooltipFormat');

            if ($spark.attr('labels')) {
                $spark.removeAttr('original-title');
            }
            var options = {
                enableTagOptions: true,
                width: $spark.attr('sparkWidth') || 12,
                height: $spark.attr('sparkHeight') || 12,
                tooltipFormatter: function (sparkline, options, fields) {
                    var out       = format;
                    var replace   = {
                        title:     title,
                        label:     labels[fields.offset] ? labels[fields.offset] : fields.offset,
                        formatted: formatted[fields.offset] ? formatted[fields.offset] : '',
                        value:     fields.value,
                        percent:   Math.round(fields.percent * 100) / 100
                    };
                    $.each(replace, function(key, value) {
                        out = out.replace('{{' + key + '}}', value);
                    });
                    return out;
                }
            };
            $spark.sparkline('html', options);
        });
    };

    Icinga.Behaviors.Sparkline = Sparkline;

}) (Icinga, jQuery);
