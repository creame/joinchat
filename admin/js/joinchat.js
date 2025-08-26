(function ($, window) {
  'use strict';

  function textarea_autoheight() {
    $(this).height(0).height(this.scrollHeight);
  }

  $(function () {
    var has_iti = typeof intlTelInput === 'function' && window.intl_tel_l10n;
    var $phone = $('#joinchat_phone');

    if (has_iti) {
      // Set intlTelInput config (make global)
      var country_request = JSON.parse(localStorage.joinchat_country_code || '{}');
      var country_code = (country_request.code && country_request.date == new Date().toDateString()) ? country_request.code : false;

      // Capture placeholder (global settings phone)
      var global_phone = $phone.attr('placeholder') || '';

      window.joinchat_intl_tel_config = {
        hiddenInput: () => { return { phone: $phone.data('name') || 'joinchat[telephone]' }; },
        strictMode: true,
        separateDialCode: true,
        initialCountry: country_code || 'auto',
        geoIpLookup: country_code ? null : (success, failure) => {
          fetch("https://ipapi.co/json")
            .then((res) => res.json())
            .then((data) => {
              localStorage.joinchat_country_code = JSON.stringify({ code: data.country_code, date: new Date().toDateString() });
              success(data.country_code);
            }).catch(() => failure());
        },
        autoPlaceholder: 'aggressive',
        customPlaceholder: (country_ph) => global_phone || `${intl_tel_l10n.placeholder} ${country_ph}`,
        i18n: intl_tel_l10n,
      };

      // Apply intlTelInput to phone input
      if ($phone.length) {
        var iti = intlTelInput($phone[0], joinchat_intl_tel_config);
        // Placeholder phone format and reset to initial value
        iti.promise.then(() => {
          if (global_phone === '') return;

          const phone = $phone.val();
          iti.setNumber(global_phone);
          global_phone = iti.getNumber(intlTelInput.utils.numberFormat.NATIONAL);
          iti.setNumber(phone);
          iti.setPlaceholderNumberType("MOBILE"); // Trigger placeholder update
        });

        $phone.on('open:countrydropdown', () => { global_phone = null; });
        $phone.on('input countrychange', function () {
          $(this).css('color', this.value.trim() && !iti.isValidNumber(true) ? '#ca4a1f' : '');
          // Ensures number it's updated on AJAX save (Gutemberg)
          iti.hiddenInput.value = iti.getNumber();
        });
      }
    }

    if ($('.joinchat-metabox').length) {
      // Texarea auto height
      $('.joinchat-metabox textarea').on('focus input', textarea_autoheight).each(textarea_autoheight);
    }
  });
})(jQuery, window);
