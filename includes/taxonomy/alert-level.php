<?php
/**
 * Manage the alert level taxonomy.
 *
 * @package alerts
 */

namespace HP\Alerts\Taxonomy\AlertLevel;

use HP\Alerts;

add_action( 'init', __NAMESPACE__ . '\register_taxonomy' );

/**
 * Provide the slug used to register the taxonomy.
 *
 * @return string The taxonomy slug.
 */
function get_slug(): string {
	return 'alert_level';
}

/**
 * Register the alert level taxonomy.
 */
function register_taxonomy() {
	\register_taxonomy(
		get_slug(),
		Alerts\get_post_types(),
		[
			'labels'       => [
				'name'                       => 'Alert Levels',
				'singular_name'              => 'Alert Level',
				'search_items'               => 'Search Alert Levels',
				'all_items'                  => 'All Levels',
				'edit_item'                  => 'Edit Level',
				'view_item'                  => 'View Level',
				'update_item'                => 'Update Level',
				'add_new_item'               => 'Add New Level',
				'new_item_name'              => 'New Level Name',
				'seperate_items_with_commas' => 'Separate levels with commas',
				'add_or_remove_items'        => 'Add or remove levels',
				'not_found'                  => 'No levels found',
				'no_terms'                   => 'No alert levels',
			],
			'public'       => true,
			'hierarchical' => false,
			'has_archive'  => false,
			'show_in_rest' => true,
		]
	);
}
