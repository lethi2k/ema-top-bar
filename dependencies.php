<?php

// check for ZozoEMA for WordPress (version 1.0 or higher)
if( defined( 'EMA4WP_VERSION' ) && version_compare( EMA4WP_VERSION, '1.0', '>=' ) ) {
	return true;
}

// Show notice to user
add_action( 'admin_notices',  function() {

	// only show to user with caps
	if( ! current_user_can( 'install_plugins' ) ) {
		return;
	}

	add_thickbox();
	$url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=zozoema-for-wp&TB_iframe=true&width=600&height=550' );
	?>
	<div class="notice notice-warning is-dismissible">
		<p><?php printf( __( 'Please install or update <a href="%s" class="thickbox">%s</a> in order to use %s.', 'zozoema-top-bar' ), $url, '<strong>ZozoEMA for WordPress</strong> (version 3.0 or higher)', 'ZozoEMA Top Bar' ); ?></p>
	</div>
<?php
} );

// tell plugin not to proceed
return false;
