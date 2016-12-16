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

	// Post & date format
	$post = get_post();

	// Get tokens
	$setup  = false;
	$tokens = Keyring::get_token_store()->get_tokens( array(
		'service' => 'publishiza',
		'user_id' => $post->post_author
	) );

	// Not authed
	if ( ! empty( $tokens ) ) {
		$setup = true;
	} ?>

	<div class="misc-pub-section misc-pub-section-last publishiza">
		<label for="publishiza"><?php esc_html_e( 'Publishiza', 'publishiza' ); ?></label>
		<?php if ( true === $setup ) :

			// Publishiza'ed?
			$status = ( 'publish' === $post->post_status );
			$meta   = get_post_meta( $post->ID, 'publishiza', true );
			$text   = empty( $meta )
				? esc_html__( 'off', 'publishiza' )
				: date( __( 'M j, Y @ H:i', 'publishiza' ), $meta['time'] ); ?>

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
		else :
			$request_kr_nonce = wp_create_nonce( 'keyring-request' );
			$request_nonce    = wp_create_nonce( 'keyring-request-publishiza' );
			echo '<a href="' . esc_url( Keyring_Util::admin_url( 'publishiza', array( 'action' => 'request', 'kr_nonce' => $request_kr_nonce, 'nonce' => $request_nonce ) ) ) . '">' . esc_html__( 'Connect', 'publishiza' ) . '</a>';
		endif;

	?></div>

<?php
}