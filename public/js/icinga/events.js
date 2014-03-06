(function(Icinga) {

  Icinga.Events = function(icinga) {
    this.icinga = icinga;
  };

  Icinga.Events.prototype = {

    /**
     * Icinga will call our initialize() function once it's ready
     */
    initialize: function()
    {
      this.applyGlobalDefaults();
      this.icinga.ui.prepareContainers();
    },

    // TODO: What's this?
    applyHandlers: function(el)
    {
        var icinga = this.icinga;
        $('.dashboard > div', el).each(function(idx, el) {
            var url = $(el).attr('data-icinga-url');
            if (typeof url === 'undefined') return;
            icinga.loader.loadUrl(url, $(el)).autorefresh = true;
        });
        // Set first links href in a action table tr as row href:
        $('table.action tr', el).each(function(idx, el) {
          var $a = $('a[href]', el).first();
          if ($a.length) {
            $(el).attr('href', $a.attr('href'));
          }
        });
        $('.icinga-module', el).each(function(idx, mod) {
            $mod = $(mod);
            var moduleName = $mod.data('icinga-module');
            if (icinga.hasModule(moduleName)) {
                var module = icinga.module(moduleName);
                // NOT YET, the applyOnloadDings: module.applyEventHandlers(mod);
            }
        });

        $('.inlinepie', el).sparkline('html', {
            type:        'pie',
            sliceColors: ['#44bb77', '#ffaa44', '#ff5566', '#dcd'],
            width:       '2em',
            height:      '2em',
        });

    },
    /**
     * Global default event handlers
     */
    applyGlobalDefaults: function()
    {
      // We catch resize events
      $(window).on('resize', { self: this }, this.onWindowResize);

      // Destroy Icinga, clean up and interrupt pending requests on unload
      $( window ).on('unload', { self: this }, this.onUnload);
      $( window ).on('beforeunload', { self: this }, this.onUnload);

      // We catch scroll events in our containers
      $('.container').on('scroll', icinga.events.onContainerScroll);

      // We want to catch each link click
      $(document).on('click', 'a', { self: this }, this.linkClicked);

      // We treat tr's with a href attribute like links
      $(document).on('click', 'tr[href]', { self: this }, this.linkClicked);

      // We catch all form submit events
      $(document).on('submit', 'form', { self: this }, this.submitForm);

      // We support an 'autosubmit' class on dropdown form elements
      $(document).on('change', 'form select.autosubmit', { self: this }, this.submitForm);

      $(window).on('popstate', { self: this }, this.historyChanged);

      // TBD: a global autocompletion handler
      // $(document).on('keyup', 'form.auto input', this.formChangeDelayed);
      // $(document).on('change', 'form.auto input', this.formChanged);
      // $(document).on('change', 'form.auto select', this.submitForm);
    },

    onUnload: function(event)
    {
        var icinga = event.data.self.icinga;
        icinga.logger.info('Unloading Icinga');
        icinga.destroy();
    },

    historyChanged: function(event)
    {
      var icinga = event.data.self.icinga;
      if (event.originalEvent.state === null) {
        icinga.logger.debug('No more history steps available');
      } else {
        icinga.logger.debug(event.originalEvent.state);
      }
      icinga.loader.loadUrl(
        document.location.pathname + document.location.search,
        $('#col1')
      ).historyTriggered = true;
    },

    /**
     * Our window got resized, let's fix our UI
     */
    onWindowResize: function(event)
    {
        var icinga = event.data.self.icinga;
        icinga.ui.fixControls();
    },

    /**
     * A scroll event happened in one of our containers
     */
    onContainerScroll: function(event)
    {
        // Yet ugly. And PLEASE, not so often
        icinga.ui.fixControls();
    },

    /**
     *
     */
    submitForm: function (event)
    {
        var icinga = event.data.self.icinga;
        event.stopPropagation();
        event.preventDefault();

        // .closest is not required unless subelements to trigger this
        var $form = $(event.currentTarget).closest('form');
        var url = $form.attr('action');
        var method = $form.attr('method');

        var data = $form.serializeArray();
        // TODO: Check button
        data.push({ name: 'btn_submit', value: 'yesss' });

        icinga.logger.debug('Submitting form: ' + method + ' ' + url);


        // We should move this to a generic target-finder:
        var $target = $form.closest('.container');
        if ($target.length == 0) {
            $target = $('#body');
        }

        icinga.loader.loadUrl(url, $target, data, method);

        // TODO: Do we really need to return false with stop/preventDefault?
        return false;
    },


    /**
     * Someone clicked a link or tr[href]
     */
    linkClicked: function(event)
    {
      var icinga = event.data.self.icinga;

      var $a = $(this);
      var href = $a.attr('href');
      if ($a.attr('target') === '_blank') {
          return true;
      }
      event.stopPropagation();
      event.preventDefault();
      if (href === '#') {
          if ($a.closest('#menu')) {
              var $li = $a.closest('li');
              $li.siblings('li.active').removeClass('active');
              $li.addClass('active');
          }
          return;
      }
      var $target = $('#col1');
      var $container = $a.closest('.container');
      if ($container.length) {
        $target = $container;
      }
// If link is hash tag...
      if ($a.closest('table.action').length) {
          $target = $('#col2');
          $('#layout').addClass('twocols');
          icinga.ui.fixControls();
      }
      if ($a.closest('[data-base-target]').length) {
          $target = $('#' + $a.closest('[data-base-target]').data('baseTarget'));
          $('#layout').addClass('twocols');
          icinga.ui.fixControls();
      }
      if ($a.closest('.tree').length) {
        var $li = $a.closest('li');
        if ($li.find('li').length) {
          if ($li.hasClass('collapsed')) {
              $li.removeClass('collapsed');
          } else {
              $li.addClass('collapsed');
              $li.find('li').addClass('collapsed');
          }
          return false;
        } else {
          $target = $('#col2');
          $('#layout').addClass('twocols');
          icinga.ui.fixControls();
        }
      }
      icinga.loader.loadUrl(href, $target);
      event.stopPropagation();
      event.preventDefault();
      if ($a.closest('#menu').length) {
        $('#layout').removeClass('twocols');
        $('#col2').html('<ul class="tabs"></ul>');
        icinga.ui.fixControls();
        return false;
      }
      if ($a.closest('table').length) {
        if ($('#layout').hasClass('twocols')) {
          if ($target.attr('id') === 'col2') return;
          icinga.logger.debug('Switching to single col');
          $('#layout').removeClass('twocols');
          icinga.ui.fixControls();
        } else {
          icinga.logger.debug('Switching to double col');
          $('#layout').addClass('twocols');
          icinga.ui.fixControls();
        }
        return false;
      }
    },

/*
    hrefIsHashtag: function(href)
    {
        // WARNING: IE gives full URL :(
        // Also it doesn't support negativ indexes in substr
        return href.substr(href.length - 1, 1) == '#';
    },
*/

    unbindGlobalHandlers: function()
    {
      $(window).off('popstate', this.historyChanged);
      $(window).off('resize', this.onWindowResize);
      $(window).off('unload', this.onUnload);
      $(window).off('beforeunload', this.onUnload);
      $(document).off('scroll', '.container', this.onContainerScroll);
      $(document).off('click', 'a', this.linkClicked);
      $(document).off('click', 'tr[href]', this.linkClicked);
      $(document).off('submit', 'form', this.submitForm);
      $(document).off('change', 'form select.autosubmit', this.submitForm);
    },

    destroy: function() {
      // This is gonna be hard, clean up the mess
      this.unbindGlobalHandlers();
      this.icinga = null;
    }
  };

}(Icinga));
