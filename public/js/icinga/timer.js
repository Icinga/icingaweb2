/**
 * Icinga.Timer
 *
 * Timer events are triggered once a second. Runs all reegistered callback
 * functions and is able to preserve a desired scope.
 */
(function(Icinga) {

  Icinga.Timer = function(icinga) {

    /**
     * We keep a reference to the Icinga instance even if we don't need it
     */
    this.icinga = icinga;

    /**
     * The Interval object
     */
    this.ticker = null;

    /**
     * Fixed default interval is 250ms
     */
    this.interval = 250;

    /**
     * Our registerd observers
     */
    this.observers = [];

    /**
     * Counter
     */
    this.stepCounter = 0;

    this.start = (new Date()).getTime();


    this.lastRuntime = [];
  };

  Icinga.Timer.prototype = {

    /**
     * The initialization function starts our ticker
     */
    initialize: function(icinga)
    {
      var self = this;
      this.ticker = setInterval(function() { self.tick(); }, this.interval);
    },

    /**
     * We will trigger our tick function once a second. It will call each
     * registered observer.
     */
    tick: function()
    {
      var icinga = this.icinga;
      $.each(this.observers, function(idx, observer) {
        if (observer.isDue()) {
          observer.run();
        } else {
          // Not due
        }
      });
      icinga = null;
    },

    /**
     * Register a given callback function to be run within an optional scope.
     */
    register: function(callback, scope, interval)
    {
      try {
        if (typeof scope === 'undefined') {
          this.observers.push(new Icinga.Timer.Interval(callback, interval));
        } else {
          this.observers.push(
            new Icinga.Timer.Interval(
              callback.bind(scope),
              interval
            )
          );
        }
      } catch(err) {
        this.icinga.logger.error(err);
      }
    },

    /**
     * Our destroy function will clean up everything. Unused right now.
     */
    destroy: function()
    {
      if (this.ticker !== null) {
        clearInterval(this.ticker);
      }
      this.icinga = null;
      $.each(this.observers, function(idx, observer) {
        observer.destroy();
      });
      this.observers = [];
    }
  };

  Icinga.Timer.Interval = function(callback, interval) {
    
    if (typeof interval === 'undefined') {
      throw 'Timer interval is required';
    }

    if (interval < 100) {
      throw 'Timer interval cannot be less than 100ms, got ' + interval;
    }

    this.lastRun = (new Date()).getTime();

    this.interval = interval;

    this.scheduledNextRun = this.lastRun + interval;

    this.callback = callback;
  };

  Icinga.Timer.Interval.prototype = {
    isDue: function()
    {
      return this.scheduledNextRun < (new Date()).getTime();
    },

    run: function()
    {
        this.lastRun = (new Date()).getTime();
        while (this.scheduledNextRun < this.lastRun) {
          this.scheduledNextRun += this.interval;
        }
        this.callback();    
    },

    destroy: function()
    {
      this.callback = null;
    }
  };

}(Icinga));
