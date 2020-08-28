<?php
/*
Plugin Name: EMA4WP: ZozoEMA Top Bar
Plugin URI: https://www.ema4wp.com/#utm_source=wp-plugin&utm_medium=zozoema-top-bar&utm_campaign=plugins-page
Description: Adds a ZozoEMA opt-in bar to the top of your site.
Version: 1.5.2
Author: ibericode
Author URI: https://ibericode.com/
Text Domain: zozoema-top-bar
Domain Path: /languages
License: GPL v3
Requires PHP: 5.3

ZozoEMA Top Bar
Copyright (C) 2015-2020, Danny van Kooten, hi@dannyvankooten.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 1 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}


/**
 * Loads the ZozoEMA Top Bar plugin
 *
 * @ignore
 * @access private
 */
function _load_zozoema_top_bar() {
	// check deps
	$ready = include __DIR__ . '/dependencies.php';
	if( ! $ready ) {
		return;
	}

	define('ZOZOEMA_TOP_BAR_FILE', __FILE__);
	define('ZOZOEMA_TOP_BAR_DIR', __DIR__);
	define('ZOZOEMA_TOP_BAR_VERSION', '1.0.1');

	// create instance
	require_once __DIR__ . '/bootstrap.php';
}

if( version_compare( PHP_VERSION, '5.3', '<' ) ) {
	require_once dirname( __FILE__ ) . '/php-backwards-compatibility.php';
} else {
	add_action( 'plugins_loaded', '_load_zozoema_top_bar', 30 );
}
