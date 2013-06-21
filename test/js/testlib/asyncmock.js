/**
 *  Helper for mocking $.async's XHR requests
 * 
 */


var getCallback = function(empty, response, succeed, headers) {
    if (empty)
        return function() {};
    return function(callback) {
        callback(response, succeed, {
            getAllResponseHeaders: function() {
                return headers; 
            },
            getResponseHeader: function(header) {
                return headers[header] || null;
            }
        });
    };
};

module.exports = {
    setNextAsyncResult: function(async, response, fails, headers) {
        headers = headers || {};
        var succeed = fails ? "fail" : "success";
        async.__internalXHRImplementation = function(config) {
            return {
                done: getCallback(fails, response, succeed, headers),
                fail: getCallback(!fails, response, succeed, headers)
            };
        };     
    }
};
