requirejs.config({
    'baseUrl': window.base_url + '/js',
    'paths': {
        'jquery':           'vendor/jquery-1.8.3',
        'bootstrap':        'vendor/bootstrap/bootstrap.min',
        'history':          'vendor/history',
        'logging':          'icinga/util/logging',
        'datetimepicker':   'vendor/bootstrap/datetimepicker.min'
    },
    'shim': {
        'datetimepicker': {
            'exports': 'datetimepicker'
        },
        'jquery' : {
            exports: 'jquery'
        }
    }
});

define(['jquery', 'history'], function ($) {
    requirejs(['bootstrap'], function() {
        requirejs(['datetimepicker']);
    });

    requirejs(['icinga/icinga'], function (Icinga) {
        window.$ = $;
        window.jQuery = $;
        window.Icinga = Icinga;
    });
});