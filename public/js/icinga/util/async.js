/*global Icinga:false define:false require:false base_url:false console:false */
(function() {
    "use strict";
    var asyncMgrInstance = null;

    define(['icinga/container','logging','jquery'],function(containerMgr,log,$) {


        var pending = {

        };

        var getDOMForDestination = function(destination) {
            var target = destination;
            if(typeof destination === "string") {
                target = containerMgr.getContainer(destination)[0];
            } else if(typeof destination.context !== "undefined") {
                target = destination[0];
            }
            return target;
        };

        var handleResponse = function(html) {
            if(this.destination) {
                containerMgr.updateContainer(this.destination,html,this);
            } else {
                containerMgr.createPopupContainer(html,this);
            }
        };

        var handleFailure = function(result,error) {
            if(error === "abort") {
                return;
            }
            log.error("Error loading resource",error,arguments);
            if(this.destination) {
                containerMgr.updateContainer(this.destination,result.responseText,this);
            }
        };

        var isParent = function(dom,parentToCheck) {
            while(dom.parentNode) {
                dom = dom.parentNode;
                if(dom === parentToCheck) {
                    return true;
                }
            }
            return false;
        };

        var CallInterface = function() {

            this.clearPendingRequestsFor = function(destination) {
                if(!$.isArray(pending)) {
                    pending = [];
                    return;
                }
                var resultset = [];
                for(var x=0;x<pending.length;x++) {
                    var container = pending[x].DOM;
                    if(isParent(container,getDOMForDestination(destination))) {
                        pending[x].request.abort();
                    } else {
                        resultset.push(pending[x]);
                    }
                }
                pending = resultset;

            };

            this.createRequest = function(url,data) {
                var req = $.ajax({
                    type   : data ? 'POST' : 'GET',
                    url    :  url,
                    data   : data,
                    headers: { 'X-Icinga-Accept': 'text/html' }
                });
                req.url = url;
                req.done(handleResponse.bind(req));
                req.fail(handleFailure.bind(req));
                return req;
            };

            this.loadToTarget = function(destination,url,data) {
                if(destination) {
                    this.clearPendingRequestsFor(destination);
                }
                var req = this.createRequest(url,data);
                if(destination) {
                    pending.push({
                        request: req,
                        DOM: getDOMForDestination(destination)
                    });
                    req.destination = destination;
                }
                if (destination == "icinga-main") {
                    History.pushState(data, document.title, url);
                } else {
                    data = data || {};
                    data[destination] = url;
                    History.pushState(data, document.title, document.location.href); 
                }
                return req;

            };

            this.loadCSS = function(name) {

            };
        };
        return new CallInterface();
    });

})();
