(function(Icinga) {

  Icinga.UI = function(icinga) {
    this.icinga = icinga;
  };

  Icinga.UI.prototype = {
    initialize: function()
    {
      this.icinga.timer.register(this.refreshDebug, this, 1000);
      this.refreshDebug();
    },

    prepareContainers: function ()
    {
      var icinga = this.icinga;
      $('.container').each(function(idx, el) {
        icinga.events.applyHandlers($(el));
        icinga.ui.initializeControls($(el));
      });
/*
      $('#icinga-main').attr(
          'icingaurl',
          window.location.pathname + window.location.search
      );
*/
    },
    refreshDebug: function()
    {
      var size = this.icinga.ui.getDefaultFontSize().toString();
      var winWidth = $( window ).width();
      var winHeight = $( window ).height();
      $('#responsive-debug').html(
          'Time: ' +
          this.icinga.ui.formatHHiiss(new Date) +
          '<br />&nbsp;1em: ' +
          size +
          'px<br />&nbsp;Win: ' +
          winWidth +
          'x'+
          winHeight +
          'px<br />'
      ).css({display: 'block'});
    },
    formatHHiiss: function(date)
    {
      var hours = date.getHours();
      var minutes = date.getMinutes();
      var seconds = date.getSeconds();
      if (hours < 10) hours = '0' + hours;
      if (minutes < 10) minutes = '0' + minutes;
      if (seconds < 10) seconds = '0' + seconds;
      return hours + ':' + minutes + ':' + seconds;
    },
    createFontSizeCalculator: function()
    {
      var $el = $('<div id="fontsize-calc">&nbsp;</div>');
      $('#main').append($el);
      return $el;
    },
    getDefaultFontSize: function()
    {
      var $calc = $('#fontsize-calc');
      if (! $calc.length) {
        $calc = this.createFontSizeCalculator();
      }
      return $calc.width() / 1000;
    },
    initializeControls: function(parent)
    {
        var self = this;
        $('.controls', parent).each(function(idx, el) {
            var $el = $(el);
            if (! $el.next('.fake-controls').length) {
                var newdiv = $('<div class="fake-controls"></div>');
                newdiv.css({
                    height: $el.css('height')
                });
                $el.after(newdiv);
            }
        });
        this.fixControls(parent);
    },
    fixControls: function($parent)
    {
      var self = this;
      if (typeof $parent === 'undefined') {
        $('.container').each(function(idx, container) {
          self.fixControls($(container));
        });
        return;
      }
      self.icinga.logger.debug('Fixing controls for ', $parent);
      $('.controls', $parent).each(function(idx, el) {
        var $el = $(el);
        var $fake = $el.next('.fake-controls');
        var y = $parent.scrollTop();
        $el.css({
          position: 'fixed',
          top:      $parent.offset().top,
          width:    $fake.css('width')
        });
        $fake.css({
          height: $el.css('height'),
          display: 'block'
        });
      });
    },

    destroy: function() {
      // This is gonna be hard, clean up the mess
      this.icinga = null;
    }

  };

}(Icinga));
