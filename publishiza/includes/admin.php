<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Tweak admin styling for a calendar specific layout
 *
 * @since 1.0.0
 */
function publishiza_admin_assets() {
	wp_enqueue_style( 'publishiza', publishiza_get_plugin_url() . 'assets/css/publishiza.css', false, publishiza_get_asset_version(), false );
	wp_enqueue_script( 'publishiza', publishiza_get_plugin_url() . 'assets/js/publishiza.js',   false, publishiza_get_asset_version(), true  );
}

/**
 * Output the post submit-box form field
 *
 * @since 1.0.0
 */
function publishiza_post_submitbox_start() {
	$post  = get_post();
	$datef = __( 'M j, Y @ H:i', 'publishiza' );

	// Can publishiza
	$status = ( 'publish' === $post->post_status );
	$meta   = get_post_meta( $post->ID, 'publishiza', true );
	$text   = empty( $meta )
		? esc_html__( 'off', 'publishiza' )
		: date( $datef, $meta['time'] ); ?>

	<div class="misc-pub-section misc-pub-section-last publishiza">
		<label for="publishiza"><?php esc_html_e( 'Publishiza', 'publishiza' ); ?></label>
		<span id="publishiza-display"><?php echo esc_html( $text ); ?></span>

		<?php if ( empty( $status ) && ( false !== $meta ) ) : ?>

			<a href="#" id="edit-publishiza" class="hide-if-no-js"><?php esc_html_e( 'Edit', 'publishiza' ); ?></a>
			<div id="publishiza-select">
				<select name="publishiza" id="publishiza">
					<option value="0"><?php esc_html_e( 'off',  'publishiza' ); ?></option>
					<option value="1"><?php esc_html_e( 'on', 'publishiza' ); ?></option>
				</select>
				<a href="#" id="save-publishiza" class="hide-if-no-js button"><?php esc_html_e( 'OK', 'publishiza' ); ?></a>
				<a href="#" id="cancel-publishiza" class="hide-if-no-js"><?php esc_html_e( 'Cancel', 'publishiza' ); ?></a>
			</div><?php

			//wp_nonce_field( 'publishiza-select', 'publishiza-nonce' );

		endif;

	?></div>

<?php
}