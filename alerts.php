<?php
/**
 * Plugin Name: Alerts
 * Plugin URI: https://github.com/happyprime/alerts
 * Description: Display alerts of various levels for a given amount of time.
 * Author: Happy Prime
 * Author URI: https://happyprime.co/
 * Version: 2.0.0
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 *  @package HP_Alerts
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// This plugin, like WordPress, requires PHP 5.6 and higher.
if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
	add_action( 'admin_notices', 'hp_alerts_admin_notice' );

	/**
	 * Display an admin notice if PHP is not 5.6.
	 */
	function hp_alerts_admin_notice() {
		echo '<div class="error"><p>';
		esc_html_e( 'Alerts requires PHP 5.6 to function properly. Please upgrade PHP or deactivate the plugin.', 'hp-alerts' );
		echo '</p></div>';
	}

	return;
}

/**
 * Provides a versioned transient key for getting and setting alert data.
 *
 * @return string Current alert transient key.
 */
function hp_get_alerts_transient_key() {
	return 'hp_alert_data_002';
}

require_once __DIR__ . '/includes/alerts.php';
