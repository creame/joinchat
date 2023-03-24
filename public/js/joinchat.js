(function ($, window, document, joinchat_obj) {
  'use strict';

  joinchat_obj = $.extend({
    $div: null,
    settings: null,
    store: null,
    chatbox: false,
    showed_at: 0,
    is_ready: false, // Change to true when Joinchat ends initialization
    is_mobile: !!navigator.userAgent.match(/Android|iPhone|BlackBerry|IEMobile|Opera Mini/i),
    can_qr: typeof kjua == 'function',
  }, joinchat_obj);
  window.joinchat_obj = joinchat_obj; // Save global

  joinchat_obj.$ = function (sel) {
    return $(sel || this.$div, this.$div);
  };

  /**
   * Trigger Analytics events
   *
   * Available customizations via joinchat_obj.settings:
   *  - 'ga_tracker' for custom UA tracker name (default 'ga' or GADP by MonsterInsights '__gaTracker')
   *  - 'data_layer' for custom data layer name (default 'dataLayer' or GTM4WP custom DataLayer name)
   *  - 'ga_event'   for GA4 custom event       (default 'generate_lead' recommended event)
   *
   * All params can be edited with document event 'joinchat:event' or cancel if returns false.
   * e.g.: $(document).on('joinchat:event', function(){ return false; });
   *
   */
  joinchat_obj.send_event = function (params) {
    params = $.extend({
      event_category: 'JoinChat', // Name
      event_label: '',            // Destination url
      event_action: '',           // "chanel: id"
      chat_channel: 'whatsapp',   // Channel name
      chat_id: '--',              // Channel contact (phone, username...)
      is_mobile: this.is_mobile ? 'yes' : 'no',
      page_location: location.href,
      page_title: document.title || 'no title',
    }, params);
    params.event_label = params.event_label || params.link || '';
    params.event_action = params.event_action || params.chat_channel + ': ' + params.chat_id;
    delete params.link;

    // Trigger event (params can be edited by third party scripts or cancel if return false)
    if (false === $(document).triggerHandler('joinchat:event', [params])) return;

    var ga_tracker = window[this.settings.ga_tracker] || window['ga'] || window['__gaTracker'];
    var data_layer = window[this.settings.data_layer] || window[window.gtm4wp_datalayer_name] || window['dataLayer'];

    // Send Google Analytics custom event (Universal Analytics - analytics.js)
    if (typeof ga_tracker == 'function' && typeof ga_tracker.getAll == 'function') {
      ga_tracker('set', 'transport', 'beacon');
      var trackers = ga_tracker.getAll();
      trackers.forEach(function (tracker) {
        tracker.send('event', params.event_category, params.event_action, params.event_label);
      });
    }

    // GA4 param max_length of 100 chars (https://support.google.com/analytics/answer/9267744)
    $.each(params, function (k, v) { params[k] = typeof v == 'string' ? v.substring(0, 100) : v; });

    // gtag.js
    if (typeof gtag == 'function' && typeof data_layer == 'object') {
      // Google Analytics 4 send recomended event "generate_lead"
      var ga4_event = this.settings.ga_event || 'generate_lead';
      var ga4_params = $.extend({ transport_type: 'beacon' }, params);
      // Already defined in GA4
      delete ga4_params.page_location;
      delete ga4_params.page_title;

      data_layer.forEach(function (item) {
        if (item[0] == 'config' && item[1] && item[1].substring(0, 2) == 'G-') {
          ga4_params.send_to = item[1];
          gtag('event', ga4_event, ga4_params);
        }
      });

      // Send Google Ads conversion
      if (this.settings.gads) {
        gtag('event', 'conversion', { send_to: this.settings.gads });
      }
    }

    // Store category in var and delete from params
    var event_category = params.event_category;
    delete params.event_category;

    // Send Google Tag Manager custom event
    if (typeof data_layer == 'object') {
      data_layer.push($.extend({ event: event_category }, params));
    }

    // Send Facebook Pixel custom event
    if (typeof fbq == 'function') {
      fbq('trackCustom', event_category, params);
    }
  };

  // Return WhatsApp link with optional message
  joinchat_obj.whatsapp_link = function (phone, message, wa_web) {
    message = message !== undefined ? message : this.settings.message_send || '';
    wa_web = wa_web !== undefined ? wa_web : this.settings.whatsapp_web && !this.is_mobile;
    var link = (wa_web ? 'https://web.whatsapp.com/send?phone=' : 'https://wa.me/') + encodeURIComponent(phone || this.settings.telephone);

    return link + (message ? (wa_web ? '&text=' : '?text=') + encodeURIComponent(message) : '');
  };

  // Open Chatbox and trigger event
  joinchat_obj.chatbox_show = function () {
    if (!this.chatbox) {
      this.chatbox = true;
      this.showed_at = Date.now();
      this.$div.addClass('joinchat--chatbox');

      if (this.settings.message_badge && this.$('.joinchat__badge').hasClass('joinchat__badge--in')) {
        this.$('.joinchat__badge').toggleClass('joinchat__badge--in joinchat__badge--out');
      }
      // Trigger custom event
      $(document).trigger('joinchat:show');
    }
  };

  // Close Chatbox and trigger event
  joinchat_obj.chatbox_hide = function () {
    if (this.chatbox) {
      this.chatbox = false;
      this.$div.removeClass('joinchat--chatbox joinchat--tooltip');

      if (this.settings.message_badge) {
        this.$('.joinchat__badge').removeClass('joinchat__badge--out');
      }
      // Trigger custom event
      $(document).trigger('joinchat:hide');
    }
  };

  // Save CTA hash
  joinchat_obj.save_hash = function () {
    var hash = this.settings.message_hash || 'none';
    var saved_hashes = (this.store.getItem('joinchat_hashes') || '').split(',').filter(Boolean);

    if (saved_hashes.indexOf(hash) === -1) {
      saved_hashes.push(hash);
      this.store.setItem('joinchat_hashes', saved_hashes.join(','));
    }
  };

  // Open WhatsApp link with supplied phone and message or with settings defaults
  joinchat_obj.open_whatsapp = function (phone, message) {
    phone = phone || this.settings.telephone;
    message = message !== undefined ? message : this.settings.message_send || '';

    var params = {
      link: this.whatsapp_link(phone, message),
      chat_channel: 'whatsapp',
      chat_id: phone,
      chat_message: message,
    };
    var secure_link = new RegExp("^https?:\/\/(wa\.me|(api|web|chat)\.whatsapp\.com|" + location.hostname.replace('.', '\.') + ")\/.*", 'i');

    // Trigger event (params can be edited by third party scripts or cancel if return false)
    if (false === $(document).triggerHandler('joinchat:open', [params])) return;

    // Ensure the link is safe
    if (secure_link.test(params.link)) {
      // Send analytics events
      this.send_event(params);
      // Open WhatsApp link
      window.open(params.link, 'joinchat', 'noopener');
    } else {
      console.error("Joinchat: the link doesn't seem safe, it must point to the current domain or whatsapp.com");
    }
  };

  joinchat_obj.optin = function () {
    return !this.$div.hasClass('joinchat--optout');
  };

  // Generate QR canvas
  joinchat_obj.qr = function (text, options) {
    return kjua($.extend({
      text: text,
      render: 'canvas',
      rounded: 80,
    }, joinchat_obj.settings.qr || {}, options || {}));
  }

  function joinchat_magic() {
    $(document).trigger('joinchat:starting');

    var button_delay = joinchat_obj.settings.button_delay * 1000;
    var chat_delay = joinchat_obj.settings.message_delay * 1000;
    var has_cta = !!joinchat_obj.settings.message_hash;
    var has_chatbox = !!joinchat_obj.$('.joinchat__box').length;
    var timeoutHover, timeoutCTA;

    // Stored values
    var has_pageviews = parseInt(joinchat_obj.store.getItem('joinchat_views') || 1) >= joinchat_obj.settings.message_views;
    var saved_hashes = (joinchat_obj.store.getItem('joinchat_hashes') || '').split(',').filter(Boolean);
    var is_viewed = saved_hashes.indexOf(joinchat_obj.settings.message_hash || 'none') !== -1;

    function clear_and_show() {
      clearTimeout(timeoutCTA);
      joinchat_obj.chatbox_show();
    }

    function save_and_hide() {
      joinchat_obj.save_hash();
      joinchat_obj.chatbox_hide();
    }

    function joinchat_click() {
      if (has_chatbox && !joinchat_obj.chatbox) {
        clear_and_show();
      } else if (Date.now() > joinchat_obj.showed_at + 600) { // A bit delay to prevent open WA on auto show
        save_and_hide();
        joinchat_obj.open_whatsapp();
      }
    }

    // Show button (and tooltip)
    var classes = 'joinchat--show';
    if (!is_viewed && (!has_cta || !chat_delay || joinchat_obj.settings.message_badge || !has_pageviews)) {
      classes += ' joinchat--tooltip';
    }
    setTimeout(function () { joinchat_obj.$div.addClass(classes); }, button_delay);

    // Show badge or chatbox
    if (has_cta && !is_viewed && chat_delay) {
      if (joinchat_obj.settings.message_badge) {
        timeoutCTA = setTimeout(function () { joinchat_obj.$('.joinchat__badge').addClass('joinchat__badge--in'); }, button_delay + chat_delay);
      } else if (has_pageviews) {
        timeoutCTA = setTimeout(clear_and_show, button_delay + chat_delay);
      }
    }

    // Open Joinchat on mouse over
    if (has_chatbox && !joinchat_obj.is_mobile) {
      joinchat_obj.$('.joinchat__button')
        .on('mouseenter', function () { timeoutHover = setTimeout(clear_and_show, 1500); })
        .on('mouseleave', function () { clearTimeout(timeoutHover); });
    }

    joinchat_obj.$('.joinchat__button').on('click', joinchat_click);
    joinchat_obj.$('.joinchat__close').on('click', save_and_hide);

    // Opt-in toggle
    joinchat_obj.$('#joinchat_optin').on('change', function () {
      joinchat_obj.$div.toggleClass('joinchat--optout', !this.checked);
    });

    // Only scroll Joinchat message box (no all body)
    // TODO: disable also on touch
    joinchat_obj.$('.joinchat__box__scroll').on('mousewheel DOMMouseScroll', function (e) {
      e.preventDefault();
      var delta = e.originalEvent.wheelDelta || -e.originalEvent.detail;
      this.scrollTop += (delta < 0 ? 1 : -1) * 30;
    });

    // Mobile enhancements
    if (joinchat_obj.is_mobile) {
      var timeoutKB, timeoutResize;

      function form_focus_toggle() {
        var type = (document.activeElement.type || '').toLowerCase();

        if (['date', 'datetime', 'email', 'month', 'number', 'password', 'search', 'tel', 'text', 'textarea', 'time', 'url', 'week'].indexOf(type) >= 0) {
          if (joinchat_obj.chatbox) {
            joinchat_obj.chatbox_hide();
            setTimeout(function () { joinchat_obj.$div.removeClass('joinchat--show'); }, 400);
          } else {
            joinchat_obj.$div.removeClass('joinchat--show');
          }
        } else {
          joinchat_obj.$div.addClass('joinchat--show');
        }
      }

      // Hide on mobile when virtual keyboard is open (on fill forms)
      $(document).on('focus blur', 'input, textarea', function (e) {
        if (!$(e.target).closest(joinchat_obj.$div).length) {
          clearTimeout(timeoutKB);
          timeoutKB = setTimeout(form_focus_toggle, 200);
        }
      });

      // Ensure header is visible
      $(window).on('resize', function () {
        clearTimeout(timeoutResize);
        timeoutResize = setTimeout(function () { joinchat_obj.$div[0].style.setProperty('--vh', window.innerHeight + 'px'); }, 200);
      }).trigger('resize');
    }

    // Triggers: open chatbox or launch WhatsApp on click
    $(document).on('click', '.joinchat_open, .joinchat_app, a[href="#joinchat"], a[href="#whatsapp"]', function (e) {
      e.preventDefault();
      if (has_chatbox && (!joinchat_obj.optin() || $(this).is('.joinchat_open, a[href="#joinchat"]'))) clear_and_show(); // Open chatbox
      else joinchat_obj.open_whatsapp($(this).data('phone'), $(this).data('message')); // WhatsApp direct
    });

    // Close chatbox when click on nodes with class "joinchat_close"
    $(document).on('click', '.joinchat_close', function (e) {
      e.preventDefault();
      joinchat_obj.chatbox_hide();
    });

    // Triggers: open chatbox on scroll (when node on viewport)
    if (has_chatbox && 'IntersectionObserver' in window) {
      var $show_on_scroll = $('.joinchat_show, .joinchat_force_show');

      function joinchat_observed(objs) {
        $.each(objs, function () {
          if (this.intersectionRatio > 0 && (!is_viewed || $(this.target).hasClass('joinchat_force_show'))) {
            clear_and_show();
            observer.disconnect(); // Only one show for visit
            return false;
          }
        });
      }

      if ($show_on_scroll.length > 0) {
        var observer = new IntersectionObserver(joinchat_observed);
        $show_on_scroll.each(function () { observer.observe(this); });
      }
    }

    // Add QR Code
    if (joinchat_obj.settings.qr && joinchat_obj.can_qr && !joinchat_obj.is_mobile) {
      joinchat_obj.$('.joinchat__qr').append(joinchat_obj.qr(joinchat_obj.whatsapp_link(undefined, undefined, false)));
    } else {
      joinchat_obj.$('.joinchat__qr').remove();
    }

    // Fix message clip-path style broken by some CSS optimizers
    if (has_chatbox) {
      joinchat_obj.$div.css('--peak', 'ur' + 'l(#joinchat__message__peak)');
    }

    $(document).trigger('joinchat:start');
    joinchat_obj.is_ready = true;
  }

  // Simple run only once wrapper
  function once(fn) {
    return function () {
      fn && fn.apply(this, arguments);
      fn = null;
    };
  }

  function on_page_ready() {
    joinchat_obj.$div = $('.joinchat');

    // Exit if no joinchat div
    if (!joinchat_obj.$div.length) return;

    joinchat_obj.settings = joinchat_obj.$div.data('settings');

    // Fallback if localStorage not supported (iOS incognito)
    // Implements functional storage in memory and will not persist between page loads
    try {
      localStorage.setItem('test', 1);
      localStorage.removeItem('test');
      joinchat_obj.store = localStorage;
    } catch (e) {
      joinchat_obj.store = {
        _data: {},
        setItem: function (id, val) { this._data[id] = String(val); },
        getItem: function (id) { return this._data.hasOwnProperty(id) ? this._data[id] : null; }
      };
    }

    // In some strange cases data settings are empty
    if (typeof joinchat_obj.settings != 'object') {
      try {
        joinchat_obj.settings = JSON.parse(joinchat_obj.$div.attr('data-settings'));
      } catch (error) {
        joinchat_obj.settings = undefined;
        console.error("Joinchat: can't get settings");
      }
    }

    // Only works if joinchat is defined
    if (!!joinchat_obj.settings && !!joinchat_obj.settings.telephone) {
      if (joinchat_obj.is_mobile || !joinchat_obj.settings.mobile_only) {
        joinchat_magic();
      } else {
        // Ensure don't show
        joinchat_obj.$div.removeClass('joinchat--show');

        // Triggers: launch WhatsApp on click
        $(document).on('click', '.joinchat_open, .joinchat_app, a[href="#joinchat"], a[href="#whatsapp"]', function (e) {
          e.preventDefault();
          joinchat_obj.open_whatsapp($(this).data('phone'), $(this).data('message'));
        });
      }

      // Gutenberg buttons add QR
      if (joinchat_obj.can_qr && !joinchat_obj.is_mobile) {
        $('.joinchat-button__qr').each(function () {
          $(this).append(joinchat_obj.qr(joinchat_obj.whatsapp_link($(this).data('phone'), $(this).data('message'), false)));
        });
      } else {
        $('.wp-block-joinchat-button figure').remove();
      }

      // Replace product variable SKU
      if (joinchat_obj.settings.sku !== undefined) {
        var message = joinchat_obj.settings.message_send;
        $('form.variations_form').on('found_variation reset_data', function (e, variation) {
          var sku = variation && variation.sku || joinchat_obj.settings.sku;
          joinchat_obj.$('.joinchat__box sku').text(sku);
          joinchat_obj.settings.message_send = message.replace(/<sku>.*<\/sku>/g, sku);
        });
      }
    }

    joinchat_obj.store.setItem('joinchat_views', parseInt(joinchat_obj.store.getItem('joinchat_views') || 0) + 1);
  }

  // Ready!! (in some scenarios jQuery.ready doesn't fire, this try to ensure Joinchat initialization)
  var once_page_ready = once(on_page_ready);
  $(once_page_ready);
  $(window).on('load', once_page_ready);
  document.addEventListener('DOMContentLoaded', once_page_ready);

}(jQuery, window, document, window.joinchat_obj || {}));
