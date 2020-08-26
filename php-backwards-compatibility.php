<?php

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

/**
 * Deactivates the plugin
 *
 * @return bool
 */
function zozoema_top_bar_deactivate_self() {

	if( ! current_user_can( 'activate_plugins' ) ) {
		return false;
	}

	// deactivate self
	deactivate_plugins( plugin_basename( ZOZOEMA_TOP_BAR_FILE ) );

	// get rid of "Plugin activated" notice
	if( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}

	// show notice to user
	add_action( 'admin_notices', 'zozoema_top_bar_php_requirement_notice' );

	return true;
}

/**
 * Outputs a notice telling the user that the plugin deactivated itself
 */
function zozoema_top_bar_php_requirement_notice() {
	?>
	<div class="updated">
		<p><?php _e( 'ZozoEMA Top Bar did not activate because it requires your server to run PHP 5.3 or higher.', 'zozoema-top-bar' ); ?></p>
	</div>
	<?php
}

// Hook into `admin_init`
add_action( 'admin_init', 'zozoema_top_bar_deactivate_self' );
