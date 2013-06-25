/*global Icinga:false, document: false, define:false require:false base_url:false console:false */
(function() {
    "use strict";

    var containerMgrInstance = null;
    var async;

    var ContainerMgr = function($,log,Widgets,SubTable,holder) {


        var enhanceDetachLinks = function() {
            $('a[target=_blank]',this).each(function() {
                $(this).attr("target","popup");
            });
        };
        /**
         * Loading Async directly via AMD would result in a circular dependency and return null
         * @param asyncMgr
         */
        this.registerAsyncMgr = function(asyncMgr) {
            async = asyncMgr;
        };

        this.updateContainer = function(id,content,req) {
            var target = id;
            if (typeof id === "string") {
                target = $('div[container-id='+id+']');
            }
            var ctrl = $('.container-controls',target);
            target.html(content);
            if(ctrl.length) {
                this.updateControlTargets(ctrl,req);
                target.append(ctrl.first());
            }
            target.focus();
            this.initializeContainers(target);
            
        };

        this.updateControlTargets = function(ctrl, req) {
            $('a',ctrl).each(function() {
                $(this).attr("href",req.url);
            });
            
        };

        this.initControlBehaviour = function(root) {
            $('div[container-id] .container-controls',root).each(function() {
                enhanceDetachLinks.apply(this);
            });

        };

        this.initExpandables = function(root) {
            $('div[container-id] .expandable',root).each(function() {
                var ctr = this;
                $('.expand-link',this).on("click",function() {
                    $(ctr).removeClass('collapsed');
                });
                $('.collapse-link',this).on("click",function() {
                    $(ctr).addClass('collapsed');
                });
            });
        };

        this.drawImplicitWidgets = function(root) {
            $('.icinga-widget[type="icinga/subTable"]',root).each(function() {
                new SubTable(this);
            });
            $('div[container-id] .inlinepie',root).each(function() {
                new Widgets.inlinePie(this,32,32);
            });
        };

        this.loadAsyncContainers = function(root) {
            $('.icinga-container[icinga-url]',root).each(function() {
                var el = $(this);
                var url = el.attr('icinga-url');
                el.attr('loaded',true);
                async.loadToTarget(el,url);
            });
        };

        this.initializeContainers = function(root) {
            this.initControlBehaviour(root);
            this.initExpandables(root);
            this.drawImplicitWidgets(root);
            this.loadAsyncContainers(root);
            
        };

        this.createPopupContainer = function(content,req) {
            var closeButton = $('<button type="button" class="close" data-dismiss="modal" >&times;</button>');
            var container = $('<div>').addClass('modal').attr('container-id','popup-'+req.url).attr("role","dialog")
                .append($("<div>").addClass('modal-header').text('Header').append(closeButton))
                .append($("<div>").addClass('modal-body').html(content)).appendTo(document.body);

            closeButton.on("click",function() {container.remove();});
            
        };

        this.getContainer = function(id) {
            return $('div[container-id='+id+']');
        };

    };
    define(['jquery','logging','icinga/widgets/checkIcons','icinga/widgets/subTable'], function($,log,widgets,subTable) {
        if (containerMgrInstance === null) {
            containerMgrInstance = new ContainerMgr($,log,widgets,subTable);
        }
        return containerMgrInstance;

    });
})();
