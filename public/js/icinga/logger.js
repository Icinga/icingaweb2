(function(Icinga) {

  Icinga.Logger = function(icinga) {

    // Well... we don't really need Icinga right now
    this.icinga = icinga;

    this.logLevel = 'info';

    this.logLevels = {
      'debug': 0,
      'info' : 1,
      'warn' : 2,
      'error': 3
    };

    // Let's get started
    this.initialize();
  };

  Icinga.Logger.prototype = {

    /**
     * Logger initialization
     */
    initialize: function()
    {
    },

    /**
     * Whether the browser has a console object
     */
    hasConsole: function()
    {
      return typeof console !== 'undefined';
    },

    debug: function(msg)
    {
      this.writeToConsole('debug', arguments);
    },

    setLevel: function(level)
    {
      if (this.numericLevel(level) !== 'undefined') {
        this.logLevel = level;
      }
    },

    info: function()
    {
      this.writeToConsole('info', arguments);
    },

    warn: function()
    {
      this.writeToConsole('warn', arguments);
    },

    error: function()
    {
      this.writeToConsole('error', arguments);
    },

    writeToConsole: function(level, args) {
      args = Array.prototype.slice.call(args);
      args.unshift(this.icinga.utils.timeWithMs());
      if (this.hasConsole() && this.hasLogLevel(level)) {
        console[level].apply(console, args);
      }
    },

    numericLevel: function(level)
    {
      var ret = this.logLevels[level];
      if (typeof ret === 'undefined') {
        throw 'Got invalid log level ' + level;
      }
      return ret;
    },

    hasLogLevel: function(level)
    {
      return this.numericLevel(level) >= this.numericLevel(this.logLevel);
    },

    /**
     * There isn't much to clean up here
     */
    destroy: function() {
      this.enabled = false;
      this.icinga = null;
    }
  };

}(Icinga));
