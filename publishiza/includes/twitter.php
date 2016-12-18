<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Register the postmeta key
 *
 * @since 1.0.0
 */
function publishiza_register_post_meta() {
	register_meta( 'post', 'publishiza', array(
		'type'              => 'array',
		'description'       => esc_html__( 'Publishiza response information from Twitter', 'publishiza' ),
		'single'            => true,
		'sanitize_callback' => 'publishiza_sanitize_post_meta',
		'auth_callback'     => 'publishiza_auth_post_meta',
		'show_in_rest'      => true
	) );
}

/**
 * Publishiza to Twitter
 *
 * @since 1.0.0
 *
 * @param int $post_id
 * @param WP_Post $post
 */
function publishiza_publish_post( $post_id = 0, $post = null ) {

	// Bail if not Publishiza'ing
	if ( empty( $_POST['publishiza'] ) ) {
		return;
	}

	// Bail if already Publishizaed
	if ( get_post_meta( $post_id, 'publishiza', true ) ) {
		return;
	}

	// Bail if nonce fails
	if ( ! wp_verify_nonce( 'publishiza-nonce', 'publishiza-select' ) ) {
		//return;
	}

	// Publishiza & get response
	$response = publishiza_post_to_twitter( $post );

	// Save response to prevent
	add_post_meta( $post_id, 'publishiza', array(
		'time'  => time(),
		'start' => $response
	) );
}

/**
 * Publishiza a WordPress post to Twitter
 *
 * @since 1.0.0
 *
 * @param WP_Post $post
 */
function publishiza_post_to_twitter( $post = null ) {

	/**
	 * @var Keyring_Service_Twitter $service
	 */
	$service = Keyring::get_service_by_name( 'publishiza' );

	// Bail if no connection to Twitter
	if ( ! $service->is_connected() ) {
		return;
	}

	//$token = $service->build_token_meta();
	$tokens = Keyring::get_token_store()->get_tokens( array(
		'service' => 'publishiza',
		'user_id' => $post->post_author
	) );

	// Not authed
	if ( empty( $tokens ) ) {
		return false;
	}

	// Storm settings
	$sleep   = apply_filters( 'publishiza_storm_sleep',    2   );
	$length  = apply_filters( 'publishiza_storm_length',   119 );
	$control = apply_filters( 'publishiza_storm_control',  html_entity_decode( '&#x1f4a9;&#x1f329; ', 0, 'UTF-8') );
	$ndash   = apply_filters( 'publishiza_storm_divider',  html_entity_decode( '&ndash;',  0, 'UTF-8' ) );
	$ellip   = apply_filters( 'publishiza_storm_ellipsis', html_entity_decode( '&hellip;', 0, 'UTF-8' ) );

	// Process post content into Tweets
	$original_poo = $post->post_content;
	$decoded      = html_entity_decode( $original_poo, ENT_QUOTES, 'UTF-8' );
	$stripped     = wp_strip_all_tags( $decoded, true );
	$split        = wordwrap( $stripped, $length, "\n", false );
	$trimmed      = array_map( 'trim', explode( "\n", $split ) );
	$tweets       = array_filter( $trimmed );
	$count        = count( $tweets );
	$responses    = array();
	$first        = false;

	// Connections
	foreach ( $tokens as $token ) {

		// Set the token
		$service->token     = $token;
		$previous_status_id = false;

		// Storm
		foreach ( $tweets as $index => $tweet ) {

			// Build the prefix
			$position = (int) $index + 1;
			$prefix   = apply_filters( 'publishiza_storm_prefix', "{$control}{$position}/{$count} {$ndash} " );
			$text     = "{$prefix}{$tweet}";
 
			// Maybe append an ellipsis
			if ( ! empty( $ellip ) && ( $position !== $count ) ) {
				$text = "{$text}{$ellip}";
			}

			// Get request body
			$body = array(
				'status'    => $text,
				'trim_user' => 1
			);

			// Maybe in response to the first ID
			if ( ( $position > 1 ) && ! empty( $previous_status_id ) ) {
				$body['in_reply_to_status_id'] = (int) $previous_status_id;
			}

			// Send update to Twitter
			$response = $service->request( $service->update_url, array(
				'method' => $service->update_method,
				'body'   => $body
			) );

			// Error!
			if ( is_a( $response, 'Keyring_Error' ) ) {
				return false;
			}

			// Setup storm
			if ( isset( $response->id ) ) {
				$previous_status_id = $response->id;

				// Set first response ID
				if ( empty( $first ) && ( 1 === $position ) ) {
					$first = $response->id;
				}
			}

			// Add response to responses array (@todo: save for debug?)
			$responses[] = $response;

			// Wait to avoid being throttled
			sleep( $sleep );
		}
	}

	return $first;
}
