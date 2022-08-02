(function ($, win, doc) {
  'use strict';

  win.joinchat_obj = $.extend({
    settings: null,
    is_mobile: !!navigator.userAgent.match(/Android|iPhone|BlackBerry|IEMobile|Opera Mini/i),
  }, win.joinchat_obj || {});

  // Trigger Analytics events
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
    if (false === $(doc).triggerHandler('joinchat:event', [params])) return;

    // Can pass setting 'ga_tracker' for custom UA tracker name
    // Compatible with GADP for WordPress by MonsterInsights tracker name
    var ga_tracker = win[this.settings.ga_tracker] || win['ga'] || win['__gaTracker'];
    // Can pass setting 'data_layer' for custom data layer name
    // Compatible with GTM4WP custom DataLayer name
    var data_layer = win[this.settings.data_layer] || win[win.gtm4wp_datalayer_name] || win['dataLayer'];

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
      var ga4_params = $.extend({ transport_type: 'beacon' }, params);
      // Already defined in GA4
      delete ga4_params.page_location;
      delete ga4_params.page_title;

      data_layer.forEach(function (item) {
        if (item[0] == 'config' && item[1].substring(0, 2) == 'G-') {
          ga4_params.send_to = item[1];
          gtag('event', 'generate_lead', ga4_params);
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
    message = typeof message != 'undefined' ? message : this.settings.message_send || '';
    wa_web = typeof wa_web != 'undefined' ? wa_web : this.settings.whatsapp_web && !this.is_mobile;
    var link = (wa_web ? 'https://web.whatsapp.com/send?phone=' : 'https://wa.me/') + encodeURIComponent(phone || this.settings.telephone);

    return link + (message ? (wa_web ? '&text=' : '?text=') + encodeURIComponent(message) : '');
  };

  joinchat_obj.open_whatsapp = function (phone, message) {
    phone = phone || this.settings.telephone;
    message = typeof message != 'undefined' ? message : this.settings.message_send || '';

    var params = {
      link: this.whatsapp_link(phone, message),
      chat_channel: 'whatsapp',
      chat_id: phone,
      chat_message: message,
    };
    var secure_link = new RegExp("^https?:\/\/(wa\.me|(api|web|chat)\.whatsapp\.com|" + location.hostname.replace('.', '\.') + ")\/.*", 'i');

    // Trigger event (params can be edited by third party scripts or cancel if return false)
    if (false === $(doc).triggerHandler('joinchat:open', [params])) return;

    // Ensure the link is safe
    if (secure_link.test(params.link)) {
      // Send analytics events
      this.send_event(params);
      // Open WhatsApp link
      win.open(params.link, 'joinchat', 'noopener');
    } else {
      console.error("Joinchat: the link doesn't seem safe, it must point to the current domain or whatsapp.com");
    }
  };

  // Triggers: launch WhatsApp on click
  $(doc).on('click', '.joinchat_open, .joinchat_app, a[href="#joinchat"], a[href="#whatsapp"]', function (e) {
    e.preventDefault();
    joinchat_obj.open_whatsapp($(this).data('phone'), $(this).data('message'));
  });

  // Gutenberg buttons add QR
  if (typeof kjua == 'function' && !joinchat_obj.is_mobile) {
    $('.joinchat-button__qr').each(function () {
      $(this).kjua({
        text: joinchat_obj.whatsapp_link($(this).data('phone'), $(this).data('message'), false),
        render: 'canvas',
        rounded: 80,
      });
    });
  } else {
    $('.wp-block-joinchat-button figure').remove();
  }

}(jQuery, window, document));
