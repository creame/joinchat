(function ($, window, document) {
  'use strict';

  // Random number
  function rand(min, max) { return Math.round(Math.random() * (max - min) + min); }

  // Country code for IntTelInput
  var country_request = JSON.parse(localStorage.joinchat_country_code || '{}');
  var country_code = (country_request.code && country_request.date == new Date().toDateString()) ? country_request.code : false;
  if (!country_code) {
    $.getJSON('https://ipinfo.io').always(function (resp) {
      country_code = (resp && resp.country) ? resp.country : '';
      localStorage.joinchat_country_code = JSON.stringify({ code: country_code, date: new Date().toDateString() });
    });
  }

  var joinchat_obj = {
    $div: null,
    $: function (sel) { return $(sel || this.$div, this.$div); },
  };

  var joinchat_dialog = {
    init: function () {
      var that = this;

      // Store dialog messages
      this.step_number = 0;
      this.step = null;
      this.msg = 0;

      // Set joinchat_dialog context as this
      $.each(this, function (name, func) { if (typeof func == 'function') that[name] = func.bind(that); });

      // DOM nodes
      this.$dialog = joinchat_obj.$('.joinchat__dialog');
      this.$scroll = $('html,body');
      this.$last_msg = null;

      this.saved = {};
    },

    // Start
    startDialog: function () {
      this.loadStep();
      // Add options listener
      this.$dialog.on('click keydown', '.joinchat__option', this.listenOption);
      // Disable focus/change on readonly fields
      this.$dialog.on('click', 'input[type="checkbox"][readonly]', function () { return false; });
      this.$dialog.on('focus', 'input[readonly],textarea[readonly]', function () { this.blur(); });
    },

    // Load funnel step
    loadStep: function (number) {
      this.step_number = number || 0;
      this.step = joinchat_steps[this.step_number] || null;
      this.msg = 0;
      if (!!this.step) this.nextMessage();
      else console.log("Joinchat: missing step " + number);
    },

    // Load step next message
    nextMessage: function () {
      var msg = this.step.content[this.msg] || false;
      if (msg) {
        this.loading();
        this.delay(function () {
          this.addMessage(msg);
          this.msg++;
          this.delay(this.nextMessage);
        }, (msg.split(/\s+/).length * 60) + rand(100, 200)); // Delay (word count * time) + random delay
      } else {
        this.options();
      }
    },

    // Append message to dialog
    addMessage: function (msg, cls) {
      var $msg = $('<div class="joinchat__message ' + (cls || '') + '">' + msg + '</div>');
      if (msg && $msg.text().trim() == '') $msg.addClass('joinchat__message--media');
      if (this.$last_msg && this.$last_msg.hasClass('joinchat__message--loading')) this.$last_msg.remove();
      this.$dialog.append($msg);
      this.addInput($msg, msg);
      this.scrollTo($msg);
      this.$last_msg = $msg;
    },

    // Append message loading...
    loading: function () {
      this.addMessage('', 'joinchat__message--loading');
    },

    // Append options
    options: function () {
      if (this.step.options.length == 0) return;
      if (this.step.options.length == 1 && this.step.options[0].text == '') {
        this.doOption(this.step.options[0]);
      } else {
        var msg = '', n = this.step_number, that = this;
        $.each(this.step.options, function (i, option) {
          if (option.type == 'contact') that.doOption(option);
          else msg += '<div class="joinchat__option joinchat__option--' + option.type + ' ' + (option.class || '') + '" role="button" tabindex="0" data-option="' + n + '-' + i + '">' + option.text + '</div>';
        });
        this.addMessage(msg, 'joinchat__message--options');
      }
    },

    // Append user reply message
    reply: function (reply) {
      this.addMessage(reply, 'joinchat__message--reply');
    },

    // Scroll chat box to message position
    scrollTo: function ($msg, duration) {
      this.$scroll.animate({ scrollTop: $msg[0].offsetTop - this.$dialog[0].offsetTop }, duration || 400);
    },

    // Exec func after delay with joinchat_dialog as this
    delay: function (func, delay) {
      setTimeout(func.bind(this), delay || rand(400, 800));
    },

    // Capture option selected
    listenOption: function (event) {
      if ($(event.target).hasClass('joinchat__option--disabled')) return;
      if (event.type != 'keydown' || event.which == 13 || event.which == 32) {
        var number = $(event.currentTarget).data('option').split('-');
        var option = joinchat_steps[number[0]].options[number[1]];
        this.doOption(option);
      }
    },

    // Exec option by type
    doOption: function (option) {
      var func_name = 'doOption' + option.type.charAt(0).toUpperCase() + option.type.slice(1);
      this[func_name](option);
    },

    // Goto option
    doOptionGoto: function (option) {
      this.$last_msg.remove();
      if (option.text != '') this.reply(option.text);
      this.delay(function () { this.loadStep(option.value || (this.step_number + 1)); }, 500);
    },

    // Link option
    doOptionLink: function (option) {
      this.delay(function () { option.value.charAt(0) == '!' ? window.open(option.value.substr(1)) : window.location = option.value; }, 250);
    },

    doOptionPhone: function (option) {
      var input = $('#joinchat_phone').get(0);
      this.saved['telephone'] = intlTelInputGlobals.getInstance(input).getNumber();
      input.readOnly = true;
      this.doOptionGoto(option);
    },

    doOptionInput: function (option) {
      joinchat_obj.$(option.field).prop('readOnly', true);
      this.saved[option.field.substr(10)] = option.action == 'save' ? joinchat_obj.$(option.field).val() : '';
      this.doOptionGoto(option);
    },

    doOptionNewsletter: function (option) {
      joinchat_obj.$(option.field).prop('readOnly', true);
      this.saved['newsletter'] = option.action == 'save' ? joinchat_obj.$(option.field).val() : '';
      this.saveOnboard(option);
    },

    saveOnboard: function (option) {
      var that = this;
      this.$last_msg.remove();
      this.reply(option.text);
      this.loading();
      $.post(ajaxurl, { action: 'joinchat_onboard', nonce: joinchat_settings.nonce, data: this.saved }, null, 'json')
        .always(function () { that.$last_msg.remove(); })
        .done(function () { that.loadStep(that.step_number + (!!that.saved['newsletter'] ? 1 : 2)); })
        .fail(function () { that.loadStep(that.step_number + 3); });
    },

    addInput: function ($msg, msg) {
      if (!msg.includes('{INPUT')) return;

      if (msg.includes('{INPUT phone}')) {
        $msg.html(msg.replace('{INPUT phone}', '<input id="joinchat_phone" data-name="joinchat[telephone]" value="" type="text">'));
        $msg.css('z-index', 1); // Flag dropdown over option buttons.

        if (typeof intlTelInput === 'function') {
          var $phone = $('#joinchat_phone');
          intlTelInput($phone[0], {
            hiddenInput: $phone.data('name') || 'joinchat[telephone]',
            separateDialCode: true,
            initialCountry: 'auto',
            preferredCountries: [country_code || ''],
            geoIpLookup: function (callback) { if (country_code) callback(country_code); },
            customPlaceholder: function (placeholder) { return intlTelConf.placeholder + ' ' + placeholder; },
            utilsScript: intlTelConf.utils_js,
          });

          $phone.on('input countrychange', function () {
            var $this = $(this);
            var is_valid = intlTelInputGlobals.getInstance(this).isValidNumber();

            $this.css('color', $this.val().trim() && !is_valid ? '#ca4a1f' : '');
            joinchat_obj.$('.joinchat__option--phone').toggleClass('joinchat__option--disabled', !is_valid);
          }).on('blur', function () {
            var iti = intlTelInputGlobals.getInstance(this);
            iti.setNumber(iti.getNumber());
          });
        }
      } else if (msg.includes('{INPUT message}')) {
        $msg.html(msg.replace('{INPUT message}', '<textarea id="joinchat_message_send" name="joinchat[message_send]" rows="3" class="regular-text">' + joinchat_l10n.step_msg_value + '</textarea>'));
      } else if (msg.includes('{INPUT cta}')) {
        $msg.html(msg.replace('{INPUT cta}', '<textarea id="joinchat_message_text" name="joinchat[message_text]" rows="4" class="regular-text">' + joinchat_l10n.step_cta_value + '</textarea>'));
      } else if (msg.includes('{INPUT newsletter}')) {
        $msg.html(msg.replace('{INPUT newsletter}', '<input id="joinchat_email" name="joinchat[button_tip]" value="' + joinchat_settings.user_email + '" type="email" maxlength="60" class="regular-text" placeholder="john@example.com"></input>\n' +
          '<div class="joinchat__optin"><input type="checkbox" id="joinchat_optin"><label for="joinchat_optin">' + joinchat_l10n.step_news_terms + '</label></div>'));

        $msg.find('#joinchat_email,#joinchat_optin').on('input change', function () {
          var $email = $msg.find('#joinchat_email');
          var is_valid = $email.val().trim() != '' && $email.get(0).checkValidity() && $msg.find('#joinchat_optin').get(0).checked;
          joinchat_obj.$('.joinchat__option--accept').toggleClass('joinchat__option--disabled', !is_valid);
        });
      }

      $msg.find('input,textarea').get(0).focus();
    }
  };

  var joinchat_steps = [
    {
      'content': [
        joinchat_l10n.step_hi,
        '<img src="' + joinchat_settings.img_base + 'onboard-01.png" alt="">'
      ],
      'options': [
        {
          'type': 'goto',
          'text': joinchat_l10n.step_hi_next,
        },
      ]
    },
    {
      'content': [
        joinchat_l10n.step_phone + ':<br>{INPUT phone}',
      ],
      'options': [
        {
          'type': 'phone',
          'class': 'joinchat__option--disabled',
          'text': joinchat_l10n.step_phone_next,
        },
      ]
    },
    {
      'content': [
        joinchat_l10n.step_msg,
        '<img src="' + joinchat_settings.img_base + 'onboard-02.png" alt="">',
        joinchat_l10n.step_msg_field + ':<br>{INPUT message}'
      ],
      'options': [
        {
          'type': 'input',
          'text': joinchat_l10n.step_msg_yes,
          'field': '#joinchat_message_send',
          'action': 'save',
        },
        {
          'type': 'input',
          'text': joinchat_l10n.step_msg_no,
          'class': 'joinchat__option--skip',
          'field': '#joinchat_message_send',
          'action': 'empty',
        }
      ]
    },
    {
      'content': [
        joinchat_l10n.step_cta,
        '<img src="' + joinchat_settings.img_base + 'onboard-03.png" alt="">',
        joinchat_l10n.step_cta_field + ':<br>{INPUT cta}',
      ],
      'options': [
        {
          'type': 'input',
          'text': joinchat_l10n.step_cta_yes,
          'field': '#joinchat_message_text',
          'action': 'save',
        },
        {
          'type': 'input',
          'text': joinchat_l10n.step_cta_no,
          'class': 'joinchat__option--skip',
          'field': '#joinchat_message_text',
          'action': 'empty',
        }
      ]
    },
    {
      'content': [
        '<img src="' + joinchat_settings.img_base + 'onboard-04.png" alt="">',
        joinchat_l10n.step_news + '<br>{INPUT newsletter}'
      ],
      'options': [
        {
          'type': 'newsletter',
          'class': 'joinchat__option--accept joinchat__option--disabled',
          'text': joinchat_l10n.step_news_yes,
          'action': 'save',
          'field': '#joinchat_email,#joinchat_optin',
        },
        {
          'type': 'newsletter',
          'text': joinchat_l10n.step_news_no,
          'class': 'joinchat__option--skip',
          'action': 'skip',
          'field': '#joinchat_email,#joinchat_optin',
        },
      ]
    },
    {
      'content': [
        joinchat_l10n.step_inbox
      ],
      'options': [
        {
          'type': 'goto',
          'text': joinchat_l10n.step_inbox_next,
        },
      ]
    },
    {
      'content': [
        joinchat_l10n.step_success,
        '<img src="' + joinchat_settings.img_base + 'onboard-05.png" alt="">',
      ],
      'options': [
        {
          'type': 'link',
          'text': joinchat_l10n.step_settings,
          'value': joinchat_settings.settings_url,
        },
      ]
    },
    {
      'content': [
        joinchat_l10n.step_fail,
      ],
      'options': [
        {
          'type': 'link',
          'text': joinchat_l10n.step_settings,
          'value': joinchat_settings.settings_url,
        },
      ]
    }
  ];

  // Start.
  $(function () {
    joinchat_obj.$div = $('#joinchat_onboard');
    joinchat_dialog.init();
    setTimeout(function () { joinchat_dialog.startDialog(); }, 700);
  });

}(jQuery, window, document));
