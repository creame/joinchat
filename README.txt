=== WhatsApp me ===
Contributors: creapuntome, pacotole, davidlillo, monillo
Donate link: https://www.paypal.me/creapuntome/
Tags: whatsapp business, whatsapp, click to chat, button, whatsapp support chat, support, contact, directly message whatsapp, floating whatsapp, whatsapp chat
Requires at least: 3.0.1
Tested up to: 5.2
Requires PHP: 5.3
Stable tag: 2.3.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Connect a WordPress chat with WhatsApp. The best solution for marketing and support. Stop losing customers and increase your sales.

== Description ==

[wame.chat](https://wame.chat) | [Chatbot demo (es)](https://wame.chat/es/wame-chatbot/) | [Plugin support](https://wordpress.org/support/plugin/creame-whatsapp-me/)

### Connect a WordPress chat with WhatsApp. The best solution for marketing and support. Stop losing customers and increase your sales.

### ⌁ What you can do with WAme ✅

#### 🛎 Insert a WhatsApp button on your website.
Define in which pages or zones it should appear, the delay time, if you want it to the right or to the left, only on mobile phones or also on the desktop.

#### 📱 Add multiple phone numbers.
You can serve users in different terminals, you can insert a different one in each page, product or section.

#### 🔴 Show a notification.
Use a balloon on the button to get the user's attention. In this way, you ensure that you do not miss any important message you want to give them, surprise them in a less intrusive way.

#### 📯 Create call-to-action messages.
For users to click on the button, use custom CTAs on each page, product or section. Welcome them, help them and offer them offers or promotions. [You can read more about this topic here](https://wame.chat/es/whatsapp-me-mucho-mas-que-un-click-to-chat/).

#### 💬 Customize conversation start messages.
So that the user does not waste time in writing. This way you will be able to know from which page it comes or what product is being consulted when you start the first conversation.

#### 🏁 Analyze the conversion data in Google Analytics.
Remember, you do not have to do anything, the plugin already creates and computes the events by itself. [You can read more about this topic here](https://wame.chat/es/wame-mide-los-eventos-de-whatsapp-en-google-analytics/).

#### 💱 Customize different languages.
To be able to support all your users, wherever they are. Our plugin is compatible with WPML and Polylang.

### ⌁ What you can´t do with WAme ⛔️

#### 👨‍🎨 Modify the appearance of the button.
Users recognize it instantly because it is in thousands of web pages and they know what it is for, it generates trust. If you modify it, you lose these important values.

#### 😡 Wasting time configuring other similar plugins.
Having many options is not an advantage, the configuration of WAme is so easy that in less than 2 minutes you will be ‘wasapeando’ with your clients.

### ⌁ Translations 🇦🇶
-[English (US)](https://wordpress.org/plugins/creame-whatsapp-me/)
-[Portuguese (Brazil)](https://br.wordpress.org/plugins/creame-whatsapp-me/)
-[Spanish (Spain)](https://es.wordpress.org/plugins/creame-whatsapp-me/)
-[Translate into your language](https://translate.wordpress.org/projects/wp-plugins/creame-whatsapp-me)

### ⌁ If you like WAme 😍
1. Subscribe to [our newsletter and our blog](https://wame.chat/blog/).
2. Learn from our tutorials on [Youtube Channel](https://www.youtube.com/channel/UCqHiSNPBaQ918fpVnCU1wog/).
3. Or rate us [on WordPress](https://wordpress.org/support/plugin/creame-whatsapp-me/reviews/?filter=5/#new-post).

== Installation ==

1. Upload the entire `creame-whatsapp-me` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Open WhatsApp Web on desktop =

By default, WhatsApp me always opens api.whatsapp.com and try to launch the native application or if it doesn't exist redirects to WhatsApp Web. Depending on the browser and the operating system, sometimes it doesn't work.

If you prefer always open WhatsApp Web on desktop you can add this code in your functions.php:

`add_filter( 'whatsappme_whatsapp_web', '__return_true' );`

**Note:** From version 2.3.0 you can mark an option to open WhatsApp Web.

= I can't see the button or it's over / under another thing =

You can change the position of the button so that nothing covers it by adding this CSS in *Appearance > Customize > Custom CSS*:

`.whatsappme { z-index:9999; }`

Greater values of z-index are left over, the default value is 400.

= What about GDPR? =

WhatsApp me don't save any personal data and don't use cookies.

= Google Analytics integration =

WhatsApp me send an event when user click to launch WhatsApp.

If Global Site Tag (gtag.js) detected:

`gtag('event', 'click', { 'event_category': 'WhatsAppMe', 'event_label': out_url })`

If Universal Analtics (analytics.js) detected:

`ga('send', 'event', 'WhatsAppMe', 'click', out_url })`

= Google Tag Manager integration =

WhatsApp me send an event (if GTM detected) when user click to launch WhatsApp:

`dataLayer.push({ 'event': 'WhatsAppMe', 'eventAction': 'click', 'eventLabel': out_url });`

== Screenshots ==

1. WhatsApp me general settings.
2. WhatsApp me advanced visibility settings.
3. WhatsApp me on post/page edition.
4. Button on desktop.
5. Call to action on desktop.
6. Button and call to action on mobile.

== Changelog ==

= 2.3.3 =
* FIX javascript error when "ga" global object is defined but isn't Google Analytics.

= 2.3.2 =
* FIX PHP notice on some archive pages.

= 2.3.1 =
* Readme texts and description.

= 2.3.0 =
* **NEW:** WPML/Polylang integration.
* **NEW:** Added setting to launch WhatsApp Web on desktop.
* **NEW:** Separated button delay and chat delay settings.
* **NEW:** dynamic variables {SITE}, {URL} and {TITLE} now also works on Call To Action.
* CHANGED Better ordered settings panel.
* FIX incorrect post id on loops can return post config instead main config.
* FIX typo error on filter "whatsappme_whastapp_web"

= 2.2.3 =
* **NEW:** Hide in front if editing with Elementor.
* CHANGED improvements in public styles.

= 2.2.2 =
* **NEW:** styles/scripts minified.
* FIX UX issues.

= 2.2.0 =
* **NEW:** Now can change telephone number on every post/page.
* **NEW:** Send Google Tag Manager event on click.
* **NEW:** New filter 'whatsappme_whastapp_web'. Set true if you prefer to open WhatsApp Web on desktop.
* **NEW:** "Send button" change when dialog is opened.
* UPDATED Tested up to Wordpress v.5.1.
* UPDATED International Telephone Input library to v.15.

= 2.1.3 =
* FIX PHP warning on some rare cases.

= 2.1.2 =
* FIX javascript error on iOS Safari private browsing.

= 2.1.1 =
* FIX javascript error on IE11.

= 2.1.0 =
* **NEW:** Button bagde option for a less intrusive mode.
* CHANGED now each different Call to Action is marked as read separately.
* CHANGED now first show Call to Action (if defined) before launch WhatsApp link.

= 2.0.1 =
* FIX removed array_filter function that requires PHP 5.6 min version.

= 2.0.0 =
* **NEW: Advanced visibility settings to define where to show *WhatsApp me* button.**
* **NEW:** WooCommerce integration.
* UPDATED International Telephone Input library to v.13.
* Minor fixes on fields cleanup and other improvements.

= 1.4.3 =
* NEW support for Google Analytics Global Site Tag (gtag.js).
* CHANGE events label now is the destination URL to match general behavior.
* UPDATED International Telephone Input library

= 1.4.2 =
* FIX JavaScript error introduced on v1.4.1.

= 1.4.1 =
* Fix JS frontend sometimes can't load WhatsApp me settings.
* Fix better Google Analytics event tracking when leave page.

= 1.4.0 =
* **NEW:** Added the option to define the first message to send. You can include variables such as {SITE}, {URL} or {TITLE}.
* Fix PHP notice when global $post is null (e.g. search results or login page).

= 1.3.2 =
* Only set admin/public hooks when it corresponds to improve performance and fix a notice on admin.

= 1.3.1 =
* Fix fatal error when the PHP mbstring extension is not active

= 1.3.0 =
* Added option to change position of button to left
* Added formatting styles for Call to action text like in WhatsApp: *italic* **bold** strikethrough

= 1.2.0 =
* Added International Telephone Input for enhanced phone input
* Phone number is cleared to generate correct WhatsApp links

= 1.1.0 =
* Added posts/pages option to override CTA or hide button
* Don't enqueue assets if not show button
* Added filters for developers

= 1.0.3 =
* Readme texts

= 1.0.2 =
* Fix plugin version

= 1.0.1 =
* Fix text domain

= 1.0.0 =
* First version

== Upgrade Notice ==

= 2.3.0 =
WPML and Polylang integration.
Added new settings to control delay and launch WhatsApp Web on desktop.
Dynamic variables {SITE}, {URL} and {TITLE} now also works on Call To Action.
Fixed incorrect WAme post settings on loops.
