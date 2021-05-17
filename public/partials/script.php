<?php

/**
 * Join.chat public fallback script template
 *
 * @since      4.1.5
 * @package    JoinChat
 * @subpackage JoinChat/public
 * @author     Creame <hola@crea.me>
 */

defined( 'WPINC' ) || exit;
?>

<script>
jQuery(function($){
	var arg = <?php echo JoinChatUtil::to_json( $args ); ?>;
	var via = arg.web && !navigator.userAgent.match(/Android|iPhone|BlackBerry|IEMobile|Opera Mini/i) ? 'web' : 'api';
	$(document).on('click', '.joinchat_open,.joinchat_app,a[href="#whatsapp"],a[href="#joinchat"]', function(e){ e.preventDefault();
		window.open('https://' + via + '.whatsapp.com/send?phone=' + encodeURIComponent(arg.tel) + '&text=' + encodeURIComponent(arg.msg), null, 'noopener');
	});
});
</script>
