ace.define("ace/mode/icinga",function(require, exports, module) {
    "use strict";

    var oop = require("../lib/oop");
    var TextHighlightRules = require("./text_highlight_rules").TextHighlightRules;

    var IcingaHighlightRules = function() {
        var objectTypes = {
            "host" : ['host_name','alias','display_name','address','address6','parents','hostgroups','check_command','initial_state','max_check_attempts','check_interval','retry_interval','active_checks_enabled','passive_checks_enabled','check_period','obsess_over_host','check_freshness','freshness_threshold','event_handler','event_handler_enabled','low_flap_threshold','high_flap_threshold','flap_detection_enabled','flap_detection_options','failure_prediction_enabled','process_perf_data','retain_status_information','retain_nonstatus_information','contacts','contact_groups','notification_interval','first_notification_delay','notification_period','notification_options','notifications_enabled','stalking_options','notes','notes_url','action_url','icon_image','icon_image_alt','statusmap_image','2d_coords'],
            'hostgroup' : ['hostgroup_name','alias','members','hostgroup_members','notes','notes_url','action_url'],
            'service' : ['host_name','hostgroup_name','service_description','display_name','servicegroups','is_volatile','check_command','initial_state','max_check_attempts','check_interval','retry_interval','active_checks_enabled','passive_checks_enabled','check_period','obsess_over_service','check_freshness','freshness_threshold','event_handler','event_handler_enabled','low_flap_threshold','high_flap_threshold','flap_detection_enabled','flap_detection_options','failure_prediction_enabled','process_perf_data','retain_status_information','retain_nonstatus_information','notification_interval','first_notification_delay','notification_period','notification_options','notifications_enabled','contacts','contact_groups','stalking_options','notes','notes_url','action_url','icon_image','icon_image_alt']
        }

        this.$rules = {
            "start" : [
                {token : "keyword", regex : /define/, next: 'objectdefinition'},
                {token : "doc.comment", regex : /^#.*/},
                {token : "comment",  regex : /;.*$/},

                {caseInsensitive: true}
            ],
            "objectdefinition" : [

            ],
            "objects" : [
            ]

        }
        for(var object in objectTypes ) {
            this.$rules["objectdefinition"].push({
                'token' : 'keyword', regex: object+"[^{]*", next: object
            })
            this.$rules[object] = [
                {token : "paren.lparen", regex: "{ *", next: object},
                {token : "paren.rparen", regex: " *} *", next: 'start'},{
                    'token' : 'variable', regex: new RegExp("^ *("+objectTypes[object].join('|')+")"), next: object
                }, {
                    'token' : 'keyword', regex: 'use', next: object
                }, {
                    'token' : 'variable.parameter', regex: " +[^;]*$", next: object,
                }, {token : "comment",  regex : /;.*$/, next: object},{
                    token : 'variable', regex : /_[^ ]*/, next: object
                }]
        }
    };
    oop.inherits(IcingaHighlightRules, TextHighlightRules);
    exports.IcingaHighlightRules = IcingaHighlightRules;
});
