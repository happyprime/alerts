<?php
/**
 * Manage the alert level taxonomy.
 *
 * @package HP_Alerts
 */

namespace HP\Alerts\Taxonomy\AlertLevel;

use HP\Alerts;

add_action( 'init', __NAMESPACE__ . '\register_taxonomy' );
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_block_editor_assets' );

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
			'labels'            => [
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
			'public'            => true,
			'hierarchical'      => false,
			'has_archive'       => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
		]
	);
}

/**
 * Enqueue the script used to manage alert level assignment in the editor.
 */
function enqueue_block_editor_assets() {
	$asset_data = require_once HP_ALERTS_PLUGIN_DIR . '/build/index.asset.php';

	wp_enqueue_script(
		'alert-level-box',
		HP_ALERTS_PLUGIN_URL . '/build/index.js',
		$asset_data['dependencies'],
		$asset_data['version'],
		true
	);
}
