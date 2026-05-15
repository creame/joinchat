<?php
/**
 * Joinchat premium addons
 *
 * @since      5.0.0
 * @package    Joinchat
 * @subpackage Joinchat/admin
 * @author     Creame <hola@crea.me>
 */

defined( 'WPINC' ) || exit;
?>

<div class="joinchat-premium">
	<h2 class="joinchat-premium__title"><?php esc_html_e( 'Are you still handling WhatsApp messages manually, one by one?', 'creame-whatsapp-me' ); ?></h2>
	<p class="joinchat-premium__description">
		<?php
		echo wp_kses(
			/* translators: %s: Joinchat brand name */
			sprintf( __( 'Today, 800K+ businesses in 176 countries use %s', 'creame-whatsapp-me' ), '<span class="joinchat-premium__brand">Joinchat</span>' ),
			array( 'span' => array( 'class' => array() ) )
		);
		?>
	</p>
	<ul>
		<li>
			<img src="<?php echo esc_url( plugin_dir_url( JOINCHAT_FILE ) . 'admin/img/ad-omnichannel.webp' ); ?>" width="400" height="250" loading="lazy" alt="">
			<span><?php esc_html_e( 'Add more contact channels', 'creame-whatsapp-me' ); ?></span>
			<a href="<?php echo esc_url( Joinchat_Util::link( 'premium/omnichannel', 'upselltab' ) ); ?>" target="_blank"><?php esc_html_e( 'Upgrade Now', 'creame-whatsapp-me' ); ?></a>
		</li>
		<li>
			<img src="<?php echo esc_url( plugin_dir_url( JOINCHAT_FILE ) . 'admin/img/ad-agents.webp' ); ?>" width="400" height="250" loading="lazy" alt="">
			<span><?php esc_html_e( 'Create as many agents as you want', 'creame-whatsapp-me' ); ?></span>
			<a href="<?php echo esc_url( Joinchat_Util::link( 'premium/support-agents', 'upselltab' ) ); ?>" target="_blank"><?php esc_html_e( 'Upgrade Now', 'creame-whatsapp-me' ); ?></a>
		</li>
		<li>
			<img src="<?php echo esc_url( plugin_dir_url( JOINCHAT_FILE ) . 'admin/img/ad-agents-schedule.webp' ); ?>" width="400" height="250" loading="lazy" alt="">
			<span><?php esc_html_e( 'Set up schedules and shifts for each day', 'creame-whatsapp-me' ); ?></span>
			<a href="<?php echo esc_url( Joinchat_Util::link( 'premium/support-agents', 'upselltab' ) ); ?>" target="_blank"><?php esc_html_e( 'Upgrade Now', 'creame-whatsapp-me' ); ?></a>
		</li>
		<li>
			<img src="<?php echo esc_url( plugin_dir_url( JOINCHAT_FILE ) . 'admin/img/ad-random-phone.webp' ); ?>" width="400" height="250" loading="lazy" alt="">
			<span><?php esc_html_e( 'Add as many WhatsApp numbers as you need', 'creame-whatsapp-me' ); ?></span>
			<a href="<?php echo esc_url( Joinchat_Util::link( 'premium/random-phone', 'upselltab' ) ); ?>" target="_blank"><?php esc_html_e( 'Upgrade Now', 'creame-whatsapp-me' ); ?></a>
		</li>
		<li>
			<img src="<?php echo esc_url( plugin_dir_url( JOINCHAT_FILE ) . 'admin/img/ad-chat-funnels.webp' ); ?>" width="400" height="250" loading="lazy" alt="">
			<span><?php esc_html_e( 'Create chat funnels, and capture key data', 'creame-whatsapp-me' ); ?></span>
			<a href="<?php echo esc_url( Joinchat_Util::link( 'premium/chatfunnel', 'upselltab' ) ); ?>" target="_blank"><?php esc_html_e( 'Upgrade Now', 'creame-whatsapp-me' ); ?></a>
		</li>
		<li>
			<img src="<?php echo esc_url( plugin_dir_url( JOINCHAT_FILE ) . 'admin/img/ad-ai.webp' ); ?>" width="400" height="250" loading="lazy" alt="">
			<span><?php esc_html_e( 'Add an AI agent and automate up to 80% of inquiries', 'creame-whatsapp-me' ); ?></span>
			<a href="<?php echo esc_url( Joinchat_Util::link( 'ai', 'upselltab' ) ); ?>" target="_blank"><?php esc_html_e( 'Upgrade Now', 'creame-whatsapp-me' ); ?></a>
		</li>
	</ul>

	<p class="joinchat-premium__description">
		<?php esc_html_e( 'Unlock dozens more features for your business', 'creame-whatsapp-me' ); ?>
		<a href="<?php echo esc_url( Joinchat_Util::link( 'wp-coupon', 'upselltab' ) ); ?>" target="_blank" class="joinchat-premium__button"><?php esc_html_e( 'Upgrade Now', 'creame-whatsapp-me' ); ?></a>
	</p>
</div>
<style>

#joinchat_form:has(#joinchat_tab_premium.joinchat-tab-active) p.submit,
#joinchat_form:has(#joinchat_tab_premium.joinchat-tab-active) #joinchat_preview_show {
	display: none;
}

.joinchat-premium {
	--check: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGZpbGw9Im5vbmUiIHZpZXdCb3g9IjAgMCAxNiAxMiI+PHBhdGggZmlsbD0iIzAwYzkwNyIgZD0ibTE1Ljg1IDEuMTUtMS0xQS41LjUgMCAwIDAgMTQuMi4xTDUuNSA2Ljg3IDEuOCA0LjFhLjUuNSAwIDAgMC0uNjUuMDVsLTEgMWEuNS41IDAgMCAwLS4wMy42N2w1IDZxLjE0LjE2LjM2LjE4aC4wMmEuNS41IDAgMCAwIC4zNS0uMTVsMTAtMTBhLjUuNSAwIDAgMCAwLS43Ii8+PC9zdmc+");
	max-width: 1300px;
	margin: 0 auto;
	padding: 40px 24px;
	text-align: center;
}

.joinchat-premium__title {
	color: #000;
	margin: 0 0 16px;
	font-weight: 800;
	font-size: 1.5rem;
	line-height: 1.5;
}

.joinchat-premium__description {
	margin: 0 0 32px;
	font-size: 20px;
}

.joinchat-premium__brand {
	color: #F9603A;
	font-weight: 700;
}

.joinchat-premium ul {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 30px;
	list-style: none;
	padding: 0;
	margin: 0 0 32px;
	text-align: left;
}

.joinchat-premium li {
	position: relative;
	display: flex;
	flex-direction: column;
	overflow: hidden;
	border: 6px solid #1a1a1a;
	border-radius: 12px;
	background: #1a1a1a;
	box-shadow: 0 1px 6px rgba(0, 0, 0, 0.25);
}

.joinchat-premium li img {
	width: 100%;
	height: auto;
	display: block;
}

.joinchat-premium li span {
	flex: 1;
	display: flex;
	align-items: center;
	gap: 6px;
	min-height: 24px;
	padding: 6px 4px 0;
	color: #fff;
	font-weight: 600;
	font-size: 14px;
	line-height: 1.3;
}

.joinchat-premium li span::before,
.joinchat-premium__description:last-child::before {
	content: "";
	display: inline-block;
	flex-shrink: 0;
	width: 16px;
	height: 12px;
	background: var(--check) center / 16px no-repeat;
}

.joinchat-premium li > a,
.joinchat-premium__button {
	background: linear-gradient(to bottom, #ff9500 0%, #f9603a 50%) top/100% 200%;
	border-radius: 4px;
	color: #000;
	font-weight: 700;
	text-decoration: none !important;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.35);
	transition: all 0.2s;
}

.joinchat-premium li > a {
	position: absolute;
	top: 12px;
	right: 12px;
	padding: 6px 14px;
	font-size: 12px;
	opacity: 0;
	transition: all 0.3s ease;
}

.joinchat-premium li:hover > a {
	opacity: 1;
}

.joinchat-premium li > a:hover {
	background-position: bottom;
	box-shadow: 0 1px 5px rgba(0, 0, 0, 0.7);
}

.joinchat-premium__button {
	display: inline-block;
	padding: 3px 26px;
	color: #fff;
	font-weight: 400;
}

.joinchat-premium__button:hover,
.joinchat-premium__button:active,
.joinchat-premium__button:focus {
	color: #fff !important;
	background-position: bottom;
	box-shadow: 0 1px 5px rgba(0, 0, 0, 0.7);
}

.joinchat-premium__description:last-child {
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
	align-items: center;
	justify-content: center;
	gap: 16px;
}

@media (max-width: 900px) {
	.joinchat-premium ul {
		grid-template-columns: repeat(2, 1fr);
		gap: 10px;
	}
}

@media (max-width: 580px) {
	.joinchat-premium ul {
		grid-template-columns: 1fr;
	}

	.joinchat-premium__title {
		font-size: 1.25rem;
	}
}
</style>
