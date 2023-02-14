<?php
/**
 * Registers the Alert post type and handles display of the alert bar.
 *
 * @package HP_Alerts
 */

namespace HP\Alerts;

add_action( 'init', __NAMESPACE__ . '\register_meta' );

/**
 * Retrieve the post types with Alerts support.
 *
 * @return array A list of post types.
 */
function get_post_types(): array {
	$post_types = array(
		'post',
	);

	return apply_filters( 'alerts_get_post_types', $post_types );
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
			]
		);
	}
}
