/*global Icinga:false, document: false, define:false require:false base_url:false console:false */

define(['jquery','logging'], function($,log) {
    "use strict";

    return function() {
        this.count = 10;
        this.offset = 0;
        this.searchable = false;

        var construct = function(el) {
            this.el = $(el);
            this.count = this.el.attr("count") || this.count;
            this.searchable = this.el.attr("searchable") || false;
            this.render();
        };

        var renderQuicksearch = (function() {
            this.input = $("<input type='text' style='padding:0px;font-size:9pt;padding-left:1em;margin-bottom:2px;line-height:8px' class='search-query input-small pull-right' >");

            $('.expand-title',this.el.parents('.expandable').first())
                .append(this.input)
                .append($("<i class='icon-search pull-right'></i>"));

            this.input.on("keyup",this.updateVisible.bind(this));
        }).bind(this);

        this.updateVisible = function() {
            var filter = this.input.val();
            $("tbody tr",this.el).hide();
            $("td",this.el).each(function() {
                if($(this).text().match(filter)) {
                    $(this).parent("tbody tr").show();
                }
            });
        };

        this.render = function() {
            renderQuicksearch();
        };

        construct.apply(this,arguments);
    };
});