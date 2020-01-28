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

  window.wame_public = window.wame_public || {};

  wame_public = $.extend({
    $wame: null,
    $badge: null,
    settings: null,
    store: null,
    chatbox: false,
    is_mobile: false,
  }, wame_public);

  // Trigger Google Analytics event
  wame_public.send_event = function (link) {
    if (typeof dataLayer == 'object') {
      // Send Google Tag Manager custom event
      dataLayer.push({
        'event': 'WhatsAppMe',
        'eventAction': 'click',
        'eventLabel': link
      });
    }
    if (typeof gtag == 'function') {
      // Send custom event (Global Site Tag - gtag.js)
      gtag('event', 'click', {
        'event_category': 'WhatsAppMe',
        'event_label': link,
        'transport_type': 'beacon'
      });
    } else if (typeof ga == 'function' && typeof ga.getAll == 'function') {
      // Send custom event (Universal Analtics - analytics.js)
      ga('set', 'transport', 'beacon');
      var trackers = ga.getAll();
      trackers.forEach(function (tracker) {
        tracker.send("event", 'WhatsAppMe', 'click', link);
      });
    }
    if (typeof fbq == 'function') {
      // Send Facebook Pixel custom event
      fbq('trackCustom', 'WhatsAppMe', { eventAction: 'click', eventLabel: link });
    }
  }

  // Return a simple hash (source https://gist.github.com/iperelivskiy/4110988#gistcomment-2697447)
  wame_public.hash = function (s) {
    for (var i = 0, h = 1; i < s.length; i++) {
      h = Math.imul(h + s.charCodeAt(i) | 0, 2654435761);
    }
    return (h ^ h >>> 17) >>> 0;
  };

  // Return WhatsApp link with optional message
  wame_public.whatsapp_link = function (phone, message, wa_web) {
    wa_web = typeof wa_web != 'undefined' ? wa_web : wame_public.settings.whatsapp_web && !wame_public.is_mobile;
    var link = wa_web ? 'https://web.whatsapp.com/send' : 'https://api.whatsapp.com/send';

    return link + '?phone=' + encodeURIComponent(phone) + '&text=' + encodeURIComponent(message || '');
  };

  wame_public.chatbox_show = function () {
    wame_public.$wame.addClass('whatsappme--chatbox');
    wame_public.chatbox = true;

    if (wame_public.settings.message_badge && wame_public.$badge.hasClass('whatsappme__badge--in')) {
      wame_public.$badge.toggleClass('whatsappme__badge--in whatsappme__badge--out');
    }
    // Trigger custom event
    $(document).trigger('whatsappme:show');
  };

  wame_public.chatbox_hide = function () {
    wame_public.$wame.removeClass('whatsappme--chatbox whatsappme--tooltip');
    wame_public.chatbox = false;
    // Trigger custom event
    $(document).trigger('whatsappme:hide');
  };

  wame_public.save_hash = function (message_hash) {
    var messages_viewed = (wame_public.store.getItem('whatsappme_hashes') || '').split(',').filter(Boolean);

    if (messages_viewed.indexOf(message_hash) == -1) {
      messages_viewed.push(message_hash);
      wame_public.store.setItem('whatsappme_hashes', messages_viewed.join(','));
    }
  };

  // Ready!!
  $(function () {
    wame_public.$wame = $('.whatsappme');
    wame_public.$badge = wame_public.$wame.find('.whatsappme__badge');
    wame_public.settings = wame_public.$wame.data('settings');
    wame_public.is_mobile = !!navigator.userAgent.match(/Android|iPhone|BlackBerry|IEMobile|Opera Mini/i);

    // Fallback if localStorage not supported (iOS incognito)
    // Implements functional storage in memory and will not persist between page loads
    try {
      localStorage.setItem('test', 1);
      localStorage.removeItem('test');
      wame_public.store = localStorage;
    } catch (e) {
      wame_public.store = {
        _data: {},
        setItem: function (id, val) { this._data[id] = String(val); },
        getItem: function (id) { return this._data.hasOwnProperty(id) ? this._data[id] : null; }
      };
    }

    // In some strange cases data settings are empty
    if (typeof wame_public.settings == 'undefined') {
      try {
        wame_public.settings = JSON.parse(wame_public.$wame.attr('data-settings'));
      } catch (error) {
        wame_public.settings = undefined;
      }
    }

    // Only works if whatsappme is defined
    if (wame_public.$wame.length && !!wame_public.settings && !!wame_public.settings.telephone) {
      whatsappme_magic();
    }

    function whatsappme_magic() {
      var button_delay = wame_public.settings.button_delay * 1000;
      var chat_delay = wame_public.settings.message_delay * 1000;
      var has_cta = !!wame_public.settings.message_text;
      var timeoutID, timeoutCTA;

      // Stored values
      var messages_viewed = (wame_public.store.getItem('whatsappme_hashes') || '').split(',').filter(Boolean);
      var is_second_visit = wame_public.store.getItem('whatsappme_visited') == 'yes';

      var message_hash = has_cta ? wame_public.hash(wame_public.settings.message_text).toString() : 'no_cta';
      var is_viewed = messages_viewed.indexOf(message_hash) > -1;

      wame_public.store.setItem('whatsappme_visited', 'yes');

      function chatbox_show() {
        clearTimeout(timeoutCTA);
        wame_public.chatbox_show();
      }

      function chatbox_hide() {
        wame_public.save_hash(message_hash);
        wame_public.chatbox_hide();
      }

      if (!wame_public.settings.mobile_only || wame_public.is_mobile) {
        var classes = 'whatsappme--show';
        if (!is_viewed && (!has_cta || !chat_delay || wame_public.settings.message_badge || !is_second_visit)) {
          classes += ' whatsappme--tooltip';
        }
        // Show button (and tooltip)
        setTimeout(function () { wame_public.$wame.addClass(classes); }, button_delay);

        if (has_cta && !is_viewed && chat_delay) {
          if (wame_public.settings.message_badge) {
            // Show badge
            timeoutCTA = setTimeout(function () { wame_public.$badge.addClass('whatsappme__badge--in'); }, button_delay + chat_delay);
          } else if (is_second_visit) {
            // Show chatbox
            timeoutCTA = setTimeout(chatbox_show, button_delay + chat_delay);
          }
        }
      }

      if (has_cta && !wame_public.is_mobile) {
        $('.whatsappme__button', wame_public.$wame)
          .mouseenter(function () { if (!wame_public.chatbox) timeoutID = setTimeout(chatbox_show, 1500); })
          .mouseleave(function () { clearTimeout(timeoutID); });
      }

      $('.whatsappme__button', wame_public.$wame).click(function () {
        if (has_cta && !wame_public.chatbox) {
          chatbox_show();
        } else {
          var args = { link: wame_public.whatsapp_link(wame_public.settings.telephone, wame_public.settings.message_send) };
          var secure_link = new RegExp("^https?:\/\/(wa\.me|(api|web|chat)\.whatsapp\.com|" + location.hostname.replace('.', '\.') + ")\/.*", 'i');

          if (wame_public.chatbox) {
            chatbox_hide();
          }
          // Trigger custom event (args obj allow edit link by third party scripts)
          $(document).trigger('whatsappme:open', [args, wame_public.settings]);

          // Ensure the link is safe
          if (secure_link.test(args.link)) {
            // Send analytics events
            wame_public.send_event(args.link);
            // Open WhatsApp link
            window.open(args.link, 'whatsappme');
          } else {
            console.error("WAme: the link doesn't seem safe, it must point to the current domain or whatsapp.com");
          }
        }
      });

      $('.whatsappme__close', wame_public.$wame).click(chatbox_hide);

      // Only scroll WAme message box (no all body)
      // TODO: disable also on touch
      $('.whatsappme__box__scroll').on('mousewheel DOMMouseScroll', function (e) {
        e.preventDefault();
        var delta = e.originalEvent.wheelDelta || -e.originalEvent.detail;
        this.scrollTop += (delta < 0 ? 1 : -1) * 30;
      });

      // Hide on mobile when virtual keyboard is open
      if (wame_public.is_mobile) {
        var initial_height = window.innerHeight;
        var timeoutKB;

        $(document).on('focus blur', 'input, textarea', function () {
          clearTimeout(timeoutKB);
          timeoutKB = setTimeout(function () {
            wame_public.$wame.toggleClass('whatsappme--show', initial_height * 0.7 < window.innerHeight);
          }, 500);
        });
      }

      $(document).trigger('whatsappme:start');
    }

  });

}(jQuery, window));