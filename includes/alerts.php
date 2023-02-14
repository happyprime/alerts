<?php
/**
 * Manage common alerts functionality.
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
			]
		);
	}
}
