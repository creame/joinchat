(function ($, window) {
  'use strict';

  // Math.imul polyfill (source https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/imul#Polyfill)
  Math.imul = Math.imul || function (a, b) {
    var ah = (a >>> 16) & 0xffff;
    var al = a & 0xffff;
    var bh = (b >>> 16) & 0xffff;
    var bl = b & 0xffff;
    return ((al * bl) + (((ah * bl + al * bh) << 16) >>> 0) | 0);
  };

  window.joinchat_public = window.joinchat_public || {};

  joinchat_public = $.extend({
    $div: null,
    $badge: null,
    settings: null,
    store: null,
    chatbox: false,
    is_mobile: false,
  }, joinchat_public);

  // Trigger Google Analytics event
  joinchat_public.send_event = function (link) {
    var ga_tracker = window[joinchat_public.settings.ga_tracker || 'ga'];

    // Send Google Analtics custom event (Universal Analtics - analytics.js) or (Global Site Tag - gtag.js)
    if (typeof ga_tracker == 'function' && typeof ga_tracker.getAll == 'function') {
      ga_tracker('set', 'transport', 'beacon');
      var trackers = ga_tracker.getAll();
      trackers.forEach(function (tracker) {
        tracker.send("event", 'JoinChat', 'click', link);
      });
    } else if (typeof gtag == 'function') {
      gtag('event', 'click', {
        'event_category': 'JoinChat',
        'event_label': link,
        'transport_type': 'beacon'
      });
    }

    // Send Google Tag Manager custom event
    if (typeof dataLayer == 'object') {
      dataLayer.push({
        'event': 'JoinChat',
        'eventAction': 'click',
        'eventLabel': link
      });
    }

    // Send Facebook Pixel custom event
    if (typeof fbq == 'function') {
      fbq('trackCustom', 'JoinChat', { eventAction: 'click', eventLabel: link });
    }
  }

  // Return a simple hash (source https://gist.github.com/iperelivskiy/4110988#gistcomment-2697447)
  joinchat_public.hash = function (s) {
    for (var i = 0, h = 1; i < s.length; i++) {
      h = Math.imul(h + s.charCodeAt(i) | 0, 2654435761);
    }
    return (h ^ h >>> 17) >>> 0;
  };

  // Return WhatsApp link with optional message
  joinchat_public.whatsapp_link = function (phone, message, wa_web) {
    wa_web = typeof wa_web != 'undefined' ? wa_web : joinchat_public.settings.whatsapp_web && !joinchat_public.is_mobile;
    var link = wa_web ? 'https://web.whatsapp.com/send' : 'https://api.whatsapp.com/send';

    return link + '?phone=' + encodeURIComponent(phone) + '&text=' + encodeURIComponent(message || '');
  };

  joinchat_public.chatbox_show = function () {
    if (!joinchat_public.chatbox) {
      joinchat_public.chatbox = true;
      joinchat_public.$div.addClass('joinchat--chatbox');

      if (joinchat_public.settings.message_badge && joinchat_public.$badge.hasClass('joinchat__badge--in')) {
        joinchat_public.$badge.toggleClass('joinchat__badge--in joinchat__badge--out');
      }
      // Trigger custom event
      $(document).trigger('joinchat:show');
    }
  };

  joinchat_public.chatbox_hide = function () {
    if (joinchat_public.chatbox) {
      joinchat_public.chatbox = false;
      joinchat_public.$div.removeClass('joinchat--chatbox joinchat--tooltip');
      // Trigger custom event
      $(document).trigger('joinchat:hide');
    }
  };

  joinchat_public.save_hash = function (message_hash) {
    var messages_viewed = (joinchat_public.store.getItem('joinchat_hashes') || '').split(',').filter(Boolean);

    if (messages_viewed.indexOf(message_hash) == -1) {
      messages_viewed.push(message_hash);
      joinchat_public.store.setItem('joinchat_hashes', messages_viewed.join(','));
    }
  };

  // Ready!!
  $(function () {
    joinchat_public.$div = $('.joinchat');
    joinchat_public.$badge = joinchat_public.$div.find('.joinchat__badge');
    joinchat_public.settings = joinchat_public.$div.data('settings');
    joinchat_public.is_mobile = !!navigator.userAgent.match(/Android|iPhone|BlackBerry|IEMobile|Opera Mini/i);

    // Fallback if localStorage not supported (iOS incognito)
    // Implements functional storage in memory and will not persist between page loads
    try {
      localStorage.setItem('test', 1);
      localStorage.removeItem('test');
      joinchat_public.store = localStorage;
    } catch (e) {
      joinchat_public.store = {
        _data: {},
        setItem: function (id, val) { this._data[id] = String(val); },
        getItem: function (id) { return this._data.hasOwnProperty(id) ? this._data[id] : null; }
      };
    }

    // In some strange cases data settings are empty
    if (typeof joinchat_public.settings == 'undefined') {
      try {
        joinchat_public.settings = JSON.parse(joinchat_public.$div.attr('data-settings'));
      } catch (error) {
        joinchat_public.settings = undefined;
      }
    }

    // Only works if joinchat is defined
    if (joinchat_public.$div.length &&
      !!joinchat_public.settings &&
      !!joinchat_public.settings.telephone &&
      (joinchat_public.is_mobile || !joinchat_public.settings.mobile_only)) {
      joinchat_magic();
    }

    function joinchat_magic() {
      var button_delay = joinchat_public.settings.button_delay * 1000;
      var chat_delay = joinchat_public.settings.message_delay * 1000;
      var has_cta = !!joinchat_public.settings.message_text;
      var timeoutHover, timeoutCTA;

      // Stored values
      var messages_viewed = (joinchat_public.store.getItem('joinchat_hashes') || '').split(',').filter(Boolean);
      var is_second_visit = joinchat_public.store.getItem('joinchat_visited') == 'yes';

      var message_hash = has_cta ? joinchat_public.hash(joinchat_public.settings.message_text).toString() : 'no_cta';
      var is_viewed = messages_viewed.indexOf(message_hash) > -1;

      function chatbox_show() {
        clearTimeout(timeoutCTA);
        joinchat_public.chatbox_show();
      }

      function chatbox_hide() {
        joinchat_public.save_hash(message_hash);
        joinchat_public.chatbox_hide();
      }

      function joinchat_click() {
        if (has_cta && !joinchat_public.chatbox) {
          chatbox_show();
        } else {
          var args = { link: joinchat_public.whatsapp_link(joinchat_public.settings.telephone, joinchat_public.settings.message_send) };
          var secure_link = new RegExp("^https?:\/\/(wa\.me|(api|web|chat)\.whatsapp\.com|" + location.hostname.replace('.', '\.') + ")\/.*", 'i');

          chatbox_hide();

          // Trigger custom event (args obj allow edit link by third party scripts)
          $(document).trigger('joinchat:open', [args, joinchat_public.settings]);

          // Ensure the link is safe
          if (secure_link.test(args.link)) {
            // Send analytics events
            joinchat_public.send_event(args.link);
            // Open WhatsApp link
            window.open(args.link, 'joinchat');
          } else {
            console.error("Join.chat: the link doesn't seem safe, it must point to the current domain or whatsapp.com");
          }
        }
      }

      // Show button (and tooltip)
      var classes = 'joinchat--show';
      if (!is_viewed && (!has_cta || !chat_delay || joinchat_public.settings.message_badge || !is_second_visit)) {
        classes += ' joinchat--tooltip';
      }
      setTimeout(function () { joinchat_public.$div.addClass(classes); }, button_delay);

      // Show badge or chatbox
      if (has_cta && !is_viewed && chat_delay) {
        if (joinchat_public.settings.message_badge) {
          timeoutCTA = setTimeout(function () { joinchat_public.$badge.addClass('joinchat__badge--in'); }, button_delay + chat_delay);
        } else if (is_second_visit) {
          timeoutCTA = setTimeout(chatbox_show, button_delay + chat_delay);
        }
      }

      // Open Join.chat on mouse over
      if (has_cta && !joinchat_public.is_mobile) {
        $('.joinchat__button', joinchat_public.$div)
          .mouseenter(function () { timeoutHover = setTimeout(chatbox_show, 1500); })
          .mouseleave(function () { clearTimeout(timeoutHover); });
      }

      $('.joinchat__button', joinchat_public.$div).click(joinchat_click);
      $('.joinchat__close', joinchat_public.$div).click(chatbox_hide);

      // Only scroll Join.chat message box (no all body)
      // TODO: disable also on touch
      $('.joinchat__box__scroll').on('mousewheel DOMMouseScroll', function (e) {
        e.preventDefault();
        var delta = e.originalEvent.wheelDelta || -e.originalEvent.detail;
        this.scrollTop += (delta < 0 ? 1 : -1) * 30;
      });

      // Hide on mobile when virtual keyboard is open
      if (joinchat_public.is_mobile) {
        var initial_height = window.innerHeight;
        var timeoutKB;

        $(document).on('focus blur', 'input, textarea', function () {
          clearTimeout(timeoutKB);
          timeoutKB = setTimeout(function () {
            joinchat_public.$div.toggleClass('joinchat--show', initial_height * 0.7 < window.innerHeight);
          }, 800);
        });
      }

      // Open Join.chat or launch WhatsApp when click on nodes with class "joinchat_open"
      // TODO: allow also when btn is not visible
      $(document).on('click', '.joinchat_open', function (e) {
        e.preventDefault();
        if (!joinchat_public.chatbox) joinchat_click();
      });

      // Close Join.chat when click on nodes with class "joinchat_close"
      $(document).on('click', '.joinchat_close', function (e) {
        e.preventDefault();
        chatbox_hide();
      });

      // Open Join.chat when "joinchat_open" or "joinchat_force_show" on viewport
      if (has_cta && 'IntersectionObserver' in window) {
        var $show_on_scroll = $('.joinchat_show,.joinchat_force_show');

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

    joinchat_public.store.setItem('joinchat_visited', 'yes');

  });

}(jQuery, window));