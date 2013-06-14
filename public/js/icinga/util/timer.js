(function() {
    "use strict";

    var Timer = function() {
        this.resolution = 1000; // 1 second resolution
        this.containers = {

        };

        this.registerContainer = function(container) {
            this.containers[container.attr('container-id')] = container;
        };

        var tick = function() {
            for(var container in this.containers) {
                var el = this.containers[container];
                // document does not exist anymore
                if(!jQuery.contains(document.documentElement, el[0])) {
                    delete this.containers[container];
                    continue;
                }
            }
        };

    };

})();