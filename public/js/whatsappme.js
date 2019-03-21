(function ($) {
  'use strict';

  $(function () {
    var delay_on_start = 3000;
    var $whatsappme = $('.whatsappme');

    // only works if whatsappme is defined
    if ($whatsappme.length && typeof ($whatsappme.data('settings')) == 'object') {
      whatsappme_magic();
    }

    function whatsappme_magic() {
      var is_mobile = !!navigator.userAgent.match(/Android|iPhone|BlackBerry|IEMobile|Opera Mini/i);
      var timeoutID = null;
      var settings = $whatsappme.data('settings');

      // stored values
      var is_clicked = localStorage.whatsappme_click == 'yes';
      var views = settings.message_text === '' ? 0 : parseInt(localStorage.whatsappme_views || 0) + 1;
      localStorage.whatsappme_views = views;

      // show button / dialog
      if (!settings.mobile_only || is_mobile) {
        setTimeout(function () {
          $whatsappme.addClass('whatsappme--show');
        }, delay_on_start);
        if (views > 1 && !is_clicked) {
          setTimeout(function () {
            $whatsappme.addClass('whatsappme--dialog');
          }, delay_on_start + settings.message_delay);
        }
      }

      if (!is_mobile && settings.message_text !== '') {
        $('.whatsappme__button').mouseenter(function () {
          timeoutID = setTimeout(function () {
            $whatsappme.addClass('whatsappme--dialog');
          }, 1600);
        }).mouseleave(function () {
          clearTimeout(timeoutID);
        });
      }

      $('.whatsappme__button').click(function () {
        $whatsappme.removeClass('whatsappme--dialog');
        localStorage.whatsappme_click = 'yes';

        // Send Google Analytics event
        if (typeof (ga) !== 'undefined') {
          ga('send', 'event', 'WhatsAppMe', 'click');
        }

        // check if is mobile or desktop device
        var waPrefix = is_mobile ? 'api' : 'web';

        // open WhatsApp link
        window.open('https://' + waPrefix + '.whatsapp.com/send?phone=' + settings.telephone, 'whatsappme');
      });

      $('.whatsappme__close').click(function () {
        $whatsappme.removeClass('whatsappme--dialog');
        localStorage.whatsappme_click = 'yes';
      });
    }

  });

})(jQuery);
