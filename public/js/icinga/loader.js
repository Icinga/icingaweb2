/**
 * Icinga.Loader
 *
 * This is where we take care of XHR requests, responses and failures.
 */
(function(Icinga) {

  Icinga.Loader = function(icinga) {

    /**
     * YES, we need Icinga
     */
    this.icinga = icinga;

    /**
     * Our base url
     */
    this.baseUrl = icinga.config.baseUrl;

    this.failureNotice = null;

    this.exception = null;

    /**
     * Pending requests
     */
    this.requests = {};

    this.autorefreshEnabled = true;
  };

  Icinga.Loader.prototype = {

    initialize: function()
    {
      this.icinga.timer.register(this.autorefresh, this, 10000);
    },

    /**
     * Load the given URL to the given target
     *
     * @param {string} url     URL to be loaded
     * @param {object} target  Target jQuery element
     * @param {object} data    Optional parameters, usually for POST requests
     * @param {string} method  HTTP method, default is 'GET'
     */
    loadUrl: function (url, $target, data, method)
    {
      var id = null;

      // Default method is GET
      if (typeof method === 'undefined') {
        method = 'GET';
      }

      this.icinga.logger.debug('Loading ', url, ' to ', $target);

      // We should do better and ignore requests without target and/or id
      if (typeof $target !== 'undefined' && $target.attr('id')) {
        id = $target.attr('id');
      }
      if (typeof $target !== 'undefined') {
        // TODO: We shouldn't use data but keep this information somewhere else.
        if ($target.data('icingaUrl') !== url) {
          $target.removeAttr('data-icinga-url');
          $target.removeAttr('data-icinga-refresh');
          $target.removeData('icingaUrl');
          $target.removeData('icingaRefresh');
        }
      }

      // If we have a pending request for the same target...
      if (id in this.requests) {
        // ...ignore the new request if it is already pending with the same URL
        if (this.requests[id].url === url) {
            this.icinga.logger.debug('Request to ', url, ' is already running for ', $target);
            return this.requests[id];
        }
        // ...or abort the former request otherwise
        this.icinga.logger.debug('Aborting pending request loading ', url, ' to ', $target);
        this.requests[id].abort();
      }

      // Not sure whether we need this Accept-header
      var headers = { 'X-Icinga-Accept': 'text/html' };

      // Ask for a new window id in case we don't already have one
      if (this.icinga.hasWindowId()) {
        headers['X-Icinga-WindowId'] = this.icinga.getWindowId();
      } else {
        headers['X-Icinga-WindowId'] = 'undefined';
      }

      var self = this;
      var req = $.ajax({
        type   : method,
        url    : url,
        data   : data,
        headers: headers,
        context: self
      });

      req.$target = $target;
      req.url = url;
      req.done(this.onResponse);
      req.fail(this.onFailure);
      req.historyTriggered = false;
      req.autorefresh = false;
      if (id) {
        this.requests[id] = req;
      }
      return req;
    },

    /**
     * Create an URL relative to the Icinga base Url, still unused
     *
     * @param {string} url Relative url
     */
    url: function(url)
    {
        if (typeof url === 'undefined') {
          return this.baseUrl;
        }
        return this.baseUrl + url;
    },

    autorefresh: function()
    {
      var self = this;
      if (self.autorefreshEnabled !== true) {
        return;
      }

      $('.container[data-icinga-refresh]').each(function(idx, el) {
        var $el = $(el);
        self.loadUrl($el.data('icingaUrl'), $el).autorefresh = true;
        el = null;
      });
    },

    disableAutorefresh: function()
    {
      this.autorefreshEnabled = false;
    },

    enableAutorefresh: function()
    {
      this.autorefreshEnabled = true;
    },

    /**
     * Handle successful XHR response
     */
    onResponse: function (data, textStatus, req)
    {
        if (this.failureNotice !== null) {
          this.failureNotice.remove();
          this.failureNotice = null;
        }

        if (this.exception !== null) {
          this.exception.remove();
          this.exception = null;
          req.$target.removeClass('impact');
        }

        var url = req.url;
        var targetId = req.$target.attr('id');
        this.icinga.logger.debug('Got response for ', req.$target, ', URL was ' + url);

        if (! req.autorefresh) {
          // TODO: Hook for response/url?
          var $matches = $('[href="' + url + '"]');
          $matches.each(function(idx, el) {
            if ($(el).closest('#menu').length) {
              $(el).closest('#menu').find('li.active').removeClass('active');
            } else if ($(el).closest('table.action').length) {
              $(el).closest('table.action').find('.active').removeClass('active');
            }
          });
        

          $matches.each(function(idx, el) {
            if ($(el).closest('#menu').length) {
              $(el).closest('li').addClass('active');
              $(el).parents('li').addClass('active');
            } else if ($(el).closest('table.action').length) {
              $(el).addClass('active');
            }
          });
        }

        delete this.requests[targetId];
        req.$target.attr('icingaurl', this.url);

        //
        var target = req.getResponseHeader('X-Icinga-Container');
        if (target) {
            req.$target = $('body');
        }

        var refresh = req.getResponseHeader('X-Icinga-Refresh');
        if (refresh) {
            // Hmmmm... .data() doesn't work here?
            req.$target.attr('data-icinga-refresh', refresh);
            req.$target.attr('data-icinga-url', req.url);
        }

        // Set a window identifier if the server asks us to do so
        var windowId = req.getResponseHeader('X-Icinga-WindowId');
        if (windowId) {
          this.icinga.setWindowId(windowId);
        }

        // Update history when necessary. Don't do so for requests triggered
        // by history or autorefresh events
        if (! req.historyTriggered && ! req.autorefresh) {

          // We only want to care about top-level containers
          if (req.$target.parent().closest('.container').length === 0) {
            this.icinga.logger.debug('Pushing ', req.url, ' to history');
            window.history.pushState({icinga: true}, null, req.url);
          }
        }
        $resp = $(req.responseText);

        /* Should we try to fiddle with responses containing full HTML? */
        /*
        if ($('body', $resp).length) {
            req.responseText = $('script', $('body', $resp).html()).remove();
        }
        */

        this.renderContentToContainer(req.responseText, req.$target);
    },

    /**
     * Handle failed XHR response
     */
    onFailure: function (req, textStatus, errorThrown)
    {
        var url = req.url;
        delete this.requests[req.$target.attr('id')];

        if (req.status === 500) {
          if (this.exception === null) {
            req.$target.addClass('impact');

            this.exception = this.createNotice(
              'error',
              $('h1', $(req.responseText)).first().html()
/*              'The connection to the Icinga web server has been lost at ' +
                this.icinga.utils.timeShort() +
                '.'
*/
            );
            this.icinga.ui.fixControls();
          }
        } else if (req.status > 0) {
            this.icinga.logger.debug(req.responseText.slice(0, 100));
            this.renderContentToContainer(
                '<h1>' + req.status + ' ' + errorThrown + '</h1> ' +
                    req.responseText,
                req.$target
            );

            // Header example:
            // Icinga.debug(req.getResponseHeader('X-Icinga-Redirect'));
        } else {
            if (errorThrown === 'abort') {
                this.icinga.logger.info('Request to ', url, ' has been aborted for ', req.$target);
            } else {
                if (this.failureNotice === null) {
                  this.failureNotice = this.createNotice(
                    'error',
                    'The connection to the Icinga web server has been lost at ' +
                      this.icinga.utils.timeShort() +
                      '.'
                  );
                  
                  this.icinga.ui.fixControls();
                }
                this.icinga.logger.error(
                  'Failed to contact web server loading ',
                  url,
                  ' for ',
                  req.$target
                );
            }
        }
    },

    createNotice: function(severity, message) {
      return $('<li class="' + severity + '">' + message + '</li>').appendTo($('#notifications'));
    },

    /**
     * Smoothly render given HTML to given container
     */
    renderContentToContainer: function (content, $container)
    {
        // Disable all click events while rendering
        $('*').click(function( event ) {
          event.stopImmediatePropagation();
          event.stopPropagation();
          event.preventDefault();
        });

        // Container update happens here
        var scrollPos = $container.scrollTop();
        $container.html(content);
        $container.scrollTop(scrollPos);

        // TODO: this.icinga.events.refreshContainer(container);
        var icinga = this.icinga;
        icinga.events.applyHandlers($container);
        icinga.ui.initializeControls($container);
        icinga.ui.fixControls();

        // Re-enable all click events
        $('*').off('click');
    },

    /**
     * On shutdown we kill all pending requests
     */
    destroy: function() {
      $.each(this.requests, function(id, request) {
        request.abort();
      });
      this.icinga = null;
      this.requests = {};
    }

  };

}(Icinga));
