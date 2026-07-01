(function (window) {
  'use strict';

  function has_iti() {
    return typeof intlTelInput === 'function' && !!window.joinchat_iti;
  }

  function get_stored_country_code() {
    const country_request = JSON.parse(localStorage.joinchat_country_code || '{}');

    return (country_request.code && country_request.date == new Date().toDateString()) ? country_request.code.toLowerCase() : '';
  }

  function store_country_code(code) {
    if (!code) return;

    localStorage.joinchat_country_code = JSON.stringify({ code: code.toLowerCase(), date: new Date().toDateString() });
  }

  async function lookup_country_code() {
    try {
      const res = await fetch('https://ipapi.co/json');
      const data = await res.json();
      const code = (data.country_code || '').toLowerCase();

      store_country_code(code);

      return code;
    } catch (_) {
      return '';
    }
  }

  function load_translations() {
    const ui_trans = window.joinchat_iti_l10n || {};
    const trans = {
      placeholder: ui_trans.placeholder || 'e.g.',
      ...ui_trans,
    };

    if (ui_trans.searchSummaryAria && typeof ui_trans.searchSummaryAria === 'object') {
      const aria_msgs = ui_trans.searchSummaryAria;
      trans.searchSummaryAria = function (count) {
        if (count === 0) return aria_msgs.zero || 'No results found';
        if (count === 1) return aria_msgs.one || '1 result found';
        return (aria_msgs.multiple || '${count} results found').replace('${count}', count);
      };
    }

    return trans;
  }

  function get_instance(iti_or_input) {
    try {
      if (!iti_or_input) return null;

      const is_input = iti_or_input.nodeType === 1 && iti_or_input.tagName === 'INPUT';

      return is_input ? intlTelInput.getInstance(iti_or_input) : iti_or_input;
    } catch (_) {
      return null;
    }
  }

  /**
   * Initialize the intl-tel-input instance
   *
   * @param {HTMLInputElement} input - The input element to initialize
   * @param {Object} custom_settings - Custom settings for intl-tel-input
   * @param {Object} extra - Extra options for event handling
   * @returns {Object|null} - The initialized intl-tel-input instance or null if initialization fails
   */
  function init(input, custom_settings, extra) {
    if (!input || !has_iti()) return null;

    const options = extra || {};
    const config = build_config(input, custom_settings || {});

    try {
      const iti = intlTelInput(input, config);

      const handle_change = function () {
        const is_valid = is_valid_number(iti);

        if (options.toggleColor !== false) {
          this.style.color = this.value.trim() && !is_valid ? '#ca4a1f' : '';
        }

        if (options.syncHiddenInput !== false) {
          sync_hidden_input(this, iti, config.hiddenInputs().phone);
        }

        if (typeof options.onChange === 'function') {
          options.onChange.call(this, iti, is_valid);
        }
      };

      input.addEventListener('input', handle_change);
      input.addEventListener('countrychange', handle_change);

      iti.promise.then(() => { input.dispatchEvent(new Event('input')); });

      return iti;
    } catch (_) {
      return null;
    }
  }

  /**
   * Build the configuration object for intl-tel-input
   *
   * @param {HTMLInputElement} input - The input element to initialize
   * @param {Object} extra - Extra options for configuration
   * @returns {Object} - The configuration object for intl-tel-input
   */
  function build_config(input, extra) {
    const options = extra || {};
    const country_code = get_stored_country_code();
    const ui_l10n = load_translations();
    const hidden_name = options.hiddenInputName || input?.dataset?.name || 'joinchat[telephone]';
    const config = {
      hiddenInputs: () => ({ phone: hidden_name }),
      initialCountry: country_code || '',
      initialCountryLookup: country_code ? null : lookup_country_code,
      countryNameLocale: ui_l10n.countryNameLocale || 'en',
      uiTranslations: ui_l10n,
      strictMode: true,
      separateDialCode: true,
      allowedNumberTypes: ['MOBILE'],
      customPlaceholder: (exampleNumber, selectedCountry) => `${ui_l10n.placeholder} ${exampleNumber}`,
    };

    if (options.customPlaceholder) {
      config.customPlaceholder = (country_ph) => options.customPlaceholder(country_ph, ui_l10n);
    }

    if (options.allowedNumberTypes) {
      config.allowedNumberTypes = options.allowedNumberTypes;
    }

    // Merge any additional configuration options provided in the extra parameter
    if (options.config) {
      Object.assign(config, options.config);
    }

    return config;
  }

  function get_number(iti_or_input) {
    const iti = get_instance(iti_or_input);
    if (!iti) return '';

    try {
      return iti.getNumber();
    } catch (_) {
      return '';
    }
  }

  function is_valid_number(iti_or_input) {
    const iti = get_instance(iti_or_input);
    if (!iti) return false;

    try {
      return iti.isValidNumber();
    } catch (_) {
      return false;
    }
  }

  function destroy_instance(iti_or_input) {
    const iti = get_instance(iti_or_input);
    if (!iti) return;

    try {
      iti.destroy();
    } catch (_) {
      // ignore
    }
  }

  function sync_hidden_input(input, iti, hidden_name) {
    const hidden_input = input.form ? input.form.querySelector(`input[type="hidden"][name="${hidden_name}"]`) : null;

    if (hidden_input) hidden_input.value = get_number(iti);
  }

  window.joinchat_iti = {
    hasITI: has_iti,
    init: init,
    getInstance: get_instance,
    destroy: destroy_instance,
    getNumber: get_number,
    isValidNumber: is_valid_number,
    getStoredCountryCode: get_stored_country_code,
  };
}(window));