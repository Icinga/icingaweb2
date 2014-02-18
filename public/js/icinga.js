/**
 * Icinga starts here.
 *
 * Usage example:
 *
 * <code>
 * var icinga = new Icinga({
 *   baseUrl: '/icinga',
 * });
 * </code>
 */
(function() {

  var Icinga = function(config) {

    /**
     * Our config object
     */
    this.config = config;

    /**
     * Icinga.Logger
     */
    this.logger = null;

    /**
     * Icinga.UI
     */
    this.ui = null;

    /**
     * Icinga.Loader
     */
    this.loader = null;

    /**
     * Icinga.Events
     */
    this.events = null;

    /**
     * Icinga.Timer
     */
    this.timer = null;

    /**
     * Icinga.Utils
     */
    this.utils = null;

    /**
     * Loaded modules
     */
    this.modules = {};

    var self = this;
    $(document).ready(function() {
      self.initialize();
      self = null;
    });
  };

  Icinga.prototype = {

    /**
     * Icinga startup, will be triggerd once the document is ready
     */
    initialize: function()
    {
      $('html').removeClass('no-js').addClass('js');

      this.utils  = new Icinga.Utils(this);
      this.logger = new Icinga.Logger(this);
      this.timer  = new Icinga.Timer(this);
      this.ui     = new Icinga.UI(this);
      this.loader = new Icinga.Loader(this);
      this.events = new Icinga.Events(this);

      this.timer.initialize();
      this.events.initialize();
      this.ui.initialize();
      this.loader.initialize();
      this.logger.setLevel('info');
      this.logger.info('Icinga is ready');
      this.timer.register(this.refreshTimeSince, this, 1000);
    },

    toggleFullscreen: function()
    {
        $('#layout').toggleClass('fullscreen');
        this.ui.fixControls();
    },

    flipContent: function()
    {
        var col1 = $('#col1 > div').detach();
        var col2 = $('#col2 > div').detach();
        $('#col2').html('');
        $('#col1').html('');

        col1.appendTo('#col2');
        col2.appendTo('#col1');
        this.ui.fixControls();
    },

    refreshTimeSince: function()
    {
      $('.timesince').each(function(idx, el) {
        var m = el.innerHTML.match(/^(\d+)m\s(\d+)s/);
        if (m !== null) {
          var nm = parseInt(m[1]);
          var ns = parseInt(m[2]);
          if (ns < 59) {
            ns++;
          } else {
            ns = 0;
            nm++;
          }
          $(el).html(nm + 'm ' + ns + 's');
        }
      });
    },

    getWindowId: function()
    {
      var res = window.name.match(/^Icinga_([a-zA-Z0-9])$/);
      if (res) {
        return res[1];
      }
      return null;
    },

    hasWindowId: function()
    {
      var res = window.name.match(/^Icinga_([a-zA-Z0-9])$/);
      return typeof res === 'object';
    },

    setWindowId: function(id)
    {
      window.name = 'Icinga_' + id;
    },

    /**
     * Load a given module by name
     */
    loadModule: function(name)
    {
      if (this.hasModule(name)) {
        this.logger.error('Cannot load module ' + name + ' twice');
        return;
      }
      this.modules[name] = new Icinga.Module(this, name);
    },

    /**
     * Whether a module matching the given name exists
     */
    hasModule: function(name)
    {
      return typeof this.modules[name] !== 'undefined' ||
          typeof Icinga.availableModules[name] !== 'undefined';
    },

    /**
     * Get a module by name
     */
    module: function(name)
    {
      if (typeof this.modules[name] === 'undefined') {
          if (typeof Icinga.availableModules[name] !== 'undefined') {
              this.modules[name] = new Icinga.Module(
                  this,
                  name,
                  Icinga.availableModules[name]
              );
          }
      }
      return this.modules[name];
    },

    /**
     * Clean up and unload all Icinga components
     */
    destroy: function()
    {
      $.each(this.modules, function(name, module) {
        module.destroy();
      });
      this.timer.destroy();
      this.events.destroy();
      this.loader.destroy();
      this.ui.destroy();
      this.logger.debug('Icinga has been destroyed');
      this.logger.destroy();
      this.utils.destroy();

      this.modules = [];
      this.timer = this.events = this.loader = this.ui = this.logger = this.utils = null;
    }
  };

  window.Icinga = Icinga;

  Icinga.availableModules = {};

})(window);

