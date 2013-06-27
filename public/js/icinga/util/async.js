/*global Icinga:false define:false require:false base_url:false console:false */
(function() {
    "use strict";
    var asyncMgrInstance = null;

    define(['icinga/container','logging','jquery'],function(containerMgr,log,$) {

        var headerListeners = {};

        var pending = {

        };
        
        var getCurrentGETParameters = function() {
            var currentGET = window.location.search.substring(1).split("&");
            var params = {};
            if(currentGET.length > 0) {
                $.each(currentGET, function(idx, elem) {
                    var keyVal = elem.split("=");
                    params[encodeURIComponent(keyVal[0])] = encodeURIComponent(keyVal[1]);
                }); 
            }
            return params;
        }
;
        var pushGet = function(param, value, url) {
            url = url || (window.location.origin+window.location.pathname);
            var params = getCurrentGETParameters();
            params[encodeURIComponent(param)] = encodeURIComponent(value);
            var search = "?";
            for (var name in params) {
                if(search != "?")
                    search += "&";
                search += name+"="+params[name];
            }
    
            return url+search+"#"+window.location.hash;
        };
        
        var getDOMForDestination = function(destination) {
            var target = destination;
            if (typeof destination === "string") {
                target = containerMgr.getContainer(destination)[0];
            } else if(typeof destination.context !== "undefined") {
                target = destination[0];
            }
            return target;
        };

        var applyHeaderListeners = function(headers) {
            for (var header in headerListeners) {
                if (headers.getResponseHeader(header) === null) {
                    // see if the browser/server converts headers to lowercase
                    if (headers.getResponseHeader(header.toLowerCase()) === null) {
                        continue;
                    }
                    header = header.toLowerCase();
                }
                var value = headers.getResponseHeader(header);
                var listeners = headerListeners[header];
                for (var i=0;i<listeners.length;i++) {
                    listeners[i].fn.apply(listeners[i].scope, [value, header, headers]);
                }
            }
        };

        var handleResponse = function(html, status, response) {
            applyHeaderListeners(response); 
            if(this.destination) {
                containerMgr.updateContainer(this.destination,html,this);
            } else {
            // tbd
            // containerMgr.createPopupContainer(html,this);
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

            this.__internalXHRImplementation = $.ajax; 

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
                var req = this.__internalXHRImplementation({
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
                    log.debug("Laoding to container", destination, url);
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
                    url = pushGet("c["+destination+"]", url);
                    History.pushState(data, document.title, url);
                }
                console.log("New url: ", url);
                return req;
            };
            
            this.loadCSS = function(name) {

            };

            this.registerHeaderListener = function(header, fn, scope) {
                headerListeners[header] = headerListeners[header] || [];
                headerListeners[header].push({fn: fn, scope:scope});
            };
        };
        return new CallInterface();
    });
})();
