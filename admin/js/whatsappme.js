(function ($) {
  'use strict';

  function textarea_autoheight() {
    $(this).height(0).height(this.scrollHeight);
  }

  $(function () {
    var media_frame;

    if (typeof (intlTelInput) === 'function' && $('#whatsappme_phone').length) {
      var country_request = JSON.parse(localStorage.whatsappme_country_code || '{}');
      var country_code = (country_request.code && country_request.date == new Date().toDateString()) ? country_request.code : false;
      var $phone = $('#whatsappme_phone');
      var iti = intlTelInput($phone.get(0), {
        hiddenInput: $phone.data('name') || 'whatsappme[telephone]',
        initialCountry: 'auto',
        preferredCountries: [country_code || ''],
        geoIpLookup: function (callback) {
          if (country_code) {
            callback(country_code);
          } else {
            $.getJSON('https://ipinfo.io').always(function (resp) {
              var countryCode = (resp && resp.country) ? resp.country : '';
              localStorage.whatsappme_country_code = JSON.stringify({ code: countryCode, date: new Date().toDateString() });
              callback(countryCode);
            });
          }
        },
        utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/' + intl_tel_input_version + '/js/utils.js'
      });
      // Ensures store current value
      iti.hiddenInput.value = $phone.val();

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

    if ($('#whatsappme_form').length === 1) {
      // Tabs
      $('.nav-tab').click(function (e) {
        e.preventDefault();
        var $navtab = $(this);

        $('.nav-tab').removeClass('nav-tab-active').attr('aria-selected', 'false');
        $navtab.addClass('nav-tab-active').attr('aria-selected', 'true').get(0).blur();
        $('.wametab').removeClass('wametab-active');
        $($navtab.attr('href')).addClass('wametab-active').find('textarea').each(textarea_autoheight);
      });

      // Toggle WhatsApp web option
      $('#whatsappme_mobile_only').change(function () {
        $('#whatsappme_whatsapp_web').closest('tr').toggleClass('wame-hidden', this.checked);
      }).change();

      // Toggle WhatsApp badge option
      $('#whatsappme_message_delay').on('change input', function () {
        $('#whatsappme_message_badge').closest('tr').toggleClass('wame-hidden', this.value == '0');
      }).change();

      // Show help
      $('.whatsappme-show-help').click(function (e) {
        e.preventDefault();
        var help_tab = $(this).attr('href');
        if ($('#contextual-help-wrap').is(':visible')) {
          $("html, body").animate({ scrollTop: 0 });
        } else {
          $('#contextual-help-link').click();
        }
        $( help_tab != '#' ? help_tab : '#tab-link-styles-and-vars').find('a').click();
      });

      // Texarea focus and auto height
      $('textarea', '#whatsappme_form')
        .on('focus', function () { $(this).closest('tr').addClass('whatsappme--focus'); })
        .on('blur', function () { $(this).closest('tr').removeClass('whatsappme--focus'); })
        .on('input', textarea_autoheight)
        .each(textarea_autoheight);


      // Advanced view inheritance
      var $tab_advanced = $('#whatsappme_tab_advanced');
      var inheritance = $('.whatsappme_view_all').data('inheritance') || {
        'all': ['front_page', 'blog_page', '404_page', 'search', 'archive', 'singular', 'cpts'],
        'archive': ['date', 'author'],
        'singular': ['page', 'post'],
      };

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

      $('#whatsappme_button_image_add').click(function (e) {
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

            $('#whatsappme_button_image_holder').css({ 'background-size': 'cover', 'background-image': 'url(' + url + ')' });
            $('#whatsappme_button_image').val(attachment.id);
            $('#whatsappme_button_image_remove').removeClass('wame-hidden');
          });

          media_frame.on('open', function () {
            // Pre-selected attachment
            var attachment = wp.media.attachment($('#whatsappme_button_image').val());
            media_frame.state().get('selection').add(attachment ? [attachment] : []);
          });
        }

        media_frame.open();
      });

      $('#whatsappme_button_image_remove').click(function (e) {
        e.preventDefault();

        $('#whatsappme_button_image_holder').removeAttr('style');
        $('#whatsappme_button_image').val('');
        $(this).addClass('wame-hidden');
      });
    }

    if ($('.whatsappme-metabox').length === 1) {
      // Texarea auto height
      $('textarea', '.whatsappme-metabox').on('focus input', textarea_autoheight).each(textarea_autoheight);
    }
  });
})(jQuery);
