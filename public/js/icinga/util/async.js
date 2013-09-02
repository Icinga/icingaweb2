// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}
/*global Icinga:false define:false require:false base_url:false console:false */

(function() {
    "use strict";
    var asyncMgrInstance = null;

    define(['logging','jquery'],function(log,$,containerMgr) {

        var headerListeners = {};

        var pending = {

        };

        var encodeForURL = function(param) {
            return encodeURIComponent(param);
        };
         
        var getCurrentGETParameters = function() {
            var currentGET = window.location.search.substring(1).split("&");
            var params = {};
            if(currentGET.length > 0) {
                $.each(currentGET, function(idx, elem) {
                    var keyVal = elem.split("=");
                    params[keyVal[0]] = encodeForURL(keyVal[1]);
                }); 
            }
            return params;
        }
;
        var pushGet = function(param, value, url) {
            url = url || (window.location.origin+window.location.pathname);
            var params = getCurrentGETParameters();
            params[param] = encodeForURL(value);
            var search = "?";
            for (var name in params) {
                if (name === "" || typeof params[name] == "undefined") {
                    continue;
                }
                if (search != "?")
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
                    history.pushState(data, document.title, url);
                } else {
                    url = pushGet("c["+destination+"]", url);
                    history.pushState(data, document.title, url);
                }
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
