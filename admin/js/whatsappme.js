(function ($) {
  'use strict';

  $(function () {
    if (typeof (intlTelInput) === 'function') {
      var country_request = JSON.parse(localStorage.whatsappme_country_code || '{}');
      var country_code = (country_request.code && country_request.date == new Date().toDateString()) ? country_request.code : false;
      var $phone = $("#whatsappme_phone");
      var iti = intlTelInput($phone.get(0), {
        hiddenInput: $phone.data("name") || "whatsappme[telephone]",
        initialCountry: "auto",
        preferredCountries: [country_code || ''],
        geoIpLookup: function (callback) {
          if (country_code) {
            callback(country_code);
          } else {
            $.getJSON('https://ipinfo.io').always(function (resp) {
              var countryCode = (resp && resp.country) ? resp.country : "";
              localStorage.whatsappme_country_code = JSON.stringify({ code: countryCode, date: new Date().toDateString() });
              callback(countryCode);
            });
          }
        },
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/15.0.1/js/utils.js"
      });

      $phone.on("keyup change", function () {
        $phone.css('border-color', '');
        // Ensures number it's updated on AJAX save (Gutemberg)
        iti.hiddenInput.value = iti.getNumber();
      });
      $phone.on("blur", function () {
        $phone.css('border-color', $phone.val().trim() && !iti.isValidNumber() ? '#ff0000' : '');
      });
    }

    function propagate_inheritance(field, show) {
      field = field || 'all';
      show = show || $('input[name="whatsappme[view][' + field + ']"]:checked').val();

      $('.view_inheritance_' + field)
        .toggleClass('dashicons-visibility', show == 'yes')
        .toggleClass('dashicons-hidden', show == 'no');

      if (field == 'cpts') {
        $('[class*=view_inheritance_cpt_]')
          .toggleClass('dashicons-visibility', show == 'yes')
          .toggleClass('dashicons-hidden', show == 'no');
      } else if (field in inheritance) {
        var value = $('input[name="whatsappme[view][' + field + ']"]:checked').val();
        value = value === '' ? show : value;

        $.each(inheritance[field], function () { propagate_inheritance(this, value); });
      }
    }

    if ($('#whatsappme_form').length === 1) {
      $('.nav-tab').click(function (e) {
        var $tab = $(this);
        e.preventDefault();

        $('.nav-tab').removeClass('nav-tab-active');
        $tab.addClass('nav-tab-active').blur();
        $('.tab').removeClass('tab-active');
        $($tab.attr('href')).addClass('tab-active');
      });

      $('#whatsappme_mobile_only').change(function () {
        $('#whatsappme_whatsapp_web').closest('tr').toggleClass('hide-if-js', this.checked);
      }).change();

      var $tab_advanced = $('#tab-advanced');
      var inheritance = {
        'all': ['front_page', 'blog_page', '404_page', 'search', 'archive', 'singular', 'woocommerce', 'cpts'],
        'archive': ['date', 'author'],
        'singular': ['page', 'post'],
        'woocommerce': ['product', 'cart', 'checkout', 'account_page']
      };

      $('input', $tab_advanced).change(function () {
        propagate_inheritance();
      });

      $('.whatsappme_view_reset').click(function (e) {
        e.preventDefault();
        $('input[value=""]', $tab_advanced).prop('checked', true);
        $('.whatsappme_view_all input', $tab_advanced).first().prop('checked', true);
        propagate_inheritance();
      });

      propagate_inheritance();
    }
  });
})(jQuery);
