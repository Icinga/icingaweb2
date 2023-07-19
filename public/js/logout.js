;(function () {
    /**
     * When JavaScript is available, trigger an XmlHTTPRequest with the non-existing user 'logout' and abort it
     * before it is able to finish. This will cause the browser to show a new authentication prompt in the next
     * request.
     */
    document.getElementById('logout-in-progress').hidden = true;
    document.getElementById('logout-successful').hidden = false;
    try {
        var xhttp = new XMLHttpRequest();
        xhttp.open('GET', 'arbitrary url', true, 'logout', 'logout');
        xhttp.send('');
        xhttp.abort();
    } catch (e) {
    }
})();
