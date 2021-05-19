(function ($) {
  'use strict';

  // Compatibility with old addons (to be removed)
  window.intl_tel_input_version = window.intlTelConf && intlTelConf.version;

  function textarea_autoheight() {
    $(this).height(0).height(this.scrollHeight);
  }

  $(function () {
    var media_frame;

    if (typeof (intlTelInput) === 'function' && $('#joinchat_phone').length) {
      var country_request = JSON.parse(localStorage.joinchat_country_code || '{}');
      var country_code = (country_request.code && country_request.date == new Date().toDateString()) ? country_request.code : false;
      var $phone = $('#joinchat_phone');

      // If empty value capture placeholder and remove
      var placeholder = $phone.val() === '' ? $phone.attr('placeholder') : null;
      $phone.removeAttr('placeholder');

      var iti = intlTelInput($phone.get(0), {
        hiddenInput: $phone.data('name') || 'joinchat[telephone]',
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

      $phone.on('input', function () {
        var $this = $(this);
        var iti = intlTelInputGlobals.getInstance(this);

        $this.css('color', $this.val().trim() && !iti.isValidNumber() ? '#ca4a1f' : '');
        // Ensures number it's updated on AJAX save (Gutemberg)
        iti.hiddenInput.value = iti.getNumber();
      }).on('blur', function () {
        var iti = intlTelInputGlobals.getInstance(this);
        iti.setNumber(iti.getNumber());
      });
    }

    if ($('#joinchat_form').length === 1) {
      // Tabs
      $('.nav-tab').on('click', function (e) {
        e.preventDefault();
        var $navtab = $(this);
        var href = $navtab.attr('href');
        var $referer = $('input[name=_wp_http_referer]');
        var ref_val = $referer.val();

        // Update form referer to open same tab on submit
        $referer.val(ref_val.substr(0, ref_val.indexOf('page=joinchat')) + 'page=joinchat&tab=' + href.substr(14));

        $('.nav-tab').removeClass('nav-tab-active').attr('aria-selected', 'false');
        $navtab.addClass('nav-tab-active').attr('aria-selected', 'true').get(0).blur();
        $('.joinchat-tab').removeClass('joinchat-tab-active');
        $(href).addClass('joinchat-tab-active').find('textarea').each(textarea_autoheight);
      });

      // Toggle WhatsApp web option
      $('#joinchat_mobile_only').on('change', function () {
        $('#joinchat_whatsapp_web').closest('tr').toggleClass('joinchat-hidden', this.checked);
      }).trigger('change');

      // Toggle badge option
      $('#joinchat_message_delay').on('change input', function () {
        $('#joinchat_message_badge, #joinchat_message_views').closest('tr').toggleClass('joinchat-hidden', this.value == '0');
      }).trigger('change');

      // Show help
      $('.joinchat-show-help').on('click', function (e) {
        e.preventDefault();
        var help_tab = $(this).attr('href');
        if ($('#contextual-help-wrap').is(':visible')) {
          $("html, body").animate({ scrollTop: 0 });
        } else {
          $('#contextual-help-link').trigger('click');
        }
        $(help_tab != '#' ? help_tab : '#tab-link-styles-and-vars').find('a').trigger('click');
      });

      // Texarea focus and auto height
      $('textarea', '#joinchat_form')
        .on('focus', function () { $(this).closest('tr').addClass('joinchat--focus'); })
        .on('blur', function () { $(this).closest('tr').removeClass('joinchat--focus'); })
        .on('input', textarea_autoheight)
        .each(textarea_autoheight);


      // Visibility view inheritance
      var $tab_visibility = $('#joinchat_tab_visibility');
      var inheritance = $('.joinchat_view_all').data('inheritance') || {
        'all': ['front_page', 'blog_page', '404_page', 'search', 'archive', 'singular', 'cpts'],
        'archive': ['date', 'author'],
        'singular': ['page', 'post'],
      };

      function propagate_inheritance(field, show) {
        field = field || 'all';
        show = show || $('input[name="joinchat[view][' + field + ']"]:checked').val();

        $('.view_inheritance_' + field)
          .toggleClass('dashicons-visibility', show == 'yes')
          .toggleClass('dashicons-hidden', show == 'no');

        if (field == 'cpts') {
          $('[class*=view_inheritance_cpt_]')
            .toggleClass('dashicons-visibility', show == 'yes')
            .toggleClass('dashicons-hidden', show == 'no');
        } else if (field in inheritance) {
          var value = $('input[name="joinchat[view][' + field + ']"]:checked').val();
          value = value === '' ? show : value;

          $.each(inheritance[field], function () { propagate_inheritance(this, value); });
        }
      }

      $('input', $tab_visibility).on('change', function () {
        propagate_inheritance();
      });

      $('.joinchat_view_reset').on('click', function (e) {
        e.preventDefault();
        $('input[value=""]', $tab_visibility).prop('checked', true);
        $('.joinchat_view_all input', $tab_visibility).first().prop('checked', true);
        propagate_inheritance();
      });

      propagate_inheritance();

      $('#joinchat_button_image_add').on('click', function (e) {
        e.preventDefault();

        if (!media_frame) {
          // Define media_frame as wp.media object
          media_frame = wp.media({
            title: $(this).data('title') || 'Select button image',
            button: { text: $(this).data('button') || 'Use Image' },
            library: { type: 'image' },
            multiple: false,
          });

          // When an image is selected in the media library...
          media_frame.on('select', function () {
            // Get media attachment details from the frame state
            var attachment = media_frame.state().get('selection').first().toJSON();
            var url = attachment.sizes && attachment.sizes.thumbnail && attachment.sizes.thumbnail.url || attachment.url;

            $('#joinchat_button_image_holder').css({ 'background-size': 'cover', 'background-image': 'url(' + url + ')' });
            $('#joinchat_button_image').val(attachment.id);
            $('#joinchat_button_image_remove').removeClass('joinchat-hidden');
          });

          media_frame.on('open', function () {
            // Pre-selected attachment
            var attachment = wp.media.attachment($('#joinchat_button_image').val());
            media_frame.state().get('selection').add(attachment ? [attachment] : []);
          });
        }

        media_frame.open();
      });

      $('#joinchat_button_image_remove').on('click', function (e) {
        e.preventDefault();

        $('#joinchat_button_image_holder').removeAttr('style');
        $('#joinchat_button_image').val('');
        $(this).addClass('joinchat-hidden');
      });

      $('#joinchat_color').wpColorPicker();

      $('#joinchat_header_custom').on('click', function () {
        $(this).prev().find('input').prop('checked', true);
      });
    }

    if ($('.joinchat-metabox').length === 1) {
      // Texarea auto height
      $('textarea', '.joinchat-metabox').on('focus input', textarea_autoheight).each(textarea_autoheight);
    }
  });
})(jQuery);
