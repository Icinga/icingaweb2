function init(isIframe) {
    initLayout();

    if (isIframe) {
        initJsAttribute();
    }

    window.name = document.currentScript.getAttribute('id');
    window.icinga = new Icinga({
        baseUrl: document.currentScript.getAttribute('baseUrl'),
        locale: document.currentScript.getAttribute('locale'),
        timezone: document.currentScript.getAttribute('timezone')
    });
}

function initLayout() {
    if (document.defaultView && document.defaultView.getComputedStyle) {
        var matched;
        var html = document.getElementsByTagName('html')[0];
        var element = document.getElementById('layout');
        var name = document.defaultView
            .getComputedStyle(html)['font-family']
            .replace(/['",]/g, '');

        if (null !== (matched = name.match(/^([a-z]+)-layout$/))) {
            element.className = element.className.replace('default-layout', name);
            if ('object' === typeof window.console) {
                window.console.log('Icinga Web 2: setting initial layout to ' + name);
            }
        }
    }
}

function initJsAttribute() {
    var html = document.getElementsByTagName('html')[0];
    html.className = html.className.replace(/no-js/, 'js');
}

(() => {
    init(!!document.currentScript.getAttribute('isIframe'));
})();
