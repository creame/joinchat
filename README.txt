=== Join.chat ===
Contributors: creapuntome, pacotole, davidlillo, monillo
Donate link: https://www.paypal.me/creapuntome/
Tags: whatsapp business, whatsapp, click to chat, button, whatsapp support chat, support, contact, directly message whatsapp, floating whatsapp, whatsapp chat
Requires at least: 3.0.1
Tested up to: 5.5
Requires PHP: 5.3
Stable tag: 4.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

(formerly WAme) We help you capture users with WhatsApp and turn them into clients.

== Description ==

### 💬 Connect your WordPress to WhatsApp in one click.

> The best WhatsApp plugin for WordPress, with almost 300,000 installations worldwide.

[Documentation](https://join.chat/en/docs/?utm_source=wporg&utm_medium=web&utm_campaign=v4_1) | [Add-ons](https://join.chat/en/addons/?utm_source=wporg&utm_medium=web&utm_campaign=v4_1) | [Support](https://join.chat/en/support/?utm_source=wporg&utm_medium=web&utm_campaign=v4_1)

### ⌁ What you can do with Join.chat (formerly WAme) ✅

#### 🛎 Insert a contact button for WhatsApp on your website.
Add your logo, profile picture or even an animated gif. Define in which pages or zones it should appear, the delay time, if you want it to the right or to the left, only on mobile phones or also on the desktop. You can define a tooltip to capture the user's attention, the limit is set by your creativity.

#### 📝 Edit at publication level.
You can **change main settings on every Post, Page, Product or CPT**. In the right side you will find Join.chat metabox where you can modify Phone, CTA, Message and display options.

#### 🔴 Show a notification.
Use a balloon on the button to get the user's attention. In this way, you ensure that you do not miss any important message you want to give them, surprise them in a less intrusive way.

#### 📯 Create call-to-action messages.
For users to click on the button, use custom CTAs on each page, product or section. Welcome them, help them and offer them offers or promotions. [Read more](https://join.chat/es/joinchat-mucho-mas-que-un-click-to-chat/)

#### 💬 Customize conversation start messages.
So that the user does not waste time in writing. This way you will be able to know from which page it comes or what product is being consulted when you start the first conversation.

#### 🛒 Integration with WooCommerce.
Define CTAs and Custom Messages for product pages and for products on sale.

#### 🃏 Dynamic Variables.
Use variables in your CTAs and messages that change dynamically for each page:

`{SITE}` ➡ Website title<br>
`{TITLE}` ➡ Current page url<br>
`{URL}` ➡ Current page url<br>
`{PRODUCT}` ➡ Product name (WooCommerce)<br>
`{SKU}` ➡ Product SKU (WooCommerce)<br>
`{REGULAR}` ➡ Product regular price (WooCommerce)<br>
`{PRICE}` ➡ Product current price (WooCommerce)<br>
`{DISCOUNT}` ➡ Product percent discount when is on sale (WooCommerce)<br>

#### 📈 Integration with Google Analytics, Google Tag Manager and Facebook Pixel.
Join.chat sends the event automatically when the user opens WhatsApp. You can also create your custom events capturing `$(document).on('joinchat:open')`. [Read more](https://join.chat/en/joinchat-measures-whatsapp-events-in-google-analytics/)

#### 💱 Multi-Language Support.
To be able to support all your users, wherever they are. Our plugin is **compatible with WPML and Polylang**.

#### 🌈 Theme Colors & 🌚 Dark Mode.
You choose a color and we customize the entire visual theme of the widget. With Dark Mode display the chat window with dark colors and white text. From settings you can activate it or leave it automatic and detects devices' configuration.

#### 🍾 CSS Triggers.
Your pages can interact with Join.chat and show the chat window or launch WhatsApp **when user clicks or an item appears on scrolling**. You just need to add a few CSS classes.

#### ⚡ Fast & Light.
Only load what need when needed. Join.chat is lightweight and follow best coding practices. [See tests report](https://wphive.com/plugins/creame-whatsapp-me/)

#### 👨‍💻 Developer friendly.
Fully extensible, with lots of filters and actions to extend its functionality or change behavior.

### ⌁ PREMIUM ADD-ONS 🍡
Extend Join.chat with awesome features:

#### 💯 [Join.chat Plus](https://join.chat/en/addons/plus/).
Join.chat + gives you access to faster and easier support through tickets.

#### 📡 [OmniChannel](https://join.chat/en/addons/omnichannel/).
This Add-on will allow you to add more chat applications to the basic plug-in, in addition to WhatsApp. You can now add Telegram, Facebook Messenger, SMS, Phone call, Skype and FaceTime.

#### 👥 [Support Agents](https://join.chat/en/addons/support-agents/).
This Add-on allows you to add several WhatsApp accounts, defining the personal data of each agent's profile, as well as their work schedule. This way customers can choose who they want to talk to.

#### 🎬 [CTA Extras](https://join.chat/en/addons/cta-extras/).
This Add-on allows you to create rich content in the chat window, to make the calls to action (CTA) 😍 irresistible. You can add links to images, call to action buttons, you can even add animated Iframes or GIFs.

#### 🎲 [Random Phone](https://join.chat/en/addons/random-phone/).
With Random Phone you can add as many phone numbers as you want, there is no limit. Every time a user of your site clicks on the start chat button, they will be randomly and equitably referred to each of the different support numbers you have configured.

#### 🤖 [Chatbot Svachat](https://join.chat/en/addons/svachat-bot/).
Our Add-on Chatbot (Svachat) is designed to answer your customers' recurring questions (FAQS) by creating a chatbot that interacts with the user and decides based on the user's queries.

### ⌁ If you like Join.chat 😍
1. Please leave us a [★★★★★](https://wordpress.org/support/plugin/creame-whatsapp-me/reviews/#new-post) rating. We'll thank you.
2. Help us with the [translation in your language](https://translate.wordpress.org/projects/wp-plugins/creame-whatsapp-me)
3. Visit our blog and find tips and tricks at [join.chat](https://join.chat/en/blog/?utm_source=wporg&utm_medium=web&utm_campaign=v4_1).
4. Follow [@joinchatnow](https://twitter.com/joinchatnow) on twitter.


== Installation ==

1. Upload the entire `creame-whatsapp-me` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Upgrade from WAme to Join.chat =

From version 4.0 we change our name to Join.chat.
To avoid using several prefixes from old and new versions we have decided to rename all them to the new `joinchat`.

* All analytics events change from `WhatsAppMe` to `JoinChat`
* All css styles change from `wame` or `whatsappme` to `joinchat`.
* All actions and filters change from `wame_` or `whatsappme_` to `joinchat_`.

= I can't see the button or it's over / under another thing =

You can change the position of the button so that nothing covers it by adding this CSS in *Appearance > Customize > Custom CSS*:

`.joinchat { z-index:9999; }`

Higher values ​​of z-index are above, the default value is 1000.

If you need to move up:

`/* always */
.joinchat { --bottom: 60px; }

/* mobile only */
@media (max-width: 480px), (max-width: 767px) and (orientation: landscape) {
  .joinchat { --bottom: 60px; }
}`

= What about GDPR? =

Join.chat don't use cookies.

Join.chat save two localStorage variables for proper operation:

* `joinchat_views` is a visits counter to control when to show chat window.
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

`jQuery(document).ready(function($){
  $(document).on('joinchat:open', function (event, args, settings) {
    // Your staff
    // Note: args.link is the link to open, you can change it
    // but only wa.me, whastapp.com or current domain are allowed.
  });
});`

== Screenshots ==

1. Mobile example
2. Desktop example (with Dynamic Vars)
3. General settings
4. Visibility settings
5. WooCommerce settings

== Changelog ==

= 4.1 =
* **NEW:** Use custom text on chat window header
* Added 'joinchat_app' class trigger that opens WhatsApp
* Added Telephone to translatable fields
* Added 'joinchat_disable_thumbs' filter
* FIX updated style regex patterns
* FIX hide on mobile when user fill forms

= 4.0.10 =
* **NEW:** show tooltip on hover button
* **NEW:** hide on mobile when user fill forms
* CHANGED by default clear all plugin data on uninstall.
* FIX remove unnecessary get option 'whatsappme'

= 4.0.9 =
* FIX notification balloon text color white
* New js event 'joinchat:starting'

= 4.0.8 =
* FIX WP Super Cache clear cache error on save
* Image thumbnail fallback if possible

= 4.0.7 =
* FIX WP Super Cache clear cache error on save

= 4.0.6 =
* Minor changes: better encode emoji detection, check WooCommerce version, css fixes and improvements

= 4.0.5 =
* **NEW:** Clear third party cache plugins on settings save.
* FIX php error on image resize.
* UPDATED International Telephone Input library to v.17.

= 4.0.4 =
* Better public settings JSON output
* Re-fix WAme deactivate

= 4.0.3 =
* Fix WAme deactivate

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

= 4.0.0 =
**Join.chat rebrand!!** Analytics events change from `WhatsAppMe` to `JoinChat` and classes, actions and filters change from `wame` or `whatsappme` to `joinchat`.

= 2.3.0 =
WPML and Polylang integration.
Added new settings to control delay and launch WhatsApp Web on desktop.
Dynamic variables {SITE}, {URL} and {TITLE} now also works on Call To Action.
Fixed incorrect WAme post settings on loops.
