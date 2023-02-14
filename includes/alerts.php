<?php
/**
 * Manage common alerts functionality.
 *
 * @package HP_Alerts
 */

namespace HP\Alerts;

use HP\Alerts\Taxonomy\AlertLevel;

add_action( 'init', __NAMESPACE__ . '\register_meta' );
add_action( 'updated_post_meta', __NAMESPACE__ . '\store_display_through', 10, 4 );
add_action( 'added_post_meta', __NAMESPACE__ . '\store_display_through', 10, 4 );
add_action( 'shutdown', __NAMESPACE__ . '\check_expired' );
add_action( 'hp_alerts_process_expired', __NAMESPACE__ . '\process_expired' );

/**
 * Retrieve the post types with Alerts support.
 *
 * @return array A list of post types.
 */
function get_post_types(): array {
	$post_types = [
		'post',
	];

	/**
	 * Filters the list of post types that support alerts.
	 *
	 * @since 2.0.0
	 *
	 * @param string[] $post_types An array of post type keys.
	 */
	return apply_filters( 'hp_alerts_get_post_types', $post_types );
}

/**
 * Register the meta key used to capture the display through date.
 */
function register_meta() {
	foreach ( get_post_types() as $post_type ) {
		register_post_meta(
			$post_type,
			'_hp_alert_display_through',
			[
				'show_in_rest'  => true,
				'auth_callback' => '__return_true',
				'single'        => true,
				'type'          => 'number',
			]
		);
	}
}

/**
 * Store an alert's display through meta in a transient for quicker
 * expiration resolution.
 *
 * @param int    $meta_id    Unused. The meta ID.
 * @param int    $post_id    The current post ID.
 * @param string $meta_key   The current meta key.
 * @param mixed  $meta_value The meta value being stored.
 */
function store_display_through( $meta_id, $post_id, $meta_key, $meta_value ) {
	if ( '_hp_alert_display_through' !== $meta_key ) {
		return;
	}

	if ( ! in_array( get_post( $post_id )->post_type, get_post_types(), true ) ) {
		return;
	}

	$current_alerts             = get_transient( 'hp_active_alerts' );
	$current_alerts[ $post_id ] = $meta_value;

	set_transient( 'hp_active_alerts', $current_alerts );
}

/**
 * Check current alerts on shutdown and schedule an event to clear any expired.
 */
function check_expired() {
	if ( wp_next_scheduled( 'hp_alerts_process_expired' ) ) {
		return;
	}

	$current_alerts = get_transient( 'hp_active_alerts' );

	if ( ! $current_alerts ) {
		return;
	}

	$now = time() + 30;

	foreach ( $current_alerts as $expiration ) {
		if ( $now >= (int) $expiration ) {
			wp_schedule_single_event( $now, 'hp_alerts_process_expired' );
		}
	}
}

/**
 * Process expired alerts.
 */
function process_expired() {
	global $wpdb;

	$alerts = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_hp_alert_display_through'" );

	$default_alert_level = AlertLevel\get_default_term_id();

	$current_alerts = [];

	foreach ( $alerts as $alert ) {
		// Capture alerts that are still valid.
		if ( time() < (int) $alert->meta_value ) {
			$current_alerts[ $alert->post_id ] = $alert->meta_value;
		} else {
			// Remove the expiration date.
			delete_post_meta( $alert->post_id, '_hp_alert_display_through' );

			// Apply the default alert level, or remove all alert levels if no
			// default is available.
			wp_set_object_terms( $alert->post_id, $default_alert_level, AlertLevel\get_slug() );
		}
	}

	set_transient( 'hp_active_alerts', $current_alerts );
}
