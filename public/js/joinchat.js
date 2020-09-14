(function ($, window) {
  'use strict';

  window.joinchat_obj = window.joinchat_obj || {};

  joinchat_obj = $.extend({
    $div: null,
    settings: null,
    store: null,
    chatbox: false,
    is_mobile: false,
  }, joinchat_obj);

  joinchat_obj.$ = function (sel) {
    return $(sel || this.$div, this.$div);
  };

  // Trigger Google Analytics event
  joinchat_obj.send_event = function (label, action) {
    var ga_tracker = window[this.settings.ga_tracker || 'ga'];
    label = label || '';
    action = action || 'click';

    // Send Google Analtics custom event (Universal Analtics - analytics.js) or (Global Site Tag - gtag.js)
    if (typeof ga_tracker == 'function' && typeof ga_tracker.getAll == 'function') {
      ga_tracker('set', 'transport', 'beacon');
      var trackers = ga_tracker.getAll();
      trackers.forEach(function (tracker) {
        tracker.send("event", 'JoinChat', action, label);
      });
    } else if (typeof gtag == 'function') {
      gtag('event', action, {
        'event_category': 'JoinChat',
        'event_label': label,
        'transport_type': 'beacon'
      });
    }

    // Send Google Tag Manager custom event
    if (typeof dataLayer == 'object') {
      dataLayer.push({
        'event': 'JoinChat',
        'eventAction': action,
        'eventLabel': label
      });
    }

    // Send Facebook Pixel custom event
    if (typeof fbq == 'function') {
      fbq('trackCustom', 'JoinChat', { eventAction: action, eventLabel: label });
    }
  };

  // Return WhatsApp link with optional message
  joinchat_obj.whatsapp_link = function (phone, message, wa_web) {
    wa_web = typeof wa_web != 'undefined' ? wa_web : this.settings.whatsapp_web && !this.is_mobile;
    var link = wa_web ? 'https://web.whatsapp.com/send' : 'https://api.whatsapp.com/send';

    return link + '?phone=' + encodeURIComponent(phone) + '&text=' + encodeURIComponent(message || '');
  };

  joinchat_obj.chatbox_show = function () {
    if (!this.chatbox) {
      this.chatbox = true;
      this.$div.addClass('joinchat--chatbox');

      if (this.settings.message_badge && this.$('.joinchat__badge').hasClass('joinchat__badge--in')) {
        this.$('.joinchat__badge').toggleClass('joinchat__badge--in joinchat__badge--out');
      }
      // Trigger custom event
      $(document).trigger('joinchat:show');
    }
  };

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

  joinchat_obj.save_hash = function () {
    var hash = this.settings.message_hash || 'none';
    var saved_hashes = (this.store.getItem('joinchat_hashes') || '').split(',').filter(Boolean);

    if (saved_hashes.indexOf(hash) === -1) {
      saved_hashes.push(hash);
      this.store.setItem('joinchat_hashes', saved_hashes.join(','));
    }
  };

  joinchat_obj.open_whatsapp = function (phone, msg) {
    var args = { link: this.whatsapp_link(phone || this.settings.telephone, msg || this.settings.message_send) };
    var secure_link = new RegExp("^https?:\/\/(wa\.me|(api|web|chat)\.whatsapp\.com|" + location.hostname.replace('.', '\.') + ")\/.*", 'i');

    // Trigger custom event (args obj allow edit link by third party scripts)
    $(document).trigger('joinchat:open', [args, this.settings]);

    // Ensure the link is safe
    if (secure_link.test(args.link)) {
      // Send analytics events
      this.send_event(args.link);
      // Open WhatsApp link
      window.open(args.link, 'joinchat');
    } else {
      console.error("Join.chat: the link doesn't seem safe, it must point to the current domain or whatsapp.com");
    }
  };

  // Ready!!
  $(function () {
    joinchat_obj.$div = $('.joinchat');
    joinchat_obj.settings = joinchat_obj.$div.data('settings');
    joinchat_obj.is_mobile = !!navigator.userAgent.match(/Android|iPhone|BlackBerry|IEMobile|Opera Mini/i);

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
    if (typeof joinchat_obj.settings == 'undefined') {
      try { joinchat_obj.settings = JSON.parse(joinchat_obj.$div.attr('data-settings')); }
      catch (error) { joinchat_obj.settings = undefined; }
    }

    // Only works if joinchat is defined
    if (joinchat_obj.$div.length && !!joinchat_obj.settings && !!joinchat_obj.settings.telephone) {
      if (joinchat_obj.is_mobile || !joinchat_obj.settings.mobile_only) {
        joinchat_magic();
      } else {
        // Launch WhatsApp when click on nodes with class "joinchat_open"
        $(document).on('click', '.joinchat_open', function (e) {
          e.preventDefault();
          joinchat_obj.open_whatsapp();
        });
      }
    }

    joinchat_obj.store.setItem('joinchat_views', parseInt(joinchat_obj.store.getItem('joinchat_views') || 0) + 1);

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

      function chatbox_show() {
        clearTimeout(timeoutCTA);
        joinchat_obj.chatbox_show();
      }

      function chatbox_hide() {
        joinchat_obj.save_hash();
        joinchat_obj.chatbox_hide();
      }

      function joinchat_click() {
        if (has_chatbox && !joinchat_obj.chatbox) {
          chatbox_show();
        } else {
          chatbox_hide();
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
          timeoutCTA = setTimeout(chatbox_show, button_delay + chat_delay);
        }
      }

      // Open Join.chat on mouse over
      if (has_chatbox && !joinchat_obj.is_mobile) {
        joinchat_obj.$('.joinchat__button')
          .mouseenter(function () { timeoutHover = setTimeout(chatbox_show, 1500); })
          .mouseleave(function () { clearTimeout(timeoutHover); });
      }

      joinchat_obj.$('.joinchat__button').click(joinchat_click);
      joinchat_obj.$('.joinchat__close').click(chatbox_hide);

      // Only scroll Join.chat message box (no all body)
      // TODO: disable also on touch
      joinchat_obj.$('.joinchat__box__scroll').on('mousewheel DOMMouseScroll', function (e) {
        e.preventDefault();
        var delta = e.originalEvent.wheelDelta || -e.originalEvent.detail;
        this.scrollTop += (delta < 0 ? 1 : -1) * 30;
      });

      // Hide on mobile when virtual keyboard is open
      if (joinchat_obj.is_mobile) {
        var initial_height = window.innerHeight;
        var timeoutKB;

        $(document).on('focus blur', 'input, textarea', function (e) {
          if (!$(e.target).closest(joinchat_obj.$div)) {
            clearTimeout(timeoutKB);
            timeoutKB = setTimeout(function () {
              joinchat_obj.$div.toggleClass('joinchat--show', window.innerHeight > initial_height * 0.7);
            }, 800);
          }
        });
      }

      // Open Join.chat or launch WhatsApp when click on nodes with class "joinchat_open"
      $(document).on('click', '.joinchat_open', function (e) {
        e.preventDefault();
        if (!joinchat_obj.chatbox) joinchat_click();
      });

      // Close Join.chat when click on nodes with class "joinchat_close"
      $(document).on('click', '.joinchat_close', function (e) {
        e.preventDefault();
        chatbox_hide();
      });

      // Open Join.chat when "joinchat_open" or "joinchat_force_show" on viewport
      if (has_chatbox && 'IntersectionObserver' in window) {
        var $show_on_scroll = $('.joinchat_show, .joinchat_force_show');

        function joinchat_observed(objs) {
          $.each(objs, function () {
            if (this.intersectionRatio > 0 && (!is_viewed || $(this.target).hasClass('joinchat_force_show'))) {
              chatbox_show();
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

      $(document).trigger('joinchat:start');
    }

  });

}(jQuery, window));