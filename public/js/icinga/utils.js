/**
 * Icinga utility functions
 */
(function(Icinga) {

  Icinga.Utils = function(icinga) {

    /**
     * Utility functions may need access to their Icinga instance
     */
    this.icinga = icinga;

    /**
     * We will use this to create an URL helper only once
     */
    this.url_helper = null;
  };

  Icinga.Utils.prototype = {

    timeWithMs: function(now)
    {
      if (typeof now === 'undefined') {
        now = new Date();
      }
      var ms = now.getMilliseconds() + '';
      while (ms.length < 3) {
          ms = '0' + ms;
      }
      return now.toLocaleTimeString() + '.' + ms;
    },

    timeShort: function(now)
    {
      if (typeof now === 'undefined') {
        now = new Date();
      }
      return now.toLocaleTimeString().replace(/:\d{2}$/, '');
    },

    /**
     * Parse a given Url and return an object 
     */
    parseUrl: function(url)
    {
      if (this.url_helper === null) {
        this.url_helper = document.createElement('a');
      }
      var a = this.url_helper;
      a.href = url;

      var result = {
        source  : url,
        protocol: a.protocol.replace(':', ''),
        host    : a.hostname,
        port    : a.port,
        query   : a.search,
        file    : (a.pathname.match(/\/([^\/?#]+)$/i) || [,''])[1],
        hash    : a.hash.replace('#',''),
        path    : a.pathname.replace(/^([^\/])/,'/$1'),
        relative: (a.href.match(/tps?:\/\/[^\/]+(.+)/) || [,''])[1],
        segments: a.pathname.replace(/^\//,'').split('/'),
        params  : this.parseParams(a),
      };
      a = null;

      return result;
    },

    /**
     * Parse url params
     */
    parseParams: function(a) {
      var params = {},
          segment = a.search.replace(/^\?/,'').split('&'),
          len = segment.length,
          i = 0,
          s;
      for (; i < len; i++) {
        if (! segment[i]) { continue; }
        s = segment[i].split('=');
        params[s[0]] = decodeURIComponent(s[1]);
      }
      return params;
    },

    /**
     * Cleanup
     */
    destroy: function()
    {
      this.url_helper = null;
      this.icinga = null;
    }
  };

}(Icinga));
