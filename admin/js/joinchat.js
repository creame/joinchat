(function ($, window) {
  'use strict';

  $(function () {
    var has_iti = window.joinchat_iti?.hasITI();
    var $phone = $('#joinchat_phone');

    if (has_iti && $phone.length) {
      // Capture placeholder (global settings phone) and clear
      var global_phone = $phone.attr('placeholder') || '';
      $phone.removeAttr('placeholder');

      // Apply intlTelInput to phone input
      window.joinchat_iti.init($phone[0], {
        customPlaceholder: (exampleNumber, selectedCountry) => global_phone || `${window.joinchat_iti_l10n.placeholder} ${exampleNumber}`,
      });

      $phone.on('open:countryselector', () => { global_phone = null; });
    }
  });
})(jQuery, window);
