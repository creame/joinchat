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

		.joinchat__open__text:empty,
		.joinchat__bubble:empty,
		.joinchat__optin:empty {
			display: none;
		}

		.joinchat--disabled {
			opacity: .2;
			pointer-events: none;
		}

		.joinchat__bubble {
			animation: none;
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
((window, document, joinchat_obj) => {
	'use strict';

	joinchat_obj = {
		$div: null,
		settings: null,
		has_chatbox: false,
		chatbox: false,
		showed_at: 0,
		is_ready: false,
		is_mobile: /Mobile|Android|iPhone|iPad/i.test(navigator.userAgent),
		can_qr: window.QrCreator && typeof QrCreator.render === 'function',
		...joinchat_obj
	};
	window.joinchat_obj = joinchat_obj; // Save global

	// querySelector alias
	joinchat_obj.$ = function (selector) {
		return this.$div.querySelector(selector);
	};

	// querySelectorAll alias
	joinchat_obj.$$ = function (selector) {
		return this.$div.querySelectorAll(selector);
	};

	// Return WhatsApp link with optional message
	joinchat_obj.get_wa_link = function (phone, message, wa_web) {
		message = message !== undefined ? message : this.settings.message_send || '';
		wa_web = wa_web !== undefined ? wa_web : this.settings.whatsapp_web && !this.is_mobile;

		const url = new URL(`${wa_web ? 'https://web.whatsapp.com/send?phone=' : 'https://wa.me/'}${phone || this.settings.telephone}`);
		if (message) url.searchParams.set('text', message);

		return url.toString();
	};

	// Open Chatbox and trigger event
	joinchat_obj.chatbox_show = function () {
		if (this.chatbox) return;

		this.chatbox = true;
		this.showed_at = Date.now(); // Avoid faux clicks
		this.$div.classList.add('joinchat--chatbox');

		if (this.settings.message_badge) {
			this.$('.joinchat__badge').classList.replace('joinchat__badge--in', 'joinchat__badge--out');
		}

		document.dispatchEvent(new Event('joinchat:show'));
	};

	// Close Chatbox and trigger event
	joinchat_obj.chatbox_hide = function () {
		if (!this.chatbox) return;

		this.chatbox = false;
		this.$div.classList.remove('joinchat--chatbox', 'joinchat--tooltip');

		if (this.settings.message_badge) {
			this.$('.joinchat__badge').classList.remove('joinchat__badge--out');
		}

		document.dispatchEvent(new Event('joinchat:hide'));
	};

	// Open WhatsApp link with supplied phone and message or with settings defaults
	joinchat_obj.open_whatsapp = function (phone, message) {
		phone = phone || this.settings.telephone;
		message = message !== undefined ? message : this.settings.message_send || '';

		// Open WhatsApp link
		window.open(this.get_wa_link(phone, message), 'joinchat', 'noopener');
	};

	// Opt-in needed
	joinchat_obj.need_optin = function () {
		return this.$div.classList.contains('joinchat--optout');
	};

	// Show Chatbox or open WhatsApp
	joinchat_obj.open = function (direct, phone, message) {
		if ((direct && !this.need_optin()) || !joinchat_obj.$('.joinchat__chatbox')) {
			if (Date.now() < joinchat_obj.showed_at + 600) return; // Avoid trigger WA on auto show chatbox
			this.open_whatsapp(phone, message);
		} else {
			this.chatbox_show();
		}
	};

	// Close Chatbox (saving hash)
	joinchat_obj.close = function () {
		this.chatbox_hide();
	};

	// Generate QR canvas
	joinchat_obj.qr = function (text, options) {
		const canvas = document.createElement('CANVAS');
		QrCreator.render(Object.assign({
			text: text,
			radius: 0.4,
			background: '#FFF',
			size: 200 * (window.devicePixelRatio || 1),
		}, this.settings.qr || {}, options || {}), canvas);
		return canvas;
	};

	// Show Chatbox or open WhatsApp
	joinchat_obj.open = function (direct, phone, message) {
		if ((direct && !this.need_optin()) || !joinchat_obj.$('.joinchat__chatbox')) {
			if (Date.now() < joinchat_obj.showed_at + 600) return; // Avoid trigger WA on auto show chatbox
			this.open_whatsapp(phone, message);
		} else {
			this.chatbox_show();
		}
	};

	let images_cache = {};

	const getImageTag = (src, alt, w) => {
		if (!src) return '<code class="not-found">Image not found</code>';

		const width = w ? ` width="${w}"` : '';
		const altText = alt ? ` alt="${alt}"` : '';
		const cls = w < 340 ? ' class="joinchat--inline"' : '';

		if (src.startsWith('vid|')) return `<video${cls} autoplay loop muted playsinline src="${src.substring(4)}"${width}></video>`;
		else return `<img${cls} src="${src}"${altText}${width}>`;
	};

	const getImage = (src, alt, w) => {
		if (!isNaN(src)) {
			if (images_cache[src] !== undefined){
				return getImageTag(images_cache[src], alt, w);
			}

			wp.apiFetch({ path: `wp/v2/media/${src}/` }).then(media => {
				if (media.mime_type.startsWith('video')) images_cache[src] = `vid|${media.source_url}`;
				else if (media.mime_type.startsWith('image')) images_cache[src] = media.source_url;
				else images_cache[src] = false;

				document.getElementById(`img-${src}`).outerHTML = getImageTag(images_cache[src], alt, w);
			}).catch(() => {
				images_cache[src] = false;

				document.getElementById(`img-${src}`).outerHTML = getImageTag(false, alt, w);
			});

			return `<code id="img-${src}" class="not-found">Loading...</code>`;
		}

		try {
			new URL(src);
		} catch (_) {
			return '<code class="not-found">Image not found</code>';
		}

		return getImageTag(src, alt, w);
	};

	joinchat_obj.update_cta = function (str) {
		// Secure HTML
		str = str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
		// Transform placeholders
		str = str.replace(/{([A-Z]+)}/g, '&lcub;$1&rcub;');

		str = str.replace(/(^|\W)_(.+?)_(\W|$)/gu, '$1<em>$2</em>$3');           // Italic
		str = str.replace(/(^|\W)\*(.+?)\*(\W|$)/gu, '$1<strong>$2</strong>$3'); // Bold
		str = str.replace(/(^|\W)~(.+?)~(\W|$)/gu, '$1<del>$2</del>$3');         // Strikethrough
		str = str.replace(/(^|\W)`(.+?)`(\W|$)/gu, '$1<code>$2</code>$3');       // Code
		str = str.replace(/\n-{3,}\n/gu, '<hr>');                                // Horizontal rule
		str = str.replace(/\n/g, '<br>');                                        // Line breaks

		// Images
		str = str.replace(/!\[(?<alt>.*?)\]\((?<src>[^\) ]*)(?: (?<w>\d+))?\)/gu, (match, alt, src, w) => getImage(src, alt, w));
		str = str.replace(/{IMG (?<src>[^ }]+)(?: (?<w>\d+))?(?: (?<alt>[^}]+))?}/g, (match, src, w, alt) => getImage(src, alt, w));

		// Links
		str = str.replace(/\[([^\]]+)\]\((https?:\/\/[^\s]+)\)/gu, '<a href="$2" target="_blank" rel="noopener">$1</a>');
		str = str.replace(/\{LINK\s+([^\s]+)\s+([^\}]+)\}/gu, '<a href="$1" target="_blank" rel="noopener">$2</a>');
		str = str.replace(/\{BTN\s+([^\s]+)\s+([^\}]+)\}/gu, '<a href="$1" class="joinchat__btn" target="_blank" rel="noopener">$2</a>');

		// Random text
		str = str.replace(/{RAND (?<text>[^}]+)}/g, (match, text) => {
			const options = text.split('||');
			return options[Math.floor(Math.random() * options.length)].trim();
		});

		// Restore placeholders
		str = str.replace(/&lcub;([A-Z]+)&rcub;/g, '{$1}');

		// Bubbles
		str = str.replace(/^(&gt;){3,}<br>/u, '>>>');
		str = str.replace(/<br>(&gt;){3,}<br>/gu, '<br>===<br>>>>');
		str = str.split(/<br>={3,}<br>/).map(bubble => {
			let msg = bubble.trim();
			let cls = 'joinchat__bubble';
			if (msg.startsWith('>>>')) {
				cls += ' joinchat__bubble--note';
				msg = msg.slice(3);
			}

			return msg !== '' ? `<div class="${cls}">${msg}</div>` : '';
		}).join('');

		this.$('.joinchat__chat').replaceChildren();
		this.$('.joinchat__chat').insertAdjacentHTML('afterbegin', str);
		this.$$('.joinchat__bubble').forEach(b => {
			if (b.childNodes.length === 1 && (b.firstChild.className === 'not-found' || b.textContent.trim() === '')) b.classList.add('joinchat__bubble--media');
		});
	};

	function joinchat_magic() {
		joinchat_obj.$div.classList.add('joinchat--show');

		// Open|close Chatbox on click
		joinchat_obj.$('.joinchat__button').addEventListener('click', () => joinchat_obj.open());
		joinchat_obj.$('.joinchat__open').addEventListener('click', () => joinchat_obj.open(true));
		joinchat_obj.$('.joinchat__close').addEventListener('click', () => joinchat_obj.close());

		// Opt-in toggle
		joinchat_obj.$('#joinchat_optin')?.addEventListener('change', (e) => joinchat_obj.$div.classList.toggle('joinchat--optout', !e.target.checked));

		if (joinchat_obj.settings.qr && joinchat_obj.can_qr && !joinchat_obj.is_mobile) {
			joinchat_obj.$('.joinchat__qr').appendChild(joinchat_obj.qr(joinchat_obj.get_wa_link(undefined, undefined, false)));
		}

		joinchat_obj.is_ready = true;
		document.dispatchEvent(new CustomEvent('joinchat:start'));
	}

	const on_page_ready = () => {
		joinchat_obj.$div = document.querySelector('.joinchat');
		joinchat_obj.settings = JSON.parse(joinchat_obj.$div.dataset.settings);
		joinchat_magic();
	};

	// Ready!!
	if (document.readyState !== 'loading') on_page_ready();
	else document.addEventListener('DOMContentLoaded', on_page_ready);

})(window, document, window.joinchat_obj || {});
	</script>
</body>

</html>
