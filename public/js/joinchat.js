((window, document, joinchat_obj) => {
  'use strict';

  // MARK: joinchat_obj
  joinchat_obj = {
    $div: null,
    settings: null,
    store: null,
    chatbox: false,
    showed_at: 0,
    is_ready: false, // Change to true when Joinchat ends initialization
    is_mobile: /Mobile|Android|iPhone|iPad/i.test(navigator.userAgent),
    can_qr: window.QrCreator && typeof QrCreator.render === 'function',
    ...joinchat_obj
  };
  window.joinchat_obj = joinchat_obj; // Save global

  // querySelector alias
  joinchat_obj.$ = function (selector) {
    return this.$div.querySelector(selector);
  };

  // querySelectorAll alias
  joinchat_obj.$$ = function (selector) {
    return this.$div.querySelectorAll(selector);
  };

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

    const data_layer = window[this.settings.data_layer] || window[window.gtm4wp_datalayer_name] || window['dataLayer'];

    if (typeof data_layer === 'object') {
      // Ensure gtag is defined
      if (typeof gtag === 'undefined') {
        window.gtag = function () { data_layer.push(arguments); };
      }

      // GA4 send recommended event "generate_lead"
      const ga4_event = this.settings.ga_event || 'generate_lead';
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

    // Send Facebook Pixel custom event
    if (typeof fbq === 'function') {
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

  // Show Joinchat button
  joinchat_obj.show = function (tooltip) {
    this.$div.removeAttribute('hidden');
    this.$div.classList.add('joinchat--show');
    if (tooltip) {
      this.$div.classList.add('joinchat--tooltip');
    }
  };

  // Hide Joinchat button
  joinchat_obj.hide = function () {
    this.$div.classList.remove('joinchat--show');
  };

  // Open Chatbox and trigger event
  joinchat_obj.chatbox_show = function () {
    if (this.chatbox) return;

    this.chatbox = true;
    this.showed_at = Date.now(); // Avoid faux clicks
    this.$div.classList.add('joinchat--chatbox');

    if (this.settings.message_badge) {
      this.$('.joinchat__badge').classList.replace('joinchat__badge--in', 'joinchat__badge--out');
    }

    document.dispatchEvent(new Event('joinchat:show'));
  };

  // Close Chatbox and trigger event
  joinchat_obj.chatbox_hide = function () {
    if (!this.chatbox) return;

    this.chatbox = false;
    this.$div.classList.remove('joinchat--chatbox', 'joinchat--tooltip');

    if (this.settings.message_badge) {
      this.$('.joinchat__badge').classList.remove('joinchat__badge--out');
    }

    document.dispatchEvent(new Event('joinchat:hide'));
  };

  // Save CTA hash
  joinchat_obj.save_hash = function () {
    if (!this.settings.message_hash) return; // No hash
    if (this.settings.message_delay < 0) return; // No delay

    let saved_hashes = (this.store.getItem('joinchat_hashes') || '').split(',').filter(Boolean);

    if (!saved_hashes.includes(this.settings.message_hash)) {
      saved_hashes.push(this.settings.message_hash);
      this.store.setItem('joinchat_hashes', saved_hashes.join(','));
    }
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

  // Opt-in needed
  joinchat_obj.need_optin = function () {
    return this.$div.classList.contains('joinchat--optout');
  };

  // QR is in use
  joinchat_obj.use_qr = function () {
    return !!this.settings.qr && this.can_qr && !this.is_mobile;
  }

  // Show Chatbox or open WhatsApp
  joinchat_obj.open = function (direct, phone, message) {
    if ((direct && !this.need_optin()) || !joinchat_obj.$('.joinchat__chatbox')) {
      if (Date.now() < joinchat_obj.showed_at + 600) return; // Avoid trigger WA on auto show chatbox
      this.save_hash();
      this.open_whatsapp(phone, message);
    } else {
      this.chatbox_show();
    }
  }

  // Close Chatbox (saving hash)
  joinchat_obj.close = function () {
    this.save_hash();
    this.chatbox_hide();
  }

  // Random text (<jc-rand><jc-opt>A</jc-opt><jc-opt>B</jc-opt>...</jc-rand>)
  joinchat_obj.rand_text = function (node) {
    node.querySelectorAll('jc-rand').forEach(rand => {
      const options = rand.children;
      rand.replaceWith(options[Math.floor(Math.random() * options.length)].innerHTML);
    });
  }

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

  // MARK: Magic
  function joinchat_magic() {
    document.dispatchEvent(new Event('joinchat:starting'));

    const button_delay = joinchat_obj.settings.button_delay * 1000;
    const chat_delay = Math.max(0, joinchat_obj.settings.message_delay * 1000);
    const has_cta = !!joinchat_obj.settings.message_hash;

    // Stored values (views counter & CTA hashes)
    const has_pageviews = parseInt(joinchat_obj.store.getItem('joinchat_views') || 1) >= joinchat_obj.settings.message_views;
    const saved_hashes = (joinchat_obj.store.getItem('joinchat_hashes') || '').split(',').filter(Boolean);
    const cta_viewed = joinchat_obj.settings.cta_viewed !== undefined ?
      joinchat_obj.settings.cta_viewed : saved_hashes.indexOf(joinchat_obj.settings.message_hash || 'none') !== -1;

    // Show button (and tooltip auto)
    const has_tooltip = !cta_viewed && (joinchat_obj.settings.message_badge || !has_cta || !chat_delay || !has_pageviews);
    setTimeout(() => joinchat_obj.show(has_tooltip), button_delay);

    const joinchatOpen = () => joinchat_obj.open(); // shortcut

    // Show badge or chatbox
    if (has_cta && !cta_viewed && chat_delay) {
      let timeout_auto_show;

      if (joinchat_obj.settings.message_badge) {
        timeout_auto_show = setTimeout(() => joinchat_obj.$('.joinchat__badge').classList.add('joinchat__badge--in'), button_delay + chat_delay);
      } else if (has_pageviews) {
        timeout_auto_show = setTimeout(joinchatOpen, button_delay + chat_delay);
      }
      document.addEventListener('joinchat:show', () => clearTimeout(timeout_auto_show), { once: true });
    }

    const jc_button = joinchat_obj.$('.joinchat__button');

    // Open Chatbox on mouse over
    if (!joinchat_obj.is_mobile) {
      let timeout_on_hover;
      jc_button.addEventListener('mouseenter', () => { if (joinchat_obj.$('.joinchat__chatbox')) timeout_on_hover = setTimeout(joinchatOpen, 1500); });
      jc_button.addEventListener('mouseleave', () => { clearTimeout(timeout_on_hover); });
    }

    // Open|close Chatbox on click
    jc_button.addEventListener('click', joinchatOpen);
    joinchat_obj.$('.joinchat__open')?.addEventListener('click', () => joinchat_obj.open(true));
    joinchat_obj.$('.joinchat__close')?.addEventListener('click', () => joinchat_obj.close());

    // Opt-in toggle
    joinchat_obj.$('#joinchat_optin')?.addEventListener('change', e => joinchat_obj.$div.classList.toggle('joinchat--optout', !e.target.checked));

    // Only scroll Joinchat message box (no all body)
    joinchat_obj.$('.joinchat__scroll')?.addEventListener('wheel', function (e) {
      e.preventDefault();
      this.scrollTop += e.deltaY;
    }, { passive: false });

    // Mobile enhancements
    if (joinchat_obj.is_mobile) {
      let timeout_kb, timeout_resize;

      const toggleOnFormFocus = () => {
        const type = (document.activeElement.type || '').toLowerCase();

        if ([
          'date',
          'datetime',
          'email',
          'month',
          'number',
          'password',
          'search',
          'tel',
          'text',
          'textarea',
          'time',
          'url',
          'week',
        ].includes(type)) {
          if (joinchat_obj.chatbox) {
            joinchat_obj.chatbox_hide();
            setTimeout(() => joinchat_obj.hide(), 400);
          } else {
            joinchat_obj.hide();
          }
        } else {
          joinchat_obj.show();
        }
      }

      // Hide on mobile when virtual keyboard is open (on fill forms)
      ['focusin', 'focusout'].forEach(event => document.addEventListener(event, e => {
        if (e.target.matches('input, textarea') && !joinchat_obj.$div.contains(e.target)) {
          clearTimeout(timeout_kb);
          timeout_kb = setTimeout(toggleOnFormFocus, 200);
        }
      }));

      // Ensure header is visible
      window.addEventListener('resize', () => {
        clearTimeout(timeout_resize);
        timeout_resize = setTimeout(() => { joinchat_obj.$div.style.setProperty('--vh', `${window.innerHeight}px`); }, 200);
      });
      window.dispatchEvent(new Event('resize'));
    }

    // Add QR Code
    if (joinchat_obj.use_qr()) {
      joinchat_obj.$('.joinchat__qr').appendChild(joinchat_obj.qr(joinchat_obj.get_wa_link(undefined, undefined, false)));
    } else {
      joinchat_obj.$('.joinchat__qr')?.remove();
    }

    // Count visits (if needed)
    if (chat_delay && !has_pageviews) {
      joinchat_obj.store.setItem('joinchat_views', parseInt(joinchat_obj.store.getItem('joinchat_views') || 0) + 1);
    }

    // On first show
    document.addEventListener('joinchat:show', () => {
      const jc_scroll = joinchat_obj.$('.joinchat__scroll');
      const jc_chat = joinchat_obj.$('.joinchat__chat');
      const jc_bubbles = joinchat_obj.$$('.joinchat__bubble');

      if (!jc_chat) return;

      // Random text
      if (has_cta) joinchat_obj.rand_text(jc_chat);

      // Bubbles animated (show one by one)
      if (jc_bubbles.length <= 1 || window.matchMedia('(prefers-reduced-motion)').matches) {
        setTimeout(() => jc_chat.dispatchEvent(new Event('joinchat:bubbles')), 1); // Need delay (to trigger after joinchat:show)
        return;
      }

      jc_bubbles.forEach(bubble => bubble.classList.add('joinchat--hidden'));
      joinchat_obj.$('.joinchat__optin')?.classList.add('joinchat--hidden');

      let index = 0;
      const random = (min, max) => Math.round(Math.random() * (max - min) + min);
      const showBubble = (bubble, next_delay) => {
        joinchat_obj.$('.joinchat__bubble--loading')?.remove();
        bubble.classList.remove('joinchat--hidden');
        jc_scroll.scrollTop = jc_scroll.scrollHeight;
        setTimeout(nextBubble, next_delay);
      }
      const nextBubble = () => {
        if (index >= jc_bubbles.length) {
          joinchat_obj.$('.joinchat__optin')?.classList.remove('joinchat--hidden');
          jc_chat.dispatchEvent(new Event('joinchat:bubbles')); // All bubbles shown
          return;
        }

        const bubble = jc_bubbles[index++];
        if (bubble.classList.contains('joinchat__bubble--note')) {
          showBubble(bubble, 100);
        } else {
          jc_chat.insertAdjacentHTML('beforeend', '<div class="joinchat__bubble joinchat__bubble--loading"></div>');
          jc_scroll.scrollTop = jc_scroll.scrollHeight;
          setTimeout(() => showBubble(bubble, random(400, 600)), (bubble.textContent.split(/\s+/).length * 60) + random(100, 200)); // Delay (word count * time) + random delay
        }
      };
      nextBubble();
    }, { once: true });

    // MARK: Triggers

    // TRIGGERS: open chatbox on load if query or anchor "joinchat" exists
    const location_url = new URL(window.location);
    if (location_url.hash === '#joinchat' || location_url.searchParams.has('joinchat')) {
      const query_delay = (parseInt(location_url.searchParams.get('joinchat')) || 0) * 1000;
      setTimeout(() => joinchat_obj.show(), query_delay);
      setTimeout(() => joinchat_obj.chatbox_show(), query_delay + 700); // 500ms animation + 200ms extra delay
    }

    // TRIGGERS: open chatbox or launch WhatsApp on click
    document.addEventListener('click', e => {
      if (!e.target.closest('.joinchat_open, .joinchat_app, a[href="#joinchat"], a[href="#whatsapp"]')) return;
      e.preventDefault();
      const direct = !!e.target.closest('.joinchat_app, a[href="#whatsapp"]');
      joinchat_obj.open(direct, e.target.dataset.phone, e.target.dataset.message);
    });

    // TRIGGERS: close chatbox when click on nodes with class "joinchat_close"
    document.addEventListener('click', e => {
      if (!e.target.closest('.joinchat_close')) return;
      e.preventDefault();
      joinchat_obj.close();
    });

    // TRIGGERS: open chatbox on scroll (when node on viewport)
    const show_on_scroll = document.querySelectorAll('.joinchat_show, .joinchat_force_show');
    if (has_cta && show_on_scroll && 'IntersectionObserver' in window) {
      const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (entry.intersectionRatio <= 0) return;
          if (cta_viewed && !entry.target.classList.contains('joinchat_force_show')) return;

          observer.disconnect(); // Only one show per visit
          joinchatOpen();
        });
      });
      show_on_scroll.forEach(element => observer.observe(element));
    }

    joinchat_obj.is_ready = true;
    document.dispatchEvent(new Event('joinchat:start'));
  }


  // MARK: Page Ready
  const on_page_ready = () => {
    joinchat_obj.$div = document.querySelector('.joinchat');

    // Exit if no joinchat div
    if (!joinchat_obj.$div) return;

    joinchat_obj.settings = JSON.parse(joinchat_obj.$div.dataset.settings);

    // Fallback if localStorage not supported (iOS incognito)
    // Implements functional storage in memory and will not persist between page loads
    try {
      localStorage.test = 2;
      joinchat_obj.store = localStorage;
    } catch (e) {
      joinchat_obj.store = {
        _data: {},
        setItem: function (id, val) { this._data[id] = String(val); },
        getItem: function (id) { return this._data.hasOwnProperty(id) ? this._data[id] : null; }
      };
    }

    // Only works if joinchat is defined
    if (!!joinchat_obj.settings && !!joinchat_obj.settings.telephone) {
      if (joinchat_obj.is_mobile || !joinchat_obj.settings.mobile_only) {
        joinchat_magic();
      } else {
        // Ensure don't show
        joinchat_obj.hide();

        // TRIGGERS: launch WhatsApp on click
        document.addEventListener('click', e => {
          if (!e.target.closest('.joinchat_open, .joinchat_app, a[href="#joinchat"], a[href="#whatsapp"]')) return;
          e.preventDefault();
          joinchat_obj.open_whatsapp(e.target.dataset.phone, e.target.dataset.message);
        });
      }

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
          joinchat_obj.$$('.joinchat__chat jc-sku').forEach(e => e.textContent = sku);
          joinchat_obj.settings.message_send = message.replace(/<jc-sku>.*<\/jc-sku>/g, sku);
        });
      }
    }
  }

  // Ready!!
  if (document.readyState !== 'loading') on_page_ready();
  else document.addEventListener('DOMContentLoaded', on_page_ready);

})(window, document, window.joinchat_obj || {});
