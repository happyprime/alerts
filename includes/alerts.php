<?php
/**
 * Registers the Alert post type and handles display of the alert bar.
 *
 * @package HP_Alerts
 */

namespace HP\Alerts;

add_action( 'init', __NAMESPACE__ . '\register_meta' );
add_action( 'save_post_alert', __NAMESPACE__ . '\save_post_meta', 10, 2 );
add_action( 'wp_trash_post', __NAMESPACE__ . '\delete_alert_transient', 10 );
add_action( 'wp_body_open', __NAMESPACE__ . '\display_alert_bar', 10 );

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

/**
 * Adds a meta box for managing alert level and display duration.
 */
function add_meta_boxes() {
	add_meta_box(
		'hp-alert',
		'Alert Settings',
		__NAMESPACE__ . '\display_alert_meta_box',
		'alert',
		'side',
		'high'
	);
}

/**
 * Returns an array of alert level field labels keyed by id.
 *
 * @return array Field values keyed by id.
 */
function get_alert_level_fields() {
	$defaults = array(
		'low'    => __( 'Announcement', 'hp-alerts' ),
		'medium' => __( 'High-level announcement', 'hp-alerts' ),
		'high'   => __( 'Safety alert', 'hp-alerts' ),
	);

	return apply_filters( 'hp_alerts_level_options', $defaults );
}

/**
 * Returns the time until transient expiration in seconds.
 *
 * @param string $display_through Date through which the alert should be shown.
 * @return int Transient expiration in seconds.
 */
function get_expiration( $display_through ) {
	$today   = strtotime( gmdate( 'Y-m-d H:i:s' ) );
	$through = strtotime( $display_through );

	return $through - $today;
}

/**
 * Displays a meta box used to manage alert level and display duration.
 *
 * @param \WP_Post $post The post object.
 */
function display_alert_meta_box( $post ) {
	wp_nonce_field( 'hp_check_alert', 'hp_alert_nonce' );

	// Get existing meta values.
	$through = get_post_meta( $post->ID, '_hp_alert_display_through', true );

	// Set the default minimum as today.
	// Seconds intentionally left out for nicer display in the time input.
	$through_default = explode( ' ', gmdate( 'Y-m-d H:i' ) );

	// Set the default "Display alert through" value as one day from now.
	$through = ( $through ) ? $through : wp_date( 'Y-m-d H:i', strtotime( '+1 day' ) );
	$through = explode( ' ', $through );

	?>
	<p>
		<label for="hp-alert_display-through"><?php esc_html_e( 'Display alert through', 'hp-alerts' ); ?></label>
		<input
			type="date"
			id="hp-alert_display-through-date"
			name="_hp_alert_display_through_date"
			value="<?php echo esc_attr( $through[0] ); ?>"
			min="<?php echo esc_attr( $through_default[0] ); ?>"
		/>
		<input
			type="time"
			id="hp-alert_display-through-time"
			name="_hp_alert_display_through_time"
			value="<?php echo esc_attr( $through[1] ); ?>"
			min="<?php echo esc_attr( $through_default[1] ); ?>"
		/>
	</p>
	<?php
}

/**
 * Saves alert post meta.
 *
 * @param int     $post_id The post ID.
 * @param WP_Post $post    Post object.
 */
function save_post_meta( $post_id, $post ) {

	/**
	 * Return early if:
	 *     the user doesn't have edit permissions;
	 *     this is an autosave;
	 *     this is a revision; or
	 *     the nonce can't be verified.
	 */
	if (
		( ! current_user_can( 'edit_post', $post_id ) )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		|| wp_is_post_revision( $post_id )
		|| ( ! isset( $_POST['hp_alert_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hp_alert_nonce'] ) ), 'hp_check_alert' ) )
		|| 'publish' !== $post->post_status
	) {
		return;
	}

	// Set up intial data to store in a transient.
	$alert_data = array(
		'heading' => $post->post_title,
		'content' => $post->post_excerpt,
		'url'     => get_the_permalink( $post_id ),
	);

	// Set up the initial expiration for the transient (none by default).
	$expiration = 0;

	if ( isset( $_POST['_hp_alert_display_through_date'] ) && '' !== sanitize_text_field( wp_unslash( $_POST['_hp_alert_display_through_date'] ) ) ) {
		$display_through  = sanitize_text_field( wp_unslash( $_POST['_hp_alert_display_through_date'] ) );
		$display_through .= ( isset( $_POST['_hp_alert_display_through_time'] ) && '' !== sanitize_text_field( wp_unslash( $_POST['_hp_alert_display_through_time'] ) ) )
			? ' ' . sanitize_text_field( wp_unslash( $_POST['_hp_alert_display_through_time'] ) ) . ':00'
			: ' 23:59:59';

		// Overwrite the expiration for the transient.
		$expiration = get_expiration( $display_through );

		update_post_meta( $post_id, '_hp_alert_display_through', $display_through );
	}

	set_transient( hp_get_alerts_transient_key(), $alert_data, $expiration );
}

/**
 * Clear the alert transient when an alert post is trashed.
 *
 * @param int $post_id The post ID.
 */
function delete_alert_transient( $post_id ) {
	if ( 'alert' === get_post_type( $post_id ) ) {
		delete_transient( hp_get_alerts_transient_key() );
	}
}

/**
 * Outputs the alert bar markup.
 */
function display_alert_bar() {

	// Return early if this is an alert post.
	if ( is_singular( 'alert' ) ) {
		return;
	}

	$alert_data = get_transient( hp_get_alerts_transient_key() );

	// Query for an alert post if no transient data is available.
	if ( ! $alert_data ) {

		// Set up intial data to store in a transient.
		$alert_data = 'no alert';

		// Set up the initial expiration for the transient (none by default).
		$expiration = 0;

		// Query for an alert post with a `_hp_alert_display_through`
		// value greater than the current date/time.
		$alert_query = new \WP_Query(
			array(
				'post_type'      => 'alert',
				'posts_per_page' => 1,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_hp_alert_display_through',
						'value'   => wp_date( 'Y-m-d H:i:s' ),
						'compare' => '>',
						'type'    => 'DATETIME',
					),
				),
			)
		);

		if ( $alert_query->have_posts() ) {
			while ( $alert_query->have_posts() ) {
				$alert_query->the_post();

				// Overwrite the data to store in the transient and make available to the script.
				$alert_data = array(
					'heading' => get_the_title(),
					'content' => get_the_excerpt(),
					'level'   => get_post_meta( get_the_ID(), '_hp_alert_level', true ),
					'url'     => get_the_permalink(),
				);

				// Overwrite the expiration for the transient.
				$display_through = get_post_meta( get_the_ID(), '_hp_alert_display_through', true );
				$expiration      = get_expiration( $display_through );
			}
		}

		wp_reset_postdata();

		set_transient( hp_get_alerts_transient_key(), $alert_data, $expiration );
	}

	// Return early if there is no alert data.
	if ( 'no alert' === $alert_data ) {
		return;
	}

	// Low level alerts should display only on the home page.
	$display_banner = 'low' === $alert_data['level'] && ! is_front_page()
		? false
		: true;

	if ( ! apply_filters( 'hp_alerts_display_banner', $display_banner, $alert_data ) ) {
		return;
	}

	$classes  = 'hp-alert';
	$classes .= ' ' . $alert_data['level'];

	?>
	<div class="<?php echo esc_attr( $classes ); ?>">
		<h1><?php echo esc_attr( $alert_data['heading'] ); ?></h1>
		<p><a href="<?php echo esc_url( $alert_data['url'] ); ?>"><?php echo wp_kses_post( $alert_data['content'] ); ?></a></p>
	</div>
	<?php
}
