requirejs.config({
    baseUrl: window.base_url + '/js',
    paths: {
        jquery: 'vendor/jquery-1.8.3',
        bootstrap: 'vendor/bootstrap.min',
        eve: 'vendor/raphael/eve',
        "raphael": 'vendor/raphael/raphael.amd',
        "raphael.core": 'vendor/raphael/raphael.core',
        "raphael.svg": 'vendor/raphael/raphael.svg',
        "raphael.vml": 'vendor/raphael/raphael.vml',
        'ace' : 'vendor/ace/ace',
        "Holder": 'vendor/holder',
        "History": 'vendor/history',

        logging: 'icinga/util/logging',

        datetimepicker: 'vendor/datetimepicker/bootstrap-datetimepicker.min'
    }

});

define(['jquery','Holder', 'History'], function ($) {
    requirejs(['bootstrap']);
    requirejs(['icinga/icinga'], function (Icinga) {
        window.$ = $;
        window.jQuery = $;
        window.Icinga = Icinga;
    });
});

define(['bootstrap'], function () {
    requirejs(['datetimepicker']);
});
