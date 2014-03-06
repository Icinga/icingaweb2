/**
 * Icinga.UI
 *
 * Our user interface
 */
(function(Icinga, $) {

    'use strict';

    Icinga.UI = function (icinga) {

        this.icinga = icinga;

        this.currentLayout = 'default';

        this.debug = false;

        this.debugTimer = null;

    };

    Icinga.UI.prototype = {

      initialize: function () {
          $('html').removeClass('no-js').addClass('js');
          this.icinga.timer.register(this.refreshTimeSince, this, 1000);
          this.triggerWindowResize();
      },

      enableDebug: function () {
          this.debug = true;
          this.debugTimer = this.icinga.timer.register(
              this.refreshDebug,
              this,
              1000
          );
          this.fixDebugVisibility();

          return this;
      },

      fixDebugVisibility: function () {
          if (this.debug) {
              $('#responsive-debug').css({display: 'block'});
          } else {
              $('#responsive-debug').css({display: 'none'});
          }
          return this;
      },

      disableDebug: function () {
          if (this.debug === false) { return; }

          this.debug = false;
          this.icinga.timer.unregister(this.debugTimer);
          this.debugTimer = null;
          this.fixDebugVisibility();
          return this;
      },

      flipContent: function () {
          var col1 = $('#col1 > div').detach();
          var col2 = $('#col2 > div').detach();
          $('#col2').html('');
          $('#col1').html('');

          col1.appendTo('#col2');
          col2.appendTo('#col1');
          this.fixControls();
      },

      triggerWindowResize: function () {
          this.onWindowResize({data: {self: this}});
      },

      /**
       * Our window got resized, let's fix our UI
       */
      onWindowResize: function (event) {
          var self = event.data.self;
          self.fixControls();

          if (self.layoutHasBeenChanged()) {
              self.icinga.logger.info(
                  'Layout change detected, switching to',
                  self.currentLayout
              );
          }
          self.refreshDebug();
      },

      layoutHasBeenChanged: function () {

          var layout = $('html').css('fontFamily').replace(/['",]/g, '');
          var matched;

          if (null !== (matched = layout.match(/^([a-z]+)-layout$/))) {
              if (matched[1] === this.currentLayout &&
                  $('#layout').hasClass(layout)
              ) {

                  return false;
              } else {
                  $('#layout').attr('class', layout);
                  this.currentLayout = matched[1];

                  return true;
              }
          }

          this.icinga.logger.error(
              'Someone messed up our responsiveness hacks, html font-family is',
              layout
          );

          return false;
      },

      getAvailableColumnSpace: function () {
          return $('#main').width() / this.getDefaultFontSize();
      },

      setColumnCount: function (count) {
          if (count === 3) {
              $('#main > .container').css({
                  width: '33.33333%'
              });
          } else if (count === 2) {
              $('#main > .container').css({
                  width: '50%'
              });
          } else {
              $('#main > .container').css({
                  width: '100%'
              });
          }
      },

      setTitle: function (title) {
          document.title = title;
          return this;
      },

      getColumnCount: function () {
          return $('#main > .container').length;
      },

      prepareContainers: function () {
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

      refreshDebug: function () {

          var size = this.getDefaultFontSize().toString();
          var winWidth = $( window ).width();
          var winHeight = $( window ).height();
          var loading = '';

          $.each(this.icinga.loader.requests, function (el, req) {
              if (loading === '') {
                  loading = '<br />Loading:<br />';
              }
              loading += el + ' => ' + req.url;
          });

          $('#responsive-debug').html(
              '   Time: ' +
              this.icinga.utils.formatHHiiss(new Date()) +
              '<br />    1em: ' +
              size +
              'px<br />    Win: ' +
              winWidth +
              'x'+
              winHeight +
              'px<br />' +
              ' Layout: ' +
              this.currentLayout +
              loading
          );
      },

      refreshTimeSince: function () {

          $('.timesince').each(function (idx, el) {
              var m = el.innerHTML.match(/^(-?\d+)m\s(-?\d+)s/);
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

          $('.timeunless').each(function (idx, el) {
              var m = el.innerHTML.match(/^(-?\d+)m\s(-?\d+)s/);
              if (m !== null) {
                  var nm = parseInt(m[1]);
                  var ns = parseInt(m[2]);
                  if (nm >= 0) {
                      if (ns > 0) {
                          ns--;
                      } else {
                          ns = 59;
                          nm--;
                      }
                  } else {
                      if (ns < 59) {
                          ns++;
                      } else {
                          ns = 0;
                          nm--;
                      }
                  }
                  $(el).html(nm + 'm ' + ns + 's');
              }
          });
      },

      createFontSizeCalculator: function () {
          var $el = $('<div id="fontsize-calc">&nbsp;</div>');
          $('#layout').append($el);
          return $el;
      },

      getDefaultFontSize: function () {
          var $calc = $('#fontsize-calc');
          if (! $calc.length) {
              $calc = this.createFontSizeCalculator();
          }
          return $calc.width() / 1000;
      },

      initializeControls: function (parent) {

          var self = this;

          $('.controls', parent).each(function (idx, el) {
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

      fixControls: function ($parent) {

          var self = this;

          if ('undefined' === typeof $parent) {

              $('#header').css({height: 'auto'});
              $('#main').css({top: $('#header').css('height')});
              $('#sidebar').css({top: $('#header').height() + 'px'});
              $('#header').css({height: $('#header').height() + 'px'});
              $('#inner-layout').css({top: $('#header').css('height')});
              $('.container').each(function (idx, container) {
                  self.fixControls($(container));
              });

              return;
          }

          self.icinga.logger.debug('Fixing controls for ', $parent);

          $('.controls', $parent).each(function (idx, el) {
              var $el = $(el);
              var $fake = $el.next('.fake-controls');
              var y = $parent.scrollTop();

              $el.css({
                  position : 'fixed',
                  top      : $parent.offset().top,
                  width    : $fake.css('width')
              });

              $fake.css({
                  height  : $el.css('height'),
                  display : 'block'
              });
          });
      },

      toggleFullscreen: function () {
          $('#layout').toggleClass('fullscreen-layout');
          this.fixControls();
      },

      getWindowId: function () {
          var res = window.name.match(/^Icinga_([a-zA-Z0-9])$/);
          if (res) {
              return res[1];
          }
          return null;
      },

      hasWindowId: function () {
          var res = window.name.match(/^Icinga_([a-zA-Z0-9])$/);
          return typeof res === 'object';
      },

      setWindowId: function (id) {
          window.name = 'Icinga_' + id;
      },

      destroy: function () {
          // This is gonna be hard, clean up the mess
          this.icinga = null;
      }

    };

}(Icinga, jQuery));
