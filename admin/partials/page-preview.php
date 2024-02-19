<?php
/**
 * Joinchat preview blank html
 *
 * @since      5.0.0
 * @package    Joinchat
 * @subpackage Joinchat/admin
 * @author     Creame <hola@crea.me>
 */

defined( 'WPINC' ) || exit;
?>

<!doctype html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />

	<?php
	// wp_head(); Don't run because only need Joinchat styles and scripts.
	do_action( 'joinchat_preview_header' );
	?>

	<style id="joinchat-preview-inline-css">
		body {
			background: #fff;
		}

		.joinchat__tooltip.joinchat--show {
			opacity: 1;
			animation: none;
			transition: opacity 0.2s;
		}

		.joinchat__button__sendtext:empty,
		.joinchat__message:empty,
		.joinchat__optin:empty {
			display: none;
		}

		.joinchat--disabled {
			opacity: .2;
			pointer-events: none;
		}

		@media (min-width: 481px) {
			.joinchat.joinchat--mobile_only {
				opacity: .2;
				pointer-events: none;
			}

			.joinchat__qr.joinchat--show {
				display: flex;
			}
		}

		@media (max-width: 480px) {
			.joinchat__qr {
				display: none !important;
			}
		}
	</style>
</head>

<body>
	<div id="page" class="site"></div>

	<?php
	// wp_footer(); Don't run because only want Joinchat actions.
	do_action( 'joinchat_preview_footer' );
	?>

	<script>
		(function($, window, document, joinchat_obj) {
			'use strict';

			joinchat_obj = $.extend({
				$div: null,
				settings: null,
				has_chatbox: false,
				chatbox: false,
				showed_at: 0,
				is_ready: false, // Change to true when Joinchat ends initialization
				is_mobile: !!navigator.userAgent.match(/Android|iPhone|BlackBerry|IEMobile|Opera Mini/i),
				can_qr: window.QrCreator && typeof QrCreator.render == 'function',
			}, joinchat_obj || {});
			window.joinchat_obj = joinchat_obj; // Save global

			joinchat_obj.$ = function(sel) {
				return $(sel || this.$div, this.$div);
			};

			// Trigger Analytics events
			joinchat_obj.send_event = function(params) {};

			// Return WhatsApp link with optional message
			joinchat_obj.whatsapp_link = function(phone, message, wa_web) {
				message = message !== undefined ? message : this.settings.message_send || '';
				wa_web = wa_web !== undefined ? wa_web : this.settings.whatsapp_web && !this.is_mobile;
				var link = (wa_web ? 'https://web.whatsapp.com/send?phone=' : 'https://wa.me/') + encodeURIComponent(phone || this.settings.telephone);

				return link + (message ? (wa_web ? '&text=' : '?text=') + encodeURIComponent(message) : '');
			};

			// Open Chatbox and trigger event
			joinchat_obj.chatbox_show = function() {
				if (!this.chatbox) {
					this.chatbox = true;
					this.showed_at = Date.now();
					this.$div.addClass('joinchat--chatbox');

					if (this.settings.message_badge && this.$('.joinchat__badge').hasClass('joinchat__badge--in')) {
						this.$('.joinchat__badge').toggleClass('joinchat__badge--in joinchat__badge--out');
					}
					// Trigger custom event
					$(document).trigger('joinchat:show');
				}
			};

			// Close Chatbox and trigger event
			joinchat_obj.chatbox_hide = function() {
				if (this.chatbox) {
					this.chatbox = false;
					this.$div.removeClass('joinchat--chatbox joinchat--tooltip');

					if (this.settings.message_badge) {
						this.$('.joinchat__badge').removeClass('joinchat__badge--out');
					}
					// Trigger custom event
					$(document).trigger('joinchat:hide');
				}
			};

			// Open WhatsApp link with supplied phone and message or with settings defaults
			joinchat_obj.open_whatsapp = function(phone, message) {
				phone = phone || this.settings.telephone;
				message = message !== undefined ? message : this.settings.message_send || '';

				var link = this.whatsapp_link(phone, message);
				var secure_link = new RegExp("^https?:\/\/(wa\.me|(api|web|chat)\.whatsapp\.com|" + location.hostname.replace('.', '\.') + ")\/.*", 'i');

				// Ensure the link is safe
				if (secure_link.test(link)) {
					// Open WhatsApp link
					window.open(link, 'joinchat', 'noopener');
				} else {
					console.error("Joinchat: the link doesn't seem safe, it must point to the current domain or whatsapp.com");
				}
			};

			joinchat_obj.optin = function() {
				return !this.$div.hasClass('joinchat--optout');
			};

			// Generate QR canvas
			joinchat_obj.qr = function(text, options) {
				var canvas = document.createElement('CANVAS');
				QrCreator.render($.extend({
					text: text,
					radius: 0.4,
					background: '#FFF',
					size: 200,
				}, joinchat_obj.settings.qr || {}, options || {}), canvas);
				return canvas;
			}

			joinchat_obj.update_cta = function(str) {
				str = str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;"); // Secure
				str = str.replace(/(^|\W)_(.+?)_(\W|$)/gu, '$1<em>$2</em>$3');           // Italic
				str = str.replace(/(^|\W)\*(.+?)\*(\W|$)/gu, '$1<strong>$2</strong>$3'); // Bold
				str = str.replace(/(^|\W)~(.+?)~(\W|$)/gu, '$1<del>$2</del>$3');         // Strikethrough
				str = str.replace(/\n/g, '<br>');                                        // New lines

				this.$('.joinchat__message').html(str);
			}

			function joinchat_magic() {
				joinchat_obj.$div.addClass('joinchat--show');

				var chatbox_hide = joinchat_obj.chatbox_hide.bind(joinchat_obj);

				function joinchat_click() {
					if (joinchat_obj.has_chatbox && !joinchat_obj.chatbox) {
						joinchat_obj.chatbox_show();
					} else if (Date.now() > joinchat_obj.showed_at + 600) { // A bit delay to prevent open WA on auto show
						joinchat_obj.chatbox_hide();
						joinchat_obj.open_whatsapp();
					}
				}

				joinchat_obj.$('.joinchat__button').on('click', joinchat_click);
				joinchat_obj.$('.joinchat__close').on('click', chatbox_hide);

				// Opt-in toggle
				joinchat_obj.$('#joinchat_optin').on('change', function() {
					joinchat_obj.$div.toggleClass('joinchat--optout', !this.checked);
				});

				// Add QR Code
				if (joinchat_obj.settings.qr && joinchat_obj.can_qr && !joinchat_obj.is_mobile) {
					joinchat_obj.$('.joinchat__qr').append(joinchat_obj.qr(joinchat_obj.whatsapp_link(undefined, undefined, false)));
				}

				$(document).trigger('joinchat:start');
				joinchat_obj.is_ready = true;
			}

			// Simple run only once wrapper
			function once(fn) {
				return function() {
					fn && fn.apply(this, arguments);
					fn = null;
				};
			}

			function on_page_ready() {
				joinchat_obj.$div = $('.joinchat');
				joinchat_obj.settings = joinchat_obj.$div.data('settings');
				joinchat_magic();
			}

			// Ready!! (in some scenarios jQuery.ready doesn't fire, this try to ensure Joinchat initialization)
			var once_page_ready = once(on_page_ready);
			$(once_page_ready);
			$(window).on('load', once_page_ready);
			document.addEventListener('DOMContentLoaded', once_page_ready);

		}(jQuery, window, document, window.joinchat_obj));
	</script>
</body>

</html>
