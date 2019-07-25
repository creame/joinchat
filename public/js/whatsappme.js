(function ($) {
  'use strict';

  $(function () {
    var $whatsappme = $('.whatsappme');
    var $badge = $whatsappme.find('.whatsappme__badge');
    var wame_settings = $whatsappme.data('settings');
    var store;

    // Fallback if localStorage not supported (iOS incognito)
    // Implements functional storage in memory and will not persist between page loads
    try {
      localStorage.setItem('test', 1);
      localStorage.removeItem('test');
      store = localStorage;
    } catch (e) {
      store = {
        _data: {},
        setItem: function (id, val) { this._data[id] = String(val); },
        getItem: function (id) { return this._data.hasOwnProperty(id) ? this._data[id] : null; }
      };
    }

    // In some strange cases data settings are empty
    if (typeof (wame_settings) == 'undefined') {
      try {
        wame_settings = JSON.parse($whatsappme.attr('data-settings'));
      } catch (error) {
        wame_settings = undefined;
      }
    }

    // only works if whatsappme is defined
    if ($whatsappme.length && !!wame_settings && !!wame_settings.telephone) {
      whatsappme_magic();
    }

    function whatsappme_magic() {
      var is_mobile = !!navigator.userAgent.match(/Android|iPhone|BlackBerry|IEMobile|Opera Mini/i);
      var button_delay = wame_settings.button_delay * 1000;
      var has_cta = wame_settings.message_text !== '';
      var wa_web = wame_settings.whatsapp_web && !is_mobile;
      var message_hash, is_viewed, timeoutID, timeoutCTA;

      // check WebP support
      var webP = new Image();
      webP.src = 'data:image/webp;base64,UklGRi4AAABXRUJQVlA4TCEAAAAvAUAAEB8wAiMwAgSSNtse/cXjxyCCmrYNWPwmHRH9jwMA';
      webP.onload = webP.onerror = function () {
        if (webP.height !== 2) $whatsappme.addClass('nowebp');
      }

      // stored values
      var messages_viewed = (store.getItem('whatsappme_hashes') || '').split(',').filter(Boolean);
      var is_second_visit = store.getItem('whatsappme_visited') == 'yes';

      if (has_cta) {
        message_hash = hash(wame_settings.message_text).toString();
        is_viewed = messages_viewed.indexOf(message_hash) > -1;
      }

      store.setItem('whatsappme_visited', 'yes');

      if (!wame_settings.mobile_only || is_mobile) {
        // show button
        setTimeout(function () { $whatsappme.addClass('whatsappme--show'); }, button_delay);

        if (has_cta && !is_viewed) {
          if (wame_settings.message_badge) { // show badge
            timeoutCTA = setTimeout(function () { $badge.addClass('whatsappme__badge--in'); }, button_delay + (wame_settings.message_delay * 1000));
          } else if (is_second_visit) { // show dialog
            timeoutCTA = setTimeout(function () { $whatsappme.addClass('whatsappme--dialog'); }, button_delay + (wame_settings.message_delay * 1000));
          }
        }
      }

      if (has_cta && !is_mobile) {
        $('.whatsappme__button', $whatsappme)
          .mouseenter(function () { timeoutID = setTimeout(show_dialog, 1500); })
          .mouseleave(function () { clearTimeout(timeoutID); });
      }

      $('.whatsappme__button', $whatsappme).click(function () {
        var link = whatsapp_link(wa_web, wame_settings.telephone, wame_settings.message_send);

        if (has_cta && !$whatsappme.hasClass('whatsappme--dialog')) {
          show_dialog();
        } else {
          $whatsappme.removeClass('whatsappme--dialog');
          save_message_viewed();
          send_event(link);
          // Open WhatsApp link
          window.open(link, 'whatsappme');
        }
      });

      $('.whatsappme__close', $whatsappme).click(function () {
        $whatsappme.removeClass('whatsappme--dialog');
        save_message_viewed();
      });

      function show_dialog() {
        $whatsappme.addClass('whatsappme--dialog');
        clearTimeout(timeoutCTA);

        if (wame_settings.message_badge && $badge.hasClass('whatsappme__badge--in')) {
          $badge.removeClass('whatsappme__badge--in').addClass('whatsappme__badge--out');
          save_message_viewed();
        }
      }

      function save_message_viewed() {
        if (has_cta && !is_viewed) {
          messages_viewed.push(message_hash)
          store.setItem('whatsappme_hashes', messages_viewed.join(','));
          is_viewed = true;
        }
      }
    }
  });

  // Return a simple hash (source https://gist.github.com/iperelivskiy/4110988#gistcomment-2697447)
  function hash(s) {
    for (var i = 0, h = 1; i < s.length; i++) {
      h = Math.imul(h + s.charCodeAt(i) | 0, 2654435761);
    }
    return (h ^ h >>> 17) >>> 0;
  };

  // Return WhatsApp link with optional message
  function whatsapp_link(wa_web, phone, message) {
    var link = wa_web ? 'https://web.whatsapp.com/send' : 'https://api.whatsapp.com/send';

    return link + '?phone=' + encodeURIComponent(phone) + '&text=' + encodeURIComponent(message || '');
  }

  // Trigger Google Analytics event
  function send_event(link) {
    if (typeof dataLayer == 'object') {
      // Send Google Tag Manager Event
      dataLayer.push({
        'event': 'WhatsAppMe',
        'eventAction': 'click',
        'eventLabel': link
      });
    }
    if (typeof gtag == 'function') {
      // Send event (Global Site Tag - gtag.js)
      gtag('event', 'click', {
        'event_category': 'WhatsAppMe',
        'event_label': link,
        'transport_type': 'beacon'
      });
    } else if (typeof ga == 'function') {
      // Send event (Universal Analtics - analytics.js)
      ga('set', 'transport', 'beacon');
      var trackers = ga.getAll();
      trackers.forEach(function (tracker) {
        tracker.send("event", 'WhatsAppMe', 'click', link);
      });
    }
  }

  // Math.imul polyfill (source https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/imul#Polyfill)
  Math.imul = Math.imul || function (a, b) {
    var ah = (a >>> 16) & 0xffff;
    var al = a & 0xffff;
    var bh = (b >>> 16) & 0xffff;
    var bl = b & 0xffff;
    return ((al * bl) + (((ah * bl + al * bh) << 16) >>> 0) | 0);
  };

}(jQuery));