(function ($, window, document, joinchat_obj) {
  'use strict';

  joinchat_obj = $.extend({
    settings: null,
    is_mobile: !!navigator.userAgent.match(/Android|iPhone|BlackBerry|IEMobile|Opera Mini/i),
    can_qr: window.QrCreator && typeof QrCreator.render == 'function',
  }, joinchat_obj);
  window.joinchat_obj = joinchat_obj; // Save global

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

  // Generate QR canvas
  joinchat_obj.qr = function (text, options) {
    var canvas = document.createElement('CANVAS');
    QrCreator.render($.extend({
      text: text,
      radius: 0.4,
      background: '#FFF',
      size: 200,
    }, joinchat_obj.settings.qr || {}, options || {}), canvas);
    return canvas;
  }

  // Triggers: launch WhatsApp on click
  $(document).on('click', '.joinchat_open, .joinchat_app, a[href="#joinchat"], a[href="#whatsapp"]', function (e) {
    e.preventDefault();
    joinchat_obj.open_whatsapp($(this).data('phone'), $(this).data('message'));
  });

  // Gutenberg buttons add QR
  if (joinchat_obj.can_qr && !joinchat_obj.is_mobile) {
    $('.joinchat-button__qr').each(function () {
      $(this).append(joinchat_obj.qr(joinchat_obj.whatsapp_link($(this).data('phone'), $(this).data('message'), false)));
    });
  } else {
    $('.wp-block-joinchat-button figure').remove();
  }

  // Replace product variable SKU
  if (!!joinchat_obj.settings && joinchat_obj.settings.sku !== undefined) {
    var message = joinchat_obj.settings.message_send;
    $('form.variations_form').on('found_variation reset_data', function (e, variation) {
      var sku = variation && variation.sku || joinchat_obj.settings.sku;
      joinchat_obj.settings.message_send = message.replace(/<sku>.*<\/sku>/g, sku);
    });
  }

}(jQuery, window, document, window.joinchat_obj || {}));
