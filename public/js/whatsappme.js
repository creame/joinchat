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

    // Only works if whatsappme is defined
    if ($whatsappme.length && !!wame_settings && !!wame_settings.telephone) {
      whatsappme_magic();
    }

    function whatsappme_magic() {
      var is_mobile = !!navigator.userAgent.match(/Android|iPhone|BlackBerry|IEMobile|Opera Mini/i);
      var button_delay = wame_settings.button_delay * 1000;
      var chat_delay = wame_settings.message_delay * 1000;
      var has_cta = !!wame_settings.message_text;
      var wa_web = wame_settings.whatsapp_web && !is_mobile;
      var dialog_visible = false;
      var timeoutID, timeoutCTA;

      // Check WebP support
      var webP = new Image();
      webP.src = 'data:image/webp;base64,UklGRi4AAABXRUJQVlA4TCEAAAAvAUAAEB8wAiMwAgSSNtse/cXjxyCCmrYNWPwmHRH9jwMA';
      webP.onload = webP.onerror = function () { if (webP.height !== 2) $whatsappme.addClass('nowebp'); }

      // Stored values
      var messages_viewed = (store.getItem('whatsappme_hashes') || '').split(',').filter(Boolean);
      var is_second_visit = store.getItem('whatsappme_visited') == 'yes';

      var message_hash = has_cta ? hash(wame_settings.message_text).toString() : 'no_cta';
      var is_viewed = messages_viewed.indexOf(message_hash) > -1;

      store.setItem('whatsappme_visited', 'yes');

      if (!wame_settings.mobile_only || is_mobile) {
        var classes = 'whatsappme--show';
        if (!is_viewed && (!has_cta || !chat_delay || wame_settings.message_badge || !is_second_visit)) {
          classes += ' whatsappme--tooltip';
        }
        // Show button (and tooltip)
        setTimeout(function () { $whatsappme.addClass(classes); }, button_delay);

        if (has_cta && !is_viewed && chat_delay) {
          if (wame_settings.message_badge) {
            // Show badge
            timeoutCTA = setTimeout(function () { $badge.addClass('whatsappme__badge--in'); }, button_delay + chat_delay);
          } else if (is_second_visit) {
            // Show dialog
            timeoutCTA = setTimeout(dialog_show, button_delay + chat_delay);
          }
        }
      }

      if (has_cta && !is_mobile) {
        $('.whatsappme__button', $whatsappme)
          .mouseenter(function () { if (!dialog_visible) timeoutID = setTimeout(dialog_show, 1500); })
          .mouseleave(function () { clearTimeout(timeoutID); });
      }

      $('.whatsappme__button', $whatsappme).click(function () {
        if (has_cta && !dialog_visible) {
          dialog_show();
        } else {
          var args = { link: whatsapp_link(wa_web, wame_settings.telephone, wame_settings.message_send) };
          var secure_link = new RegExp("^https?:\/\/(wa\.me|(api|web|chat)\.whatsapp\.com|" + location.hostname.replace('.', '\.') + ")\/.*", 'i');

          if (dialog_visible) {
            dialog_hide();
          }
          // Trigger custom event (args obj allow edit link by third party scripts)
          $(document).trigger('whatsappme:open', [args, wame_settings]);

          // Ensure the link is safe
          if (secure_link.test(args.link)) {
            // Send analytics events
            send_event(args.link);
            // Open WhatsApp link
            window.open(args.link, 'whatsappme');
          } else {
            console.error("WAme: the link doesn't seem safe, it must point to the current domain or whatsapp.com");
          }
        }
      });

      $('.whatsappme__close', $whatsappme).click(dialog_hide);

      function dialog_show() {
        $whatsappme.addClass('whatsappme--dialog');
        dialog_visible = true;
        clearTimeout(timeoutCTA);

        if (wame_settings.message_badge && $badge.hasClass('whatsappme__badge--in')) {
          $badge.toggleClass('whatsappme__badge--in whatsappme__badge--out');
        }
        // Trigger custom event
        $(document).trigger('whatsappme:show');
      }

      function dialog_hide() {
        $whatsappme.removeClass('whatsappme--dialog whatsappme--tooltip');
        dialog_visible = false;
        save_message_viewed();
        // Trigger custom event
        $(document).trigger('whatsappme:hide');
      }

      function save_message_viewed() {
        if (!is_viewed) {
          messages_viewed.push(message_hash);
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

  // Math.imul polyfill (source https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/imul#Polyfill)
  Math.imul = Math.imul || function (a, b) {
    var ah = (a >>> 16) & 0xffff;
    var al = a & 0xffff;
    var bh = (b >>> 16) & 0xffff;
    var bl = b & 0xffff;
    return ((al * bl) + (((ah * bl + al * bh) << 16) >>> 0) | 0);
  };

}(jQuery));