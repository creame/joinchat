((window, document, joinchat_obj) => {
  'use strict';

  joinchat_obj = {
    settings: null,
    is_mobile: /Mobile|Android|iPhone|iPad/i.test(navigator.userAgent),
    can_qr: window.QrCreator && typeof QrCreator.render === 'function',
    ...joinchat_obj
  };
  window.joinchat_obj = joinchat_obj; // Save global

  /**
   * Trigger Analytics events
   *
   * Available customizations via joinchat_obj.settings:
   *  - 'data_layer' for custom data layer name (default 'dataLayer' or GTM4WP custom DataLayer name)
   *  - 'ga_event'   for GA4 custom event       (default 'generate_lead' recommended event)
   *
   * All params can be edited with document event 'joinchat:event' or cancel if returns false.
   * e.g.: $(document).on('joinchat:event', function(){ return false; });
   *
   */
  joinchat_obj.send_event = function (params) {
    params = {
      event_category: 'JoinChat', // Name
      event_label: '',            // Destination url
      event_action: '',           // "chanel: id"
      chat_channel: 'whatsapp',   // Channel name
      chat_id: '--',              // Channel contact (phone, username...)
      is_mobile: this.is_mobile ? 'yes' : 'no',
      page_location: location.href,
      page_title: document.title || 'no title',
      ...params
    };
    params.event_label = params.event_label || params.link || '';
    params.event_action = params.event_action || `${params.chat_channel}: ${params.chat_id}`;
    delete params.link;

    // Trigger event (params can be edited by third party scripts or cancel if return false)
    if (!document.dispatchEvent(new CustomEvent('joinchat:event', { detail: params, cancelable: true }))) return;

    const data_layer = window[this.settings.data_layer] || window[window.gtm4wp_datalayer_name] || window.dataLayer;

    if (typeof data_layer === 'object') {
      const gtag = window.gtag || function () { data_layer.push(arguments); };

      // GA4 send recommended event "generate_lead"
      const ga4_event = this.settings.ga_event !== undefined ? this.settings.ga_event : 'generate_lead';

      if (ga4_event) {
        const ga4_params = { transport_type: 'beacon', ...params };
        // GA4 params max_length (https://support.google.com/analytics/answer/9234069 https://support.google.com/analytics/answer/9267744)
        Object.keys(ga4_params).forEach(k => {
          if (k === 'page_location') ga4_params[k] = ga4_params[k].substring(0, 1000);
          else if (k === 'page_referrer') ga4_params[k] = ga4_params[k].substring(0, 420);
          else if (k === 'page_title') ga4_params[k] = ga4_params[k].substring(0, 300);
          else if (typeof ga4_params[k] === 'string') ga4_params[k] = ga4_params[k].substring(0, 100);
        });

        const ga4_tags = [];
        const ga4_send = tag => {
          if (ga4_tags.includes(tag)) return;
          if (tag.startsWith('G-') || tag.startsWith('GT-')) {
            ga4_tags.push(tag);
            gtag('event', ga4_event, { send_to: tag, ...ga4_params }); // Send GA4 event
          }
        };

        // gtag.js (New "Google Tag" find destinations)
        if (window.google_tag_data && google_tag_data.tidr && !!google_tag_data.tidr.destination) {
          for (const tag in google_tag_data.tidr.destination) ga4_send(tag);
        }
        // gtag.js (Old method, traverse dataLayer and find 'config')
        data_layer.forEach(item => {
          if (item[0] === 'config' && item[1]) ga4_send(item[1]);
        });
      }

      // Send Google Ads conversion
      if (this.settings.gads) {
        gtag('event', 'conversion', { send_to: this.settings.gads });
      }
    }

    // Store category and delete from params
    const event_category = params.event_category;
    delete params.event_category;

    // Send Google Tag Manager custom event
    if (typeof data_layer === 'object') {
      data_layer.push({ event: event_category, ...params });
    }

    // Send Facebook Pixel custom event (mask phone)
    if (typeof fbq === 'function') {
      if (params.chat_channel === 'whatsapp') {
        const phone = params.chat_id;
        const masked = `${phone.substring(0, 3)}${'X'.repeat(phone.length - 5)}${phone.substring(phone.length - 2)}`;

        params.chat_id = masked;
        params.event_label = params.event_label.replace(phone, masked);
        params.event_action = params.event_action.replace(phone, masked);
      }

      fbq('trackCustom', event_category, params);
    }
  };

  // Return WhatsApp link with optional message
  joinchat_obj.get_wa_link = function (phone, message, wa_web) {
    message = message !== undefined ? message : this.settings.message_send || '';
    wa_web = wa_web !== undefined ? wa_web : this.settings.whatsapp_web && !this.is_mobile;

    const url = new URL(`${wa_web ? 'https://web.whatsapp.com/send?phone=' : 'https://wa.me/'}${phone || this.settings.telephone}`);
    if (message) url.searchParams.set('text', message);

    return url.toString();
  };

  // Open WhatsApp link with supplied phone and message or with settings defaults
  joinchat_obj.open_whatsapp = function (phone, message) {
    phone = phone || this.settings.telephone;
    message = message !== undefined ? message : this.settings.message_send || '';

    let params = {
      link: this.get_wa_link(phone, message),
      chat_channel: 'whatsapp',
      chat_id: phone,
      chat_message: message,
    };

    // Trigger event (params can be edited by third party scripts or cancel if return false)
    if (!document.dispatchEvent(new CustomEvent('joinchat:open', { detail: params, cancelable: true }))) return;

    // Send analytics events
    this.send_event(params);
    // Open WhatsApp link
    window.open(params.link, 'joinchat', 'noopener');
  };

  // Generate QR canvas
  joinchat_obj.qr = function (text, options) {
    const canvas = document.createElement('CANVAS');
    QrCreator.render(Object.assign({
      text: text,
      radius: 0.4,
      background: '#FFF',
      size: 200 * (window.devicePixelRatio || 1),
    }, this.settings.qr || {}, options || {}), canvas);
    return canvas;
  }

  const on_page_ready = () => {
    // TRIGGERS: launch WhatsApp on click
    document.addEventListener('click', function (e) {
      if (e.target.closest('.joinchat_open, .joinchat_app, a[href="#joinchat"], a[href="#whatsapp"]')) {
        e.preventDefault();
        joinchat_obj.open_whatsapp(e.target.dataset.phone, e.target.dataset.message);
      }
    });

    // Gutenberg buttons add QR
    if (joinchat_obj.can_qr && !joinchat_obj.is_mobile) {
      document.querySelectorAll('.joinchat-button__qr').forEach(el => el.appendChild(joinchat_obj.qr(joinchat_obj.get_wa_link(el.dataset.phone, el.dataset.message, false))));
    } else {
      document.querySelectorAll('.wp-block-joinchat-button figure').forEach(el => el.remove());
    }

    // Replace product variable SKU (requires jQuery)
    if (joinchat_obj.settings.sku !== undefined && typeof jQuery === 'function') {
      const message = joinchat_obj.settings.message_send;
      jQuery('form.variations_form').on('found_variation reset_data', function (e, variation) {
        const sku = variation && variation.sku || joinchat_obj.settings.sku;
        joinchat_obj.settings.message_send = message.replace(/<jc-sku>.*<\/jc-sku>/g, sku);
      });
    }
  }

  // Ready!!
  if (document.readyState !== 'loading') on_page_ready();
  else document.addEventListener('DOMContentLoaded', on_page_ready);

})(window, document, window.joinchat_obj || {});
