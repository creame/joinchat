(function ($) {
  'use strict';

  function textarea_autoheight() {
    $(this).height(0).height(this.scrollHeight);
  }

  $(function () {
    var has_iti = typeof intlTelInput === 'function';

    if (has_iti && $('#joinchat_phone').length) {
      var country_request = JSON.parse(localStorage.joinchat_country_code || '{}');
      var country_code = (country_request.code && country_request.date == new Date().toDateString()) ? country_request.code : false;
      var $phone = $('#joinchat_phone');

      // If empty value capture placeholder and remove
      var placeholder = $phone.val() === '' ? $phone.attr('placeholder') : null;
      $phone.removeAttr('placeholder');

      var iti = intlTelInput($phone[0], {
        hiddenInput: $phone.data('name') || 'joinchat[telephone]',
        separateDialCode: true,
        initialCountry: 'auto',
        preferredCountries: [country_code || ''],
        geoIpLookup: function (callback) {
          if (country_code) {
            callback(country_code);
          } else {
            $.getJSON('https://ipinfo.io').always(function (resp) {
              var countryCode = (resp && resp.country) ? resp.country : '';
              localStorage.joinchat_country_code = JSON.stringify({ code: countryCode, date: new Date().toDateString() });
              callback(countryCode);
            });
          }
        },
        customPlaceholder: function (placeholder) { return intlTelConf.placeholder + ' ' + placeholder; },
        utilsScript: intlTelConf.utils_js,
      });
      // Ensures store current value
      iti.hiddenInput.value = $phone.val();

      // Post metabox if empty value set placeholder from general settings
      if (typeof placeholder == 'string' && placeholder != '') {
        iti.promise.then(function () {
          iti.setNumber(placeholder);
          $phone.attr('placeholder', iti.getNumber(intlTelInputUtils.numberFormat.NATIONAL)).val('');
        });
      }

      $phone.on('input countrychange', function () {
        var $this = $(this);
        var iti = intlTelInputGlobals.getInstance(this);

        $this.css('color', $this.val().trim() && !iti.isValidNumber() ? '#ca4a1f' : '');
        // Ensures number it's updated on AJAX save (Gutemberg)
        iti.hiddenInput.value = iti.getNumber();
        // Enable/disable phone test
        $('#joinchat_phone_test').attr('disabled', !iti.isValidNumber());
      }).on('blur', function () {
        var iti = intlTelInputGlobals.getInstance(this);
        iti.setNumber(iti.getNumber());
      });
    }

    if ($('.joinchat-metabox').length) {
      // Texarea auto height
      $('.joinchat-metabox textarea').on('focus input', textarea_autoheight).each(textarea_autoheight);
    }
  });
})(jQuery);
