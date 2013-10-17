requirejs.config({
    'baseUrl': window.base_url + '/js',
    'urlArgs': "bust=" + (new Date()).getTime(),
    'paths': {
        'jquery':           'vendor/jquery-1.8.3',
        'jquery_scrollto':  'vendor/jquery.scrollto',
        'bootstrap':        'vendor/bootstrap/bootstrap.min',
        'logging':          'icinga/util/logging',
        'URIjs':            'vendor/uri',
        'datetimepicker':   'vendor/bootstrap/datetimepicker.min'
    },
    'shim': {
        'datetimepicker': {
            'exports': 'datetimepicker'
        },
        'jquery_scrollto': {
            exports: 'jquery_scrollto'
        },
        'jquery' : {
            exports: 'jquery'
        }
    }
});

define(['jquery'], function ($, history) {
    window.$ = $;
    window.jQuery = $;

    requirejs(['bootstrap','jquery_scrollto'], function() {
        requirejs(['datetimepicker']);
    });
    requirejs(['icinga/icinga'], function (Icinga) {
        window.Icinga = Icinga;
    });

});