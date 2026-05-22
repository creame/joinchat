<?php
/**
 * Track Joinchat clicks.
 *
 * @package Joinchat
 */

defined( 'WPINC' ) || exit;

/**
 * Joinchat tracking class.
 *
 * @since      6.2.0
 * @package    Joinchat
 * @subpackage Joinchat/includes
 * @author     Creame <hola@crea.me>
 */
class Joinchat_Tracking {

	/**
	 * Option name used to store daily click counters.
	 */
	const OPTION_NAME = 'joinchat_tracking_clicks';

	/**
	 * Option name used to store recent event fingerprints for deduplication.
	 */
	const OPTION_DEDUP = 'joinchat_tracking_dedup';

	/**
	 * REST namespace.
	 */
	const REST_NAMESPACE = 'joinchat/v1';

	/**
	 * REST route.
	 */
	const REST_ROUTE = '/track-click';

	/**
	 * REST nonce action.
	 */
	const NONCE_ACTION = 'joinchat_rest';

	/**
	 * Deduplication window in seconds.
	 */
	const DEDUP_WINDOW = 60;

	/**
	 * Cache for tracking enabled status.
	 *
	 * @var bool|null
	 */
	private $is_enabled = null;

	/**
	 * Register REST routes.
	 *
	 * @since 6.2.0
	 * @return void
	 */
	public function register_rest_routes() {

		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_track_click' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Register dashboard widget.
	 *
	 * @since 6.2.0
	 * @return void
	 */
	public function register_dashboard_widget() {

		wp_add_dashboard_widget(
			'joinchat_tracking_widget',
			esc_html__( 'Joinchat Clicks', 'creame-whatsapp-me' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget.
	 *
	 * @since 6.2.0
	 * @return void
	 */
	public function render_dashboard_widget() {

		$clicks = $this->get_clicks();
		$total  = array_sum( array_map( 'intval', $clicks ) );
		$today  = $this->get_day_clicks( current_time( 'Ymd' ) );
		$week   = $this->get_period_clicks( 7 );
		$rows   = $this->get_recent_rows( 30 );

		?>
		<div class="joinchat-tracking-widget">
			<div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:12px;">
				<div><strong><?php esc_html_e( 'Total clicks', 'creame-whatsapp-me' ); ?>:</strong> <?php echo esc_html( number_format_i18n( $total ) ); ?></div>
				<div><strong><?php esc_html_e( 'Today', 'creame-whatsapp-me' ); ?>:</strong> <?php echo esc_html( number_format_i18n( $today ) ); ?></div>
				<div><strong><?php esc_html_e( 'Last 7 days', 'creame-whatsapp-me' ); ?>:</strong> <?php echo esc_html( number_format_i18n( $week ) ); ?></div>
			</div>

			<div style="margin-top:4px;">
				<?php echo $this->render_chart_svg( $rows ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php
	}

	/**
	 * REST callback that saves a click.
	 *
	 * @since 6.2.0
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_track_click( WP_REST_Request $request ) {

		if ( ! $this->is_enabled() ) {
			return new WP_Error( 'joinchat_tracking_disabled', 'Tracking is disabled.', array( 'status' => 400 ) );
		}

		// Fail silently for invalid requests to avoid exposing endpoint details to bots/scripts.
		if ( self::requires_nonce() && ! $this->verify_nonce( $request ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				return new WP_Error( 'joinchat_invalid_nonce', 'Invalid nonce.', array( 'status' => 403 ) );
			}
			return rest_ensure_response( array( 'success' => true ) );
		}

		if ( ! is_user_logged_in() && $this->is_known_bot( $request ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				return new WP_Error( 'joinchat_bot_detected', 'Bot or crawler detected.', array( 'status' => 403 ) );
			}
			return rest_ensure_response( array( 'success' => true ) );
		}

		if ( ! is_user_logged_in() && $this->is_duplicate_event( $request ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				return new WP_Error( 'joinchat_duplicate_event', 'Duplicate event detected.', array( 'status' => 429 ) );
			}
			return rest_ensure_response( array( 'success' => true ) );
		}

		$data = array(
			'trigger'      => sanitize_key( (string) $request->get_param( 'trigger' ) ),
			'chat_channel' => sanitize_text_field( (string) $request->get_param( 'chat_channel' ) ),
			'chat_id'      => sanitize_text_field( (string) $request->get_param( 'chat_id' ) ),
			'is_mobile'    => filter_var( $request->get_param( 'is_mobile' ), FILTER_VALIDATE_BOOLEAN ) ? 1 : 0,
			'day'          => current_time( 'Ymd' ),
		);

		$data = (array) apply_filters( 'joinchat_track_click_data', $data, $request );
		$day  = isset( $data['day'] ) && preg_match( '/^\d{8}$/', (string) $data['day'] ) ? (string) $data['day'] : current_time( 'Ymd' );

		$count = $this->increment_day_clicks( $day );

		do_action( 'joinchat_track_click', $data, $count, $request );

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Get REST URL for the tracking endpoint.
	 *
	 * @since 6.2.0
	 * @return string
	 */
	public static function rest_url() {

		return rest_url( self::REST_NAMESPACE . self::REST_ROUTE );
	}

	/**
	 * Check whether nonce verification is enabled.
	 *
	 * @since 6.2.0
	 * @return bool
	 */
	public static function requires_nonce() {

		return (bool) apply_filters( 'joinchat_tracking_require_nonce', is_user_logged_in() );
	}

	/**
	 * Check if tracking is enabled in settings.
	 *
	 * @since 6.2.0
	 * @return bool
	 */
	public function is_enabled() {

		if ( null !== $this->is_enabled ) {
			return $this->is_enabled;
		}

		$settings = (array) get_option( JOINCHAT_SLUG, array() );

		$this->is_enabled = ! isset( $settings['tracking'] ) || 'yes' === $settings['tracking'];

		return $this->is_enabled;
	}

	/**
	 * Verify the REST nonce.
	 *
	 * @since 6.2.0
	 * @param WP_REST_Request $request Request instance.
	 * @return bool
	 */
	private function verify_nonce( WP_REST_Request $request ) {

		$nonce = (string) $request->get_param( 'nonce' );
		if ( '' === $nonce ) {
			$nonce = (string) $request->get_header( 'X-WP-Nonce' );
		}

		return (bool) wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}

	/**
	 * Check if request is from a known bot/crawler/automation tool.
	 *
	 * Detects common bots and scripts that shouldn't trigger click events.
	 * This prevents automated crawlers, monitoring tools, and scripts from
	 * inflating click metrics.
	 *
	 * @since 6.2.2
	 * @param WP_REST_Request $request Request instance.
	 * @return bool True if request is from a known bot, false otherwise.
	 */
	private function is_known_bot( WP_REST_Request $request ) {

		$user_agent = strtolower( (string) $request->get_header( 'User-Agent' ) );

		if ( empty( $user_agent ) ) {
			return true;
		}

		$bot_patterns = array(
			// Search engine bots.
			'googlebot',
			'bingbot',
			'slurp',
			'duckduckbot',
			'baiduspider',
			'yandexbot',
			'exabot',
			'facebookexternalhit',
			'twitterbot',
			'linkedinbot',
			'whatsapp',
			'telegrambot',
			'slackbot',
			'discordbot',

			// Monitoring & analytics bots.
			'uptimerobot',
			'pingdom',
			'monitoring',
			'newrelic',
			'datadoghq',
			'elastic',
			'semrush',
			'ahrefs',
			'majestic',
			'similarweb',

			// Common crawlers & scrapers.
			'curl',
			'wget',
			'python',
			'requests',
			'scrapy',
			'selenium',
			'phantomjs',
			'headless',
			'puppeteer',
			'playwright',

			// Other automation.
			'postman',
			'insomnia',
			'apifox',
			'jmeter',
			'appium',
			'okhttp',
			'httpclient',

			// Generic empty or suspicious patterns.
			'bot',
			'crawler',
			'spider',
			'scraper',
			'downloader',
		);

		foreach ( $bot_patterns as $pattern ) {
			if ( false !== stripos( $user_agent, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get client fingerprint hash for deduplication.
	 *
	 * Combines: client IP, user agent, channel, and chat ID.
	 * Used to detect and block automated/duplicate click events.
	 *
	 * @since 6.2.2
	 * @param WP_REST_Request $request Request instance.
	 * @return string SHA256 fingerprint hash.
	 */
	private function get_event_fingerprint( WP_REST_Request $request ) {

		$client_ip    = Joinchat_Util::get_client_ip();
		$user_agent   = (string) $request->get_header( 'User-Agent' );
		$chat_channel = sanitize_text_field( (string) $request->get_param( 'chat_channel' ) );
		$chat_id      = sanitize_text_field( (string) $request->get_param( 'chat_id' ) );

		// Combine all factors into a fingerprint string.
		$fingerprint_data = sprintf(
			'%s|%s|%s|%s',
			empty( $client_ip ) ? 'unknown' : $client_ip,
			$user_agent,
			$chat_channel,
			$chat_id
		);

		return hash( 'sha256', $fingerprint_data );
	}

	/**
	 * Check if an event is a duplicate within the dedup window and store its fingerprint if not.
	 *
	 * @since 6.2.2
	 * @param WP_REST_Request $request Request instance.
	 * @return bool True if event appears to be duplicate, false otherwise.
	 */
	private function is_duplicate_event( WP_REST_Request $request ) {

		$fingerprint = $this->get_event_fingerprint( $request );
		$stored_data = (array) get_option( self::OPTION_DEDUP, array() );

		// Clean expired entries (older than DEDUP_WINDOW).
		$current_time = current_time( 'timestamp' );
		$stored_data  = array_filter(
			$stored_data,
			function( $timestamp ) use ( $current_time ) {
				return ( $current_time - (int) $timestamp ) < self::DEDUP_WINDOW;
			}
		);

		if ( isset( $stored_data[ $fingerprint ] ) ) {
			return true;
		}

		// Add this fingerprint with current timestamp.
		$stored_data[ $fingerprint ] = $current_time;
		update_option( self::OPTION_DEDUP, $stored_data, false );

		return false;
	}

	/**
	 * Increment the click counter for a given day.
	 *
	 * @since 6.2.0
	 * @param string $day Ymd date.
	 * @return int
	 */
	private function increment_day_clicks( $day ) {

		$clicks = $this->get_clicks();

		if ( ! isset( $clicks[ $day ] ) ) {
			$clicks[ $day ] = 0;
		}

		$clicks[ $day ] = (int) $clicks[ $day ] + 1;
		$this->save_clicks( $clicks );

		return (int) $clicks[ $day ];
	}

	/**
	 * Get all stored clicks.
	 *
	 * @since 6.2.0
	 * @return array
	 */
	private function get_clicks() {

		$clicks = get_option( self::OPTION_NAME, array() );

		return is_array( $clicks ) ? $clicks : array();
	}

	/**
	 * Save click counters.
	 *
	 * @since 6.2.0
	 * @param array $clicks Click counters.
	 * @return void
	 */
	private function save_clicks( $clicks ) {

		update_option( self::OPTION_NAME, $clicks, false );
	}

	/**
	 * Get clicks for a specific day.
	 *
	 * @since 6.2.0
	 * @param string $day Ymd date.
	 * @return int
	 */
	private function get_day_clicks( $day ) {

		$clicks = $this->get_clicks();

		return isset( $clicks[ $day ] ) ? (int) $clicks[ $day ] : 0;
	}

	/**
	 * Get total clicks for a period.
	 *
	 * @since 6.2.0
	 * @param int $days Number of days.
	 * @return int
	 */
	private function get_period_clicks( $days ) {

		$total  = 0;
		$clicks = $this->get_clicks();
		$stamp  = strtotime( current_time( 'mysql' ) );

		for ( $i = 0; $i < $days; $i++ ) {
			$day = date_i18n( 'Ymd', $stamp - ( $i * DAY_IN_SECONDS ) );

			if ( isset( $clicks[ $day ] ) ) {
				$total += (int) $clicks[ $day ];
			}
		}

		return $total;
	}

	/**
	 * Get recent rows for dashboard display.
	 *
	 * @since 6.2.0
	 * @param int $days Number of days.
	 * @return array
	 */
	private function get_recent_rows( $days ) {

		$rows   = array();
		$clicks = $this->get_clicks();
		$stamp  = strtotime( current_time( 'mysql' ) );

		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$day    = date_i18n( 'Ymd', $stamp - ( $i * DAY_IN_SECONDS ) );
			$rows[] = array(
				'day'    => $day,
				'clicks' => isset( $clicks[ $day ] ) ? (int) $clicks[ $day ] : 0,
			);
		}

		return $rows;
	}

	/**
	 * Render a SVG chart for recent clicks.
	 *
	 * @since 6.2.0
	 * @param array $rows Daily rows.
	 * @return string
	 */
	private function render_chart_svg( $rows ) {

		if ( empty( $rows ) ) {
			return '<p>' . esc_html__( 'No clicks yet.', 'creame-whatsapp-me' ) . '</p>';
		}

		$width       = 640;
		$height      = 180;
		$padding_x   = 12;
		$padding_y   = 14;
		$chart_width = $width - ( $padding_x * 2 );
		$chart_h     = $height - ( $padding_y * 2 ) - 14;
		$max_value   = max( 1, (int) max( array_column( $rows, 'clicks' ) ) );
		$step        = count( $rows ) > 1 ? $chart_width / ( count( $rows ) - 1 ) : $chart_width;

		$path   = array();
		$base_y = $height - 18;

		foreach ( $rows as $index => $row ) {
			$value = (int) $row['clicks'];
			$x     = $padding_x + ( $index * $step );
			$y     = $padding_y + ( $chart_h - ( ( $value / $max_value ) * $chart_h ) );

			if ( 0 === $index ) {
				$path[] = sprintf( 'M %1$.2f %2$.2f', $x, $y );
			} else {
				$path[] = sprintf( 'L %1$.2f %2$.2f', $x, $y );
			}
		}

		$start_x      = $padding_x;
		$end_x        = $padding_x + ( ( count( $rows ) - 1 ) * $step );
		$y_ticks      = 2;
		$y_label_x    = 2;
		$y_scale_rows = array();
		$seen_values  = array();

		for ( $i = 0; $i <= $y_ticks; $i++ ) {
			$tick_value = (int) round( $max_value * ( 1 - ( $i / $y_ticks ) ) );

			// Avoid duplicated labels for small ranges (e.g. max=1).
			if ( isset( $seen_values[ $tick_value ] ) ) {
				continue;
			}

			$seen_values[ $tick_value ] = true;
			$y_scale_rows[]             = array(
				'value' => $tick_value,
				'y'     => $padding_y + ( $chart_h - ( ( $tick_value / $max_value ) * $chart_h ) ),
			);
		}

		$area_path = implode( ' ', $path ) . sprintf( ' L %.2f %.2f L %.2f %.2f Z', $end_x, $base_y, $start_x, $base_y );

		ob_start();
		?>
<div style="background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:10px 8px 0;">
	<style>
		#joinchat_tracking_widget .postbox-header h2 { justify-content:flex-start; }
		#joinchat_tracking_widget .postbox-header h2::before {content:''; width:20px; height:20px; margin:-2px 8px 0 -4px; background:url(<?php echo esc_url( plugin_dir_url( JOINCHAT_FILE ) . 'admin/img/menu-icon.svg' ); ?>) !important; filter:invert(1); }
		.joinchat-tracking-svg .chart-y-grid { stroke:#e2e4e7; stroke-width:1; }
		.joinchat-tracking-svg .chart-y-label { text-anchor:start; font-size:11px; fill:#646970; }
		.joinchat-tracking-svg .chart-dot { stroke:#3a87c6; stroke-width:1.5; }
		.joinchat-tracking-svg .chart-tip-box { fill:#fff; stroke:#dcdcde; stroke-width:1; }
		.joinchat-tracking-svg .chart-tip-day { text-anchor:middle; font-size:13px; font-weight:500; fill:#333; }
		.joinchat-tracking-svg .chart-tip-num { text-anchor:middle; font-size:14px; font-weight:600; fill:#1d1d1d; }
		.joinchat-tracking-svg .chart-point { cursor:pointer; }
		.joinchat-tracking-svg .chart-tip { opacity:0; transition:opacity 0.2s ease-in-out; pointer-events:none; }
		.joinchat-tracking-svg .chart-point:hover .chart-tip { opacity:1; }
	</style>
	<svg class="joinchat-tracking-svg" viewBox="0 0 <?php echo (int) $width; ?> <?php echo (int) $height; ?>" role="img" aria-label="<?php echo esc_attr__( 'Joinchat clicks over the last 30 days', 'creame-whatsapp-me' ); ?>" style="display:block;width:100%;height:auto;overflow:visible;">
		<?php foreach ( $y_scale_rows as $scale_row ) : ?>
			<?php if ( $scale_row['value'] > 0 ) : ?>
				<line class="chart-y-grid" x1="<?php echo (int) $padding_x; ?>" y1="<?php echo esc_attr( sprintf( '%.2f', $scale_row['y'] ) ); ?>" x2="<?php echo (int) ( $width - $padding_x ); ?>" y2="<?php echo esc_attr( sprintf( '%.2f', $scale_row['y'] ) ); ?>" />
			<?php endif; ?>
			<text class="chart-y-label" x="<?php echo (int) $y_label_x; ?>" y="<?php echo esc_attr( sprintf( '%.2f', $scale_row['y'] - 2 ) ); ?>"><?php echo esc_html( number_format_i18n( $scale_row['value'] ) ); ?></text>
		<?php endforeach; ?>
		<line x1="<?php echo (int) $padding_x; ?>" y1="<?php echo (int) $base_y; ?>" x2="<?php echo (int) ( $width - $padding_x ); ?>" y2="<?php echo (int) $base_y; ?>" stroke="#c3c4c7" stroke-width="1" />
		<path d="<?php echo esc_attr( $area_path ); ?>" fill="#3a87c6" fill-opacity="0.15" />
		<path d="<?php echo esc_attr( implode( ' ', $path ) ); ?>" fill="none" stroke="#3a87c6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
		<?php foreach ( $rows as $index => $row ) : ?>
			<?php
			$value     = (int) $row['clicks'];
			$x         = $padding_x + ( $index * $step );
			$y         = $padding_y + ( $chart_h - ( ( $value / $max_value ) * $chart_h ) );
			$fill      = $value > 0 ? '#3a87c6' : '#fff';
			$day_label = $this->format_day( $row['day'] );
			/* translators: %d: number of clicks. */
			$clicks_label = sprintf( _n( '%d click', '%d clicks', $value, 'creame-whatsapp-me' ), $value );
			?>
			<g class="chart-point">
				<circle class="chart-dot" cx="<?php echo esc_attr( sprintf( '%.2f', $x ) ); ?>" cy="<?php echo esc_attr( sprintf( '%.2f', $y ) ); ?>" r="4" fill="<?php echo esc_attr( $fill ); ?>" />
				<g class="chart-tip">
					<rect class="chart-tip-box" x="<?php echo esc_attr( sprintf( '%.2f', $x - 42 ) ); ?>" y="<?php echo esc_attr( sprintf( '%.2f', $y - 48 ) ); ?>" width="84" height="50" rx="4" />
					<text class="chart-tip-day" x="<?php echo esc_attr( sprintf( '%.2f', $x ) ); ?>" y="<?php echo esc_attr( sprintf( '%.2f', $y - 28 ) ); ?>"><?php echo esc_html( $day_label ); ?></text>
					<text class="chart-tip-num" x="<?php echo esc_attr( sprintf( '%.2f', $x ) ); ?>" y="<?php echo esc_attr( sprintf( '%.2f', $y - 12 ) ); ?>"><?php echo esc_html( $clicks_label ); ?></text>
				</g>
			</g>
		<?php endforeach; ?>
	</svg>
</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Format a Ymd day for display.
	 *
	 * @since 6.2.0
	 * @param string $day Ymd date.
	 * @return string
	 */
	private function format_day( $day ) {

		if ( 8 !== strlen( $day ) ) {
			return $day;
		}

		$timestamp = strtotime( substr( $day, 0, 4 ) . '-' . substr( $day, 4, 2 ) . '-' . substr( $day, 6, 2 ) );
		return date_i18n( 'd M Y', $timestamp );
	}
}
