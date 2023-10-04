<?php
/**
 * Plugin Name: Alerts
 * Plugin URI: https://github.com/happyprime/alerts
 * Description: Display alerts of various levels for a given amount of time.
 * Version: 2.0.0
 * Requires at least: 6.1
 * Requires PHP: 7.4
 * Author: Happy Prime
 * Author URI: https://happyprime.co/
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hp-alerts
 *
 *  @package HP_Alerts
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'HP_ALERTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HP_ALERTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/includes/alerts.php';
require_once __DIR__ . '/includes/taxonomy/alert-level.php';
