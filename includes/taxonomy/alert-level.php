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
add_filter( 'body_class', __NAMESPACE__ . '\filter_body_class' );
add_action( get_slug() . '_edit_form_fields', __NAMESPACE__ . '\display_edit_form_fields' );
add_action( get_slug() . '_add_form_fields', __NAMESPACE__ . '\display_add_form_fields' );
add_action( 'edit_' . get_slug(), __NAMESPACE__ . '\save_term_meta' );
add_action( 'create_' . get_slug(), __NAMESPACE__ . '\save_term_meta' );

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
				'name'                       => __( 'Alert Levels', 'hp-alerts' ),
				'singular_name'              => __( 'Alert Level', 'hp-alerts' ),
				'search_items'               => __( 'Search Alert Levels', 'hp-alerts' ),
				'all_items'                  => __( 'All Levels', 'hp-alerts' ),
				'edit_item'                  => __( 'Edit Level', 'hp-alerts' ),
				'view_item'                  => __( 'View Level', 'hp-alerts' ),
				'update_item'                => __( 'Update Level', 'hp-alerts' ),
				'add_new_item'               => __( 'Add New Level', 'hp-alerts' ),
				'new_item_name'              => __( 'New Level Name', 'hp-alerts' ),
				'seperate_items_with_commas' => __( 'Separate levels with commas', 'hp-alerts' ),
				'add_or_remove_items'        => __( 'Add or remove levels', 'hp-alerts' ),
				'not_found'                  => __( 'No levels found', 'hp-alerts' ),
				'no_terms'                   => __( 'No alert levels', 'hp-alerts' ),
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
 * Filter the body class to include the alert level when viewing a single
 * supported post type.
 *
 * @param array $classes List of class names to apply to the body element.
 * @return array Modified list of class names.
 */
function filter_body_class( array $classes ): array {
	if ( ! is_singular( Alerts\get_post_types() ) ) {
		return $classes;
	}

	$levels = wp_get_object_terms(
		get_queried_object_id(),
		get_slug(),
		[
			'fields'                 => 'slugs',
			'number'                 => 1,
			'update_term_meta_cache' => false,
		]
	);

	foreach ( $levels as $level ) {
		$classes[] = 'has-alert-level-' . $level;
	}

	return $classes;
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

/**
 * Display a custom field when adding an alert level term.
 */
function display_add_form_fields() {
	wp_nonce_field( 'save_alert_level_meta', 'alert_level_meta' );
	?>
	<div class="form-field">
		<style>.flexed-label { display: flex; gap: 0.5rem; align-items: center;} .flexed-label input { margin-top: 0; }</style>
		<span class="flexed-label">
			<label for="alert-level-default"><?php esc_html_e( 'Default alert level', 'hp-alerts' ); ?></label>
			<input type="checkbox" name="alert_level_default" id="alert-level-default" aria-describedby="alert-level-default-description" />
		</span>
		<p class="description" id="alert-level-default-description"><?php esc_html_e( 'Checking this box will set this alert level as default and clear the setting from other levels.', 'hp-alerts' ); ?></p>
	</div>
	<?php
}

/**
 * Display a custom field when editing an alert level term.
 *
 * @param \WP_Term $term Current taxonomy term object.
 */
function display_edit_form_fields( \WP_Term $term ) {
	$checked = get_term_meta( $term->term_id, 'hp_alert_level_default', true );

	wp_nonce_field( 'save_alert_level_meta', 'alert_level_meta' );
	?>
	<tr class="form-field">
		<th scope="row">
			<label for="alert-level-default"><?php esc_html_e( 'Default alert level', 'hp-alerts' ); ?></label>
		</th>
		<td>
			<input type="checkbox" name="alert_level_default" id="alert-level-default" aria-describedby="alert-level-default-description" <?php checked( $checked ); ?> />
			<p class="description" id="alert-level-default-description"><?php esc_html_e( 'Checking this box will set this alert level as default and clear the setting from other levels.', 'hp-alerts' ); ?></p>
		</td>
	</tr>
	<?php
}

/**
 * Retrieve the current default alert level term ID.
 *
 * @return int The default alert level term ID.
 */
function get_default_term_id(): int {
	global $wpdb;

	$terms = $wpdb->get_results( "SELECT term_id FROM $wpdb->termmeta WHERE meta_key = 'hp_alert_level_default'" );
	$terms = wp_list_pluck( $terms, 'term_id' );

	return (int) array_pop( $terms );
}

/**
 * Clear the database and cache of any previous alert level default setting.
 */
function clear_term_meta() {
	global $wpdb;

	$terms = $wpdb->get_results( "SELECT term_id FROM $wpdb->termmeta WHERE meta_key = 'hp_alert_level_default'" );
	$wpdb->query( "DELETE FROM $wpdb->termmeta WHERE meta_key = 'hp_alert_level_default'" );

	$term_ids = wp_list_pluck( $terms, 'term_id' );
	$term_ids = array_map( 'intval', $term_ids );

	clean_term_cache( $term_ids, get_slug() );
}

/**
 * Save alert level term meta.
 *
 * @param int $term_id The term ID.
 */
function save_term_meta( int $term_id ) {
	if ( ! isset( $_POST['alert_level_meta'] ) || ! wp_verify_nonce( $_POST['alert_level_meta'], 'save_alert_level_meta' ) ) {
		return;
	}

	if ( isset( $_POST['alert_level_default'] ) ) {
		clear_term_meta();

		update_term_meta(
			$term_id,
			'hp_alert_level_default',
			true
		);
	}
}
