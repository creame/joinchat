=== Join.chat ===
Contributors: creapuntome, pacotole, davidlillo, monillo
Donate link: https://www.paypal.me/creapuntome/
Tags: whatsapp business, whatsapp, click to chat, button, whatsapp support chat, support, contact, directly message whatsapp, floating whatsapp, whatsapp chat
Requires at least: 3.0.1
Tested up to: 5.4
Requires PHP: 5.3
Stable tag: 4.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

(formerly WAme) We help you capture users with WhatsApp and turn them into clients.

== Description ==

[Join.chat](https://join.chat?utm_source=wporg&utm_medium=web&utm_campaign=v4_0) | [Add-ons](https://join.chat/en/addons/?utm_source=wporg&utm_medium=web&utm_campaign=v4_0) | [Documentation](https://join.chat/en/docs/?utm_source=wporg&utm_medium=web&utm_campaign=v4_0) | [Support](https://join.chat/en/support/?utm_source=wporg&utm_medium=web&utm_campaign=v4_0)

### The best WhatsApp plugin for WordPress.
> We help more than 200,000 businesses worldwide to attract users and convert them into clients.

### New in Join.chat 4.0 (formerly WAme) 📍

🌈 **Say hello to colors.** We've redesigned the entire interface of the chat window, now you choose a color, and we customize all the visual theme of the Widget.

🍾 **Triggers.** Your pages can interact with Join.chat and show the chat window or launch WhatsApp when user clicks or an item appears when scrolling. You just need to add a few css classes.

### ⌁ What you can do with Join.chat ✅

#### 🛎 Insert a WhatsApp button on your website.
Define in which pages or zones it should appear, the delay time, if you want it to the right or to the left, only on mobile phones or also on the desktop.

#### 🔮 Magic WhatsApp button.
Add your logo, profile picture or even an animated gif. You can define a tooltip to capture the user's attention, the limit is set by your creativity.

#### 📝 Edit at publication level.
You can change general settings on every Post, Page, Product or CPT. In the right side you will find Join.chat metabox where you can modify Phone, CTA, Message and display options.

#### 🔴 Show a notification.
Use a balloon on the button to get the user's attention. In this way, you ensure that you do not miss any important message you want to give them, surprise them in a less intrusive way.

#### 📯 Create call-to-action messages.
For users to click on the button, use custom CTAs on each page, product or section. Welcome them, help them and offer them offers or promotions. [You can read more about this topic here](https://join.chat/es/whatsapp-me-mucho-mas-que-un-click-to-chat/).

#### 💬 Customize conversation start messages.
So that the user does not waste time in writing. This way you will be able to know from which page it comes or what product is being consulted when you start the first conversation.

#### 🛒 Integration with WooCommerce.
Define CTAs and Custom Messages for product pages, you can use dynamic variables such as {SKU}, {PRICE} or {PRODUCT}.

#### 🏁 Analyze the conversion data in Google Analytics and Facebook Pixel.
Remember, you do not have to do anything, the plugin already creates and computes the events by itself. [You can read more about this topic here](https://join.chat/es/wame-mide-los-eventos-de-whatsapp-en-google-analytics/).

#### 💱 Customize different languages.
To be able to support all your users, wherever they are. Our plugin is compatible with WPML and Polylang.

#### 🌚 Dark Mode.
Display the chat window with dark colors and white text. From settings you can activate it or leave it automatic so that it detects the configuration of devices in dark mode.

#### 👨‍💻 Developer friendly.
Fully extensible, with lots of filters and actions to extend its functionality or change behavior.

### ⌁ What you can´t do with Join.chat ⛔️

#### 👨‍🎨 Modify the appearance of the button.
Users recognize it instantly because it is in thousands of web pages and they know what it is for, it generates trust. If you modify it, you lose these important values.

#### 😡 Wasting time configuring other similar plugins.
Having many options is not an advantage, the configuration of Join.chat is so easy that in less than 2 minutes you will be ‘wasapeando’ with your clients.

### ⌁ If you like Join.chat 😍
1. Please leave us a [★★★★★](https://wordpress.org/support/plugin/creame-whatsapp-me/reviews/#new-post) rating. We'll thank you.
2. Help us with the [translation in your language](https://translate.wordpress.org/projects/wp-plugins/creame-whatsapp-me)
3. Subscribe to our newsletter and visit our blog at [join.chat](https://join.chat/?utm_source=wporg&utm_medium=web&utm_campaign=v4_0).
4. Follow [@joinchat](https://twitter.com/wamechat) on twitter.

Name history: *WhatsApp me > WAme > VVAme > **Join.chat***

*WhatsApp and WhatsApp Logo are brand assets and trademark of Facebook, Inc. Join.chat is not in partnership, sponsored or endorsed by Facebook, Inc.*

== Installation ==

1. Upload the entire `creame-whatsapp-me` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= WAme now is Join.chat =

From version 4.0 we change our name to Join.chat.
To avoid using several prefixes from old and new versions we have decided to rename all them to the new `joinchat`.

* All analytics events change from `WhatsAppMe` to `JoinChat`
* All css styles change from `wame` or `whatsappme` to `joinchat`.
* All actions and filters change from `wame_` or `whatsappme_` to `joinchat_`.

= I can't see the button or it's over / under another thing =

You can change the position of the button so that nothing covers it by adding this CSS in *Appearance > Customize > Custom CSS*:

`.joinchat { z-index:9999; }`

If you need to move up:

```
/* always */
.joinchat { --bottom: 60px; }

/* mobile only */
@media (max-width: 480px), (max-width: 767px) and (orientation: landscape) {
  .joinchat { --bottom: 60px; }
}
```

Greater values of z-index are left over, the default value is 400.

= What about GDPR? =

Join.chat don't use cookies.

Join.chat save two localStorage variables for proper operation:

* `joinchat_visited` to know if is the first time on site or is a returning user.
* `joinchat_hashes` if you set a Call To Action (CTA), when user launch WhatsApp or close Chat Window the CTA hashed is saved to prevent show automatically that CTA again.

= Google Analytics integration =

Join.chat send a custom event when user click to launch WhatsApp.

If Global Site Tag (gtag.js) detected:

`gtag('event', 'click', { 'event_category': 'JoinChat', 'event_label': out_url })`

If Universal Analtics (analytics.js) detected:

`ga('send', 'event', 'JoinChat', 'click', out_url })`

If your tracker doesn't have the standard name 'ga' you can set your custom name with 'ga_tracker' setting:

`add_filter( 'joinchat_get_settings', function( $settings ){
    $settings['ga_tracker'] = 'my_custom_GA_name';
    return $settings;
} );`

= Google Tag Manager integration =

Join.chat send an event (if GTM detected) when user click to launch WhatsApp:

`dataLayer.push({ 'event': 'JoinChat', 'eventAction': 'click', 'eventLabel': out_url });`

= Facebook Pixel integration =

Join.chat send a custom event if Facebook Pixel is detected when user click to launch WhatsApp:

`fbq('trackCustom', 'JoinChat', { eventAction: 'click', eventLabel: out_url });`

= Other integrations =

There is a Javascript event that Join.chat triggers automatically before launch WhatsApp, which can be used to add your custom tracking code (or other needs).

```
jQuery(document).ready(function($){
  $(document).on('joinchat:open', function (event, args, settings) {
    // Your staff
    // Note: args.link is the link to open, you can change it
    // but only wa.me, whastapp.com or current domain are allowed.
  });
});
```

= WPML/Polylang change Telephone by language =

Join.chat general text settings can be translated with the strings translation of WPML/Polylang. You only need to save Join.chat settings to register strings and make them ready for translation. But "Telephone" is not translateable by default. If you need different phone numbers for every language add the following php code in your theme functions.php and save Join.chat settings.

```
add_filter( 'joinchat_settings_i18n', function( $settings ) {
    $settings['telephone'] = 'Telephone';
    return $settings;
} );
```

= Settings are not saved when using emojis =

To save emojis site database must use utf8mb4 encoding.
If settings are not saved when using emojis, add this code in your theme functions.php:

`add_filter( 'sanitize_text_field', 'wp_encode_emoji' );`

== Screenshots ==

1. Set phone, button text and call to action.
2. Chat window.
3. Set button image and tooltip.
4. Set chat window color theme.

== Changelog ==

= 4.0.2 =
* Encode emojis if DB not support utf8mb4.
* Better update from WAme (no manual activation required).

= 4.0.1 =
* minor fixes.

= 4.0.0 =
* **NEW:** Join.chat brand.
* **NEW:** Widget theme color.
* **NEW:** CSS class triggers to open chat window.
* Lighter, reduced assets size and deleted images.

**CHANGED for SEO:** All analytics events change from `WhatsAppMe` to `JoinChat`.

**CHANGED for Devs:** All css classes, actions and filters change from `wame` or `whatsappme` to `joinchat`.

See [changelog.txt](https://plugins.svn.wordpress.org/creame-whatsapp-me/trunk/changelog.txt) for older changelog

== Upgrade Notice ==

= 4.0.2 =
**Join.chat rebrand!!** Analytics events change from `WhatsAppMe` to `JoinChat` and classes, actions and filters change from `wame` or `whatsappme` to `joinchat`.

= 2.3.0 =
WPML and Polylang integration.
Added new settings to control delay and launch WhatsApp Web on desktop.
Dynamic variables {SITE}, {URL} and {TITLE} now also works on Call To Action.
Fixed incorrect WAme post settings on loops.
